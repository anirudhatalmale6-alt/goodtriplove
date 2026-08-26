<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FeatureFlagService;
use App\Services\OllamaClient;
use App\Services\TechnicalErrorCenter;
use App\Services\YouTubeClient;
use App\Services\YouTubeQuotaManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Operations: service status, YouTube quota, feature flags and the error
 * centre. Read-only where possible; the only switches here are the feature
 * flags and maintenance mode.
 */
class OperationsController extends Controller
{
    /** Flags the administration can toggle without a deploy. */
    public const FLAGS = [
        'maintenance_mode' => 'Mode maintenance (le site répond 503 sauf pour les super admins)',
        'video_collector' => 'Collecteur vidéo automatique',
        'ai_classification' => 'Classification par le modèle local',
        'business_registration' => 'Inscription des établissements',
        'user_registration' => 'Création de comptes visiteurs',
        'ads' => 'Affichage des espaces publicitaires',
        'tv' => 'GoodTripLove TV',
    ];

    public function status(YouTubeClient $youtube, OllamaClient $ollama): View
    {
        return view('admin.operations.status', [
            'checks' => $this->runChecks($youtube, $ollama),
            'lastHealth' => DB::table('service_health')->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function youtubeQuota(YouTubeClient $youtube, YouTubeQuotaManager $quota): View
    {
        $limit = (int) config('core_operations.youtube.daily_quota');
        $used = $quota->used();

        return view('admin.operations.youtube-quota', [
            'configured' => $youtube->isConfigured(),
            'limit' => $limit,
            'used' => $used,
            'remaining' => $youtube->unitsRemaining(),
            'percent' => $limit > 0 ? round($used / $limit * 100, 1) : 0,
            'warningPercent' => (int) config('core_operations.youtube.warning_percent'),
            'hardStopPercent' => (int) config('core_operations.youtube.hard_stop_percent'),
            'searchCost' => (int) config('goodtriplove.youtube.cost.search'),
            'lastRequestAt' => DB::table('youtube_quota_usage')
                ->where('usage_date', today()->toDateString())->value('last_request_at'),
            'history' => DB::table('youtube_quota_usage')->orderByDesc('usage_date')->limit(14)->get(),
        ]);
    }

    public function features(FeatureFlagService $flags): View
    {
        $state = [];

        foreach (self::FLAGS as $key => $label) {
            $state[$key] = [
                'label' => $label,
                // Everything except maintenance defaults to on, so a fresh
                // install behaves normally before anything is configured.
                'enabled' => $flags->enabled($key, $key !== 'maintenance_mode'),
            ];
        }

        return view('admin.operations.features', ['flags' => $state]);
    }

    public function updateFeatures(Request $request, FeatureFlagService $flags): RedirectResponse
    {
        foreach (array_keys(self::FLAGS) as $key) {
            $flags->set($key, $request->boolean('flags.'.$key));
        }

        return back()->with('status', 'Fonctionnalités mises à jour.');
    }

    public function errors(TechnicalErrorCenter $centre): View
    {
        return view('admin.operations.errors', [
            'events' => DB::table('technical_error_events')->orderByDesc('id')->limit(100)->get(),
            'failedJobs' => DB::table('failed_jobs')->orderByDesc('id')->limit(20)->get(),
        ]);
    }


    /**
     * Mail is not "configured" just because a mailer is set.
     *
     * If the sending domain publishes SPF that does not authorise this server,
     * every verification code is sent into a hard fail and users simply never
     * receive one — with no error anywhere in the application. So the check
     * reads the actual DNS for the From domain.
     */
    private function mailCheck(): array
    {
        $mailer = config('mail.default');
        $from = (string) config('mail.from.address');
        $domain = Str::after($from, '@');

        if ($mailer === 'log') {
            return ['name' => 'Messagerie', 'status' => 'warning', 'detail' => 'mailer : log — aucun e-mail n’est réellement envoyé'];
        }

        if (! $domain) {
            return ['name' => 'Messagerie', 'status' => 'warning', 'detail' => 'MAIL_FROM_ADDRESS non renseigné'];
        }

        $spf = '';
        $mx = [];

        try {
            foreach (@dns_get_record($domain, DNS_TXT) ?: [] as $record) {
                $text = $record['txt'] ?? '';

                if (Str::startsWith($text, 'v=spf1')) {
                    $spf = $text;
                }
            }

            foreach (@dns_get_record($domain, DNS_MX) ?: [] as $record) {
                $mx[] = $record['target'] ?? '';
            }
        } catch (\Throwable) {
            return ['name' => 'Messagerie', 'status' => 'warning', 'detail' => 'DNS illisible pour '.$domain];
        }

        $serverIp = gethostbyname(gethostname());
        $spfAuthorises = $spf !== '' && (
            str_contains($spf, 'ip4:'.$serverIp)
            || str_contains($spf, '+all')
            || str_contains($spf, '~all') && ! str_contains($spf, '-all')
        );

        if ($spf !== '' && ! $spfAuthorises) {
            return [
                'name' => 'Messagerie',
                'status' => 'down',
                'detail' => 'SPF de '.$domain.' n’autorise pas ce serveur ('.$serverIp.') : "'.$spf
                    .'". Les codes de vérification seront rejetés. MX : '.(implode(', ', $mx) ?: 'aucun'),
            ];
        }

        return [
            'name' => 'Messagerie',
            'status' => 'ok',
            'detail' => 'mailer : '.$mailer.' · expéditeur : '.$from.' · SPF : '.($spf ?: 'aucun enregistrement'),
        ];
    }

    /**
     * @return array<int, array{name: string, status: string, detail: string}>
     */
    private function runChecks(YouTubeClient $youtube, OllamaClient $ollama): array
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks[] = ['name' => 'Base de données', 'status' => 'ok', 'detail' => config('database.default')];
        } catch (\Throwable $e) {
            $checks[] = ['name' => 'Base de données', 'status' => 'down', 'detail' => $e->getMessage()];
        }

        $pending = DB::table('jobs')->count();
        $checks[] = [
            'name' => 'File d’attente',
            'status' => $pending > (int) config('growth_ops.monitoring.queue_warning_count') ? 'warning' : 'ok',
            'detail' => $pending.' job(s) en attente · driver '.config('queue.default'),
        ];

        $lastRun = DB::table('collector_runs')->orderByDesc('id')->value('created_at');
        $checks[] = [
            'name' => 'Planificateur (dernière collecte)',
            'status' => $lastRun ? 'ok' : 'warning',
            'detail' => $lastRun ?: 'aucune exécution enregistrée',
        ];

        $checks[] = $this->mailCheck();

        $checks[] = [
            'name' => 'API YouTube',
            'status' => $youtube->isConfigured() ? ($youtube->isNearLimit() ? 'warning' : 'ok') : 'warning',
            'detail' => $youtube->isConfigured()
                ? $youtube->unitsUsedToday().' unités utilisées aujourd’hui'
                : 'clé API absente',
        ];

        $checks[] = [
            'name' => 'Modèle local (Ollama)',
            'status' => ! $ollama->enabled() ? 'warning' : ($ollama->isUp() ? 'ok' : 'down'),
            'detail' => $ollama->enabled() ? config('goodtriplove.ollama.model') : 'désactivé',
        ];

        $checks[] = [
            'name' => 'HTTPS',
            'status' => request()->secure() || app()->environment('local') ? 'ok' : 'warning',
            'detail' => request()->secure() ? 'requête servie en HTTPS' : 'requête servie en HTTP',
        ];

        $checks[] = [
            'name' => 'Cloudflare Turnstile',
            'status' => config('security.turnstile.secret_key') ? 'ok' : 'warning',
            'detail' => config('security.turnstile.secret_key')
                ? 'clés configurées'
                : 'non configuré — la vérification est ignorée',
        ];

        return $checks;
    }
}
