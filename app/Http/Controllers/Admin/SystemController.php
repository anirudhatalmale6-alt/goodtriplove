<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * The screen that makes the keys and switches editable without a developer.
 *
 * Each section can be *tested* as well as saved, because saving a key proves
 * nothing: a YouTube key restricted to the wrong IP, a Turnstile secret with a
 * character missing and a mailbox password that is simply wrong all look
 * identical in a form. The tests here talk to the real services and report
 * what they actually answered.
 */
class SystemController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $current = [];

        foreach (SystemSettings::keys() as $key) {
            $value = SystemSettings::effective($key);

            // A secret is never sent back to the browser. The masked form is
            // enough to tell "the right key is saved" from "nothing is saved",
            // which is the only question the page has to answer.
            $current[$key] = SystemSettings::isSecret($key)
                ? SystemSettings::mask(is_string($value) ? $value : null)
                : $value;
        }

        return view('admin.system.index', [
            'definitions' => SystemSettings::DEFINITIONS,
            'current' => $current,
            'turnstileActive' => SystemSettings::turnstileActive(),
            'youtubeConfigured' => filled(config('goodtriplove.youtube.api_key')),
            'mailConfigured' => filled(config('mail.mailers.smtp.host')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['array'],
            'settings.admin_2fa_required' => ['nullable', 'boolean'],
            'settings.turnstile_enabled' => ['nullable', 'boolean'],
            'settings.turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'settings.turnstile_secret_key' => ['nullable', 'string', 'max:255'],
            'settings.youtube_api_key' => ['nullable', 'string', 'max:255'],
            // Secrets are read from the validated data, so a key missing here
            // is silently never saved. The booleans below come from
            // $request->boolean() and are listed for the same reason.
            'settings.social_youtube_enabled' => ['nullable', 'boolean'],
            'settings.social_tiktok_enabled' => ['nullable', 'boolean'],
            'settings.social_instagram_enabled' => ['nullable', 'boolean'],
            'settings.social_facebook_enabled' => ['nullable', 'boolean'],
            'settings.social_require_approval' => ['nullable', 'boolean'],
            'settings.social_duplicate_check' => ['nullable', 'boolean'],
            'settings.social_meta_token' => ['nullable', 'string', 'max:500'],
            'settings.social_tiktok_token' => ['nullable', 'string', 'max:500'],
            'settings.mail_host' => ['nullable', 'string', 'max:190'],
            'settings.mail_port' => ['nullable', 'integer', 'between:1,65535'],
            'settings.mail_username' => ['nullable', 'string', 'max:190'],
            'settings.mail_password' => ['nullable', 'string', 'max:190'],
            'settings.mail_encryption' => ['nullable', 'in:,smtp,smtps'],
            'settings.mail_from_address' => ['nullable', 'email:rfc', 'max:190'],
            'settings.mail_from_name' => ['nullable', 'string', 'max:190'],
            'clear' => ['array'],
        ]);

        $posted = $data['settings'] ?? [];
        $clear = array_keys($data['clear'] ?? []);
        $changed = [];

        foreach (SystemSettings::keys() as $key) {
            $definition = SystemSettings::definition($key);

            if ($definition['type'] === 'bool') {
                // An unticked checkbox posts nothing at all, so a switch read
                // from the posted data could be turned on and never off.
                $value = $request->boolean('settings.'.$key);

                if ((bool) SystemSettings::effective($key) !== $value) {
                    SystemSettings::put($key, $value);
                    $changed[$key] = $value ? 'activé' : 'désactivé';
                }

                continue;
            }

            if (SystemSettings::isSecret($key)) {
                if (in_array($key, $clear, true)) {
                    SystemSettings::put($key, null);
                    $changed[$key] = 'effacé';
                } elseif (filled($posted[$key] ?? null)) {
                    // Blank means "leave it alone" — the field is shown masked,
                    // so an untouched form must not wipe a working key.
                    SystemSettings::put($key, $posted[$key]);
                    $changed[$key] = 'modifié';
                }

                continue;
            }

            if (! array_key_exists($key, $posted)) {
                continue;
            }

            if ((string) SystemSettings::effective($key) !== (string) $posted[$key]) {
                SystemSettings::put($key, $posted[$key]);
                $changed[$key] = $posted[$key];
            }
        }

        if ($changed !== []) {
            // The audit trail records *that* a secret changed, never its value.
            $this->audit->record('system_settings.update', null, [], $changed);
        }

        return back()->with('status', __('gtl.saved'));
    }

    /**
     * videos.list on a single id — one quota unit, the cheapest real call the
     * API offers. A key that is merely present in the form tells us nothing;
     * this distinguishes a wrong key from a key blocked by its own IP
     * restriction, which is a failure this project has already hit once.
     */
    public function testYoutube(): RedirectResponse
    {
        $key = config('goodtriplove.youtube.api_key');

        if (! filled($key)) {
            return back()->with('test_error', 'Aucune clé YouTube enregistrée.');
        }

        try {
            $request = Http::timeout(20)->acceptJson();

            if (config('goodtriplove.youtube.force_ipv4')) {
                $request = $request->withOptions(['force_ip_resolve' => 'v4']);
            }

            $response = $request->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'id',
                'id' => 'BHACKCNDMW8',   // a long-standing public video, 1 unit
                'key' => $key,
            ]);
        } catch (\Throwable $e) {
            return back()->with('test_error', 'La clé YouTube n\'a pas pu être testée : '.$e->getMessage());
        }

        if ($response->successful()) {
            return back()->with('test_ok', 'Clé YouTube valide. Google a répondu correctement (coût du test : 1 unité de quota).');
        }

        $reason = (string) data_get($response->json(), 'error.errors.0.reason');
        $message = (string) data_get($response->json(), 'error.message', 'réponse HTTP '.$response->status());

        $explanation = match ($reason) {
            'ipRefererBlocked' => 'La clé est valide mais restreinte à d\'autres adresses IP. Dans Google Cloud, autorise 37.59.118.121 pour cette clé.',
            'quotaExceeded', 'dailyLimitExceeded' => 'La clé est valide mais le quota du jour est épuisé. Il se réinitialise à 9h00 (heure de Paris).',
            'keyInvalid', 'badRequest' => 'La clé est refusée par Google : vérifie qu\'elle a été copiée en entier.',
            'accessNotConfigured' => 'La YouTube Data API v3 n\'est pas activée pour ce projet Google Cloud.',
            default => $message,
        };

        return back()->with('test_error', 'Test YouTube échoué. '.$explanation);
    }

    /**
     * Cloudflare's siteverify, called with a deliberately invalid token.
     *
     * Its answer separates the two cases we care about: a wrong secret is
     * reported as `invalid-input-secret`, while a correct secret gets as far as
     * rejecting the token — `invalid-input-response`. So a failure we *expect*
     * is what proves the secret is right, without needing a real browser
     * challenge to have been solved first.
     */
    public function testTurnstile(): RedirectResponse
    {
        $secret = config('security.turnstile.secret_key');

        if (! filled($secret)) {
            return back()->with('test_error', 'Aucune Secret Key Turnstile enregistrée.');
        }

        try {
            $response = Http::asForm()->timeout(15)->post(config('security.turnstile.verify_url'), [
                'secret' => $secret,
                'response' => 'gtl-connectivity-probe',
            ]);
        } catch (\Throwable $e) {
            return back()->with('test_error', 'Turnstile n\'a pas pu être contacté : '.$e->getMessage());
        }

        $errors = (array) data_get($response->json(), 'error-codes', []);

        if (in_array('invalid-input-secret', $errors, true)) {
            return back()->with('test_error', 'Secret Key refusée par Cloudflare. Vérifie qu\'elle correspond bien à la Site Key du même site Turnstile.');
        }

        if (in_array('invalid-input-response', $errors, true)) {
            return back()->with('test_ok', 'Secret Key valide : Cloudflare l\'a acceptée et n\'a rejeté que le jeton de test, ce qui est le résultat attendu.');
        }

        if (! filled(config('security.turnstile.site_key'))) {
            return back()->with('test_error', 'La Secret Key semble acceptée, mais la Site Key est vide : le widget ne peut pas s\'afficher.');
        }

        return back()->with('test_error', 'Réponse inattendue de Cloudflare : '.json_encode($response->json()));
    }

    /**
     * Sends one real message through the configured mailer.
     *
     * The whole point is the failure path: the SMTP server's own refusal is
     * far more useful than "an error occurred", and it is what told us the
     * server was answering "550 relay not permitted" to everything leaving
     * the machine.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email:rfc', 'max:190'],
        ]);

        try {
            Mail::raw(
                "Ceci est un email de test envoyé depuis l'administration de GoodTripLove.\n\n"
                ."Si tu le reçois, l'envoi des codes de vérification à 6 chiffres fonctionne.\n"
                .'Serveur utilisé : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'),
                function ($message) use ($data) {
                    $message->to($data['to'])->subject('GoodTripLove — test d\'envoi');
                }
            );
        } catch (\Throwable $e) {
            return back()->with('test_error', 'Envoi refusé : '.$this->mailHint($e->getMessage()));
        }

        return back()->with('test_ok', 'Email de test accepté par le serveur d\'envoi, à destination de '.$data['to']
            .'. Vérifie la boîte de réception, et le dossier spam.');
    }

    /** Turns the three SMTP refusals this host actually produces into next steps. */
    private function mailHint(string $message): string
    {
        $short = trim(substr($message, 0, 300));

        if (str_contains($message, 'relay not permitted') || str_contains($message, '550')) {
            return $short."\n\nLe serveur mail refuse de relayer vers une adresse externe. "
                .'Il faut renseigner un serveur d\'envoi authentifié (ssl0.ovh.net, port 587) avec l\'identifiant et le mot de passe d\'une boîte OVH.';
        }

        if (str_contains($message, 'Connection could not be established') || str_contains($message, 'Connection timed out')) {
            return $short."\n\nLe serveur d'envoi n'est pas joignable depuis ce serveur. "
                .'Le port 25 sortant est bloqué par l\'hébergeur : utilise le port 587 ou 465.';
        }

        if (str_contains($message, 'Authentication') || str_contains($message, '535')) {
            return $short."\n\nL'identifiant ou le mot de passe est refusé. L'identifiant doit être l'adresse email complète.";
        }

        return $short;
    }
}
