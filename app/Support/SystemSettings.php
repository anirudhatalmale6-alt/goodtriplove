<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The technical settings an administrator may change without a developer.
 *
 * These are deliberately separate from {@see SiteSettings}. That one holds the
 * things a visitor reads — a name, a slogan, a phone number. This one holds
 * keys and switches that change how the application *behaves*, so it carries
 * two things the other does not: values are encrypted at rest when they are
 * secret, and every key names the configuration entry it overrides.
 *
 * The override is what keeps this small. Nothing in the application reads this
 * class: `apply()` pushes the stored values into the live configuration at
 * boot, so `TurnstileService`, `YouTubeClient` and the mailer keep reading
 * `config(...)` exactly as they always did. A setting that has never been saved
 * leaves the configured value — and therefore the `.env` — untouched.
 */
class SystemSettings
{
    /** Marks a stored value as ciphertext, so plain rows stay readable. */
    private const ENCRYPTED_PREFIX = 'enc::';

    /**
     * type:   bool | text | secret | int | select
     * config: the configuration entry this value overrides at boot
     * secret: never sent back to the browser in full
     */
    public const DEFINITIONS = [
        'security' => [
            'label' => 'Authentification à deux facteurs (Google Authenticator)',
            'help' => 'Quand la 2FA est active, tout compte administrateur doit valider un code à 6 chiffres généré par Google Authenticator, en plus de son mot de passe.',
            'items' => [
                'admin_2fa_required' => [
                    'type' => 'bool',
                    'label' => 'Exiger la 2FA pour les administrateurs',
                    'config' => 'security_center.admin_2fa_required',
                    'help' => 'Désactiver ce réglage supprime la deuxième barrière protégeant le Super Admin. À ne faire que temporairement, par exemple si tu as perdu ton téléphone.',
                ],
            ],
        ],

        'turnstile' => [
            'label' => 'Cloudflare Turnstile (anti-robot)',
            'help' => 'Turnstile remplace le captcha sur l\'inscription, la connexion, l\'espace professionnel et l\'oubli de mot de passe. Les deux clés se récupèrent sur dash.cloudflare.com, rubrique Turnstile.',
            'items' => [
                'turnstile_enabled' => [
                    'type' => 'bool',
                    'label' => 'Activer Turnstile',
                    // On by default, so that saving the two keys is enough to
                    // switch the protection on. A separate switch that also
                    // had to be found would only produce silent no-ops.
                    'default' => true,
                    'help' => 'Si les deux clés ne sont pas renseignées, la vérification est ignorée même quand ce réglage est actif — un anti-robot mal configuré bloquerait les vrais visiteurs sans arrêter personne.',
                ],
                'turnstile_site_key' => [
                    'type' => 'text',
                    'label' => 'Site Key',
                    'config' => 'security.turnstile.site_key',
                    'help' => 'Clé publique, visible dans la page. Commence généralement par 0x.',
                ],
                'turnstile_secret_key' => [
                    'type' => 'secret',
                    'label' => 'Secret Key',
                    'config' => 'security.turnstile.secret_key',
                    'help' => 'Clé privée. Elle ne quitte jamais le serveur et n\'est jamais réaffichée après enregistrement.',
                ],
            ],
        ],

        'youtube' => [
            'label' => 'Clé API YouTube (Data API v3)',
            'help' => 'Sans clé valide, le collecteur de vidéos ne peut plus rien importer ni rafraîchir. Le quota gratuit est de 10 000 unités par jour : une recherche coûte 100 unités, une mise à jour de statistiques 1 unité pour 50 vidéos.',
            'items' => [
                'youtube_api_key' => [
                    'type' => 'secret',
                    'label' => 'Clé API YouTube',
                    'config' => 'goodtriplove.youtube.api_key',
                    'help' => 'Si la clé est restreinte par adresse IP dans Google Cloud, elle doit autoriser 37.59.118.121.',
                ],
            ],
        ],

        'social' => [
            'label' => 'Réseaux sociaux (YouTube, TikTok, Instagram, Facebook)',
            'help' => "Chaque plateforme peut être acceptée ou refusée séparément. Une plateforme désactivée refuse l'ajout d'une nouvelle vidéo, mais ne masque pas celles déjà publiées : retirer une vidéo du site reste une décision par vidéo. Les identifiants ne servent qu'aux informations détaillées et à l'import automatique — le lecteur officiel fonctionne sans aucune clé.",
            'items' => [
                'social_youtube_enabled' => [
                    'type' => 'bool',
                    'label' => 'Accepter les vidéos YouTube',
                    'default' => true,
                ],
                'social_tiktok_enabled' => [
                    'type' => 'bool',
                    'label' => 'Accepter les vidéos TikTok',
                    'default' => true,
                    'help' => 'Titre, auteur et miniature sont récupérés sans clé.',
                ],
                'social_instagram_enabled' => [
                    'type' => 'bool',
                    'label' => 'Accepter les vidéos Instagram',
                    'default' => true,
                    'help' => "Instagram ne renvoie ni titre ni miniature sans application Meta approuvée : le titre est à saisir à la main tant que l'application n'est pas validée.",
                ],
                'social_facebook_enabled' => [
                    'type' => 'bool',
                    'label' => 'Accepter les vidéos Facebook',
                    'default' => true,
                ],
                'social_require_approval' => [
                    'type' => 'bool',
                    'label' => 'Validation manuelle obligatoire avant publication',
                    'default' => true,
                    'help' => "Désactiver ce réglage publie immédiatement toute vidéo ajoutée, sans que personne ne l'ait regardée.",
                ],
                'social_duplicate_check' => [
                    'type' => 'bool',
                    'label' => 'Contrôle anti-doublon automatique',
                    'default' => true,
                    'help' => "Refuse une vidéo déjà présente, y compris republiée sous un autre identifiant ou sur une autre plateforme. L'identifiant exact est refusé dans tous les cas par la base.",
                ],
                'social_meta_token' => [
                    'type' => 'secret',
                    'label' => 'Jeton d\'accès Meta (Instagram + Facebook)',
                    'config' => 'goodtriplove.social.meta.access_token',
                    'help' => "À renseigner le jour où ton application Meta est approuvée. Stocké chiffré, jamais réaffiché, jamais envoyé au navigateur.",
                ],
                'social_tiktok_token' => [
                    'type' => 'secret',
                    'label' => 'Jeton d\'accès TikTok',
                    'config' => 'goodtriplove.social.tiktok.access_token',
                    'help' => "Nécessaire uniquement pour importer automatiquement le fil d'un compte. L'ajout par URL n'en a pas besoin.",
                ],
            ],
        ],

        'mail' => [
            'label' => 'Envoi des emails (SMTP)',
            'help' => 'Sert aux codes de vérification à 6 chiffres, à la réinitialisation de mot de passe et aux alertes. Le serveur ne peut pas livrer directement vers Gmail : le port 25 sortant est bloqué et le SPF du domaine n\'autorise que les serveurs d\'OVH. Il faut donc passer par un serveur d\'envoi authentifié.',
            'items' => [
                'mail_host' => [
                    'type' => 'text',
                    'label' => 'Serveur SMTP',
                    'config' => 'mail.mailers.smtp.host',
                    'help' => 'Pour une boîte OVH : ssl0.ovh.net',
                ],
                'mail_port' => [
                    'type' => 'int',
                    'label' => 'Port',
                    'config' => 'mail.mailers.smtp.port',
                    'help' => '587 pour STARTTLS, 465 pour TLS direct.',
                ],
                'mail_username' => [
                    'type' => 'text',
                    'label' => 'Identifiant',
                    'config' => 'mail.mailers.smtp.username',
                    'help' => 'L\'adresse complète de la boîte, par exemple noreply@goodtriplove.com',
                ],
                'mail_password' => [
                    'type' => 'secret',
                    'label' => 'Mot de passe',
                    'config' => 'mail.mailers.smtp.password',
                    'help' => 'Stocké chiffré. Il n\'est jamais réaffiché et ne sort jamais du serveur.',
                ],
                'mail_encryption' => [
                    'type' => 'select',
                    'label' => 'Chiffrement',
                    'config' => 'mail.mailers.smtp.scheme',
                    'options' => ['' => 'Automatique', 'smtp' => 'STARTTLS (port 587)', 'smtps' => 'TLS direct (port 465)'],
                ],
                'mail_from_address' => [
                    'type' => 'text',
                    'label' => 'Adresse expéditeur',
                    'config' => 'mail.from.address',
                    'help' => 'Doit appartenir au même domaine que l\'identifiant, sinon les emails partent en spam.',
                ],
                'mail_from_name' => [
                    'type' => 'text',
                    'label' => 'Nom expéditeur',
                    'config' => 'mail.from.name',
                ],
            ],
        ],
    ];

    /** Every declared key, flattened. */
    public static function keys(): array
    {
        return array_merge(...array_map(
            fn (array $group) => array_keys($group['items']),
            array_values(self::DEFINITIONS),
        ));
    }

    public static function definition(string $key): ?array
    {
        foreach (self::DEFINITIONS as $group) {
            if (isset($group['items'][$key])) {
                return $group['items'][$key];
            }
        }

        return null;
    }

    public static function isSecret(string $key): bool
    {
        return (self::definition($key)['type'] ?? null) === 'secret';
    }

    /**
     * The stored value, decrypted, or null when nothing has ever been saved.
     *
     * A secret that cannot be decrypted — the only realistic cause is APP_KEY
     * having been regenerated — is reported as absent rather than thrown. The
     * alternative is a 500 on every page, including the admin screen that would
     * let someone type the key in again.
     */
    public static function stored(string $key): mixed
    {
        $raw = SiteSetting::get($key);

        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_string($raw) && str_starts_with($raw, self::ENCRYPTED_PREFIX)) {
            try {
                return Crypt::decryptString(substr($raw, strlen(self::ENCRYPTED_PREFIX)));
            } catch (DecryptException $e) {
                Log::warning('a stored secret could not be decrypted', ['key' => $key]);

                return null;
            }
        }

        return $raw;
    }

    /** The value in force: what is stored, else the configuration, else the declared default. */
    public static function effective(string $key): mixed
    {
        $stored = self::stored($key);

        if ($stored !== null) {
            return $stored;
        }

        $definition = self::definition($key) ?? [];

        if (isset($definition['config'])) {
            return config($definition['config']);
        }

        return $definition['default'] ?? null;
    }

    public static function put(string $key, mixed $value): void
    {
        $definition = self::definition($key);

        if (! $definition) {
            return;
        }

        $value = match ($definition['type']) {
            'bool' => (bool) $value,
            'int' => $value === null || $value === '' ? null : (int) $value,
            default => $value === null ? null : (string) $value,
        };

        if ($definition['type'] === 'secret' && filled($value)) {
            $value = self::ENCRYPTED_PREFIX.Crypt::encryptString((string) $value);
        }

        SiteSetting::put($key, $value, 'system');
    }

    /** True once both Turnstile keys exist and the switch is on. */
    public static function turnstileActive(): bool
    {
        return (bool) self::effective('turnstile_enabled')
            && filled(config('security.turnstile.site_key'))
            && filled(config('security.turnstile.secret_key'));
    }

    /**
     * Push every saved value into the live configuration.
     *
     * Called once per request from the service provider. It must never throw:
     * this runs before the router, so an exception here takes down the whole
     * site — including the screen an administrator would use to correct the
     * value that caused it. A missing table (a fresh clone, mid-migration) is
     * the normal case on first deploy, not an error.
     */
    public static function apply(): void
    {
        try {
            foreach (self::DEFINITIONS as $group) {
                foreach ($group['items'] as $key => $definition) {
                    if (! isset($definition['config'])) {
                        continue;
                    }

                    $value = self::stored($key);

                    // Never overwrite a configured value with nothing. An
                    // administrator who has saved no SMTP password must keep
                    // whatever the .env provides, not lose it.
                    if ($value === null || $value === '') {
                        continue;
                    }

                    config([$definition['config'] => $value]);
                }
            }

            self::guardMailTransport();
        } catch (\Throwable $e) {
            Log::warning('system settings could not be applied', ['message' => $e->getMessage()]);
        }
    }

    /**
     * TLS is only optional on the loopback.
     *
     * `config/mail.php` turns the certificate check off because the local MTA
     * presents a certificate for its own hostname and a connection to
     * 127.0.0.1 fails the name check. The moment an administrator points the
     * mailer at a real server, those same two options would send the mailbox
     * password across the network in clear. So they are decided here from the
     * host rather than left to whoever last edited the .env.
     */
    private static function guardMailTransport(): void
    {
        $host = (string) config('mail.mailers.smtp.host');

        if ($host === '') {
            return;
        }

        $isLoopback = in_array($host, ['127.0.0.1', '::1', 'localhost'], true);

        if (! $isLoopback) {
            config([
                'mail.mailers.smtp.auto_tls' => true,
                'mail.mailers.smtp.verify_peer' => true,
            ]);
        }
    }

    /** A secret rendered for the admin: enough to recognise, not enough to use. */
    public static function mask(?string $value): string
    {
        if (! filled($value)) {
            return '';
        }

        $value = (string) $value;

        return strlen($value) <= 4
            ? str_repeat('•', strlen($value))
            : str_repeat('•', 8).Str::substr($value, -4);
    }
}
