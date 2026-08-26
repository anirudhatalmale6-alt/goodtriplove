<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

/**
 * Starting legal texts, in French and English.
 *
 * Two deliberate decisions:
 *
 * 1. Every factual statement about the company — publisher, address, company
 *    number, publication director, host, DPO — is a [[…]] placeholder. Those
 *    are assertions about a real legal entity and must be supplied and checked
 *    by the operator, not invented here.
 *
 * 2. Only fr and en are seeded. The other four languages fall back to French
 *    with a visible "shown in FR" notice until a real translation is published
 *    from the administration — a machine-translated legal page that looks
 *    official is worse than an honest fallback.
 */
class LegalDocumentSeeder extends Seeder
{
    private const VERSION = '1.0';

    public function run(): void
    {
        foreach ($this->documents() as $key => $locales) {
            foreach ($locales as $locale => $document) {
                LegalDocument::updateOrCreate(
                    ['key' => $key, 'locale' => $locale, 'version' => self::VERSION],
                    [
                        'title' => $document['title'],
                        'content' => $this->wrap($document['body']),
                        'published' => true,
                        'published_at' => now(),
                    ]
                );
            }
        }
    }

    private function wrap(array $sections): string
    {
        $html = '';

        foreach ($sections as $heading => $paragraphs) {
            if (! is_int($heading)) {
                $html .= '<h2>'.e($heading)."</h2>\n";
            }

            foreach ((array) $paragraphs as $paragraph) {
                $html .= '<p>'.$paragraph."</p>\n";
            }
        }

        return $html;
    }

    private function documents(): array
    {
        $placeholder = fn (string $label) => '<strong>[['.$label.']]</strong>';

        return [
            'legal-notice' => [
                'fr' => [
                    'title' => 'Mentions légales',
                    'body' => [
                        'Éditeur du site' => [
                            'Le site GoodTripLove.com est édité par '.$placeholder('raison sociale').', '
                            .$placeholder('forme juridique').' au capital de '.$placeholder('capital').', '
                            .'dont le siège social est situé '.$placeholder('adresse complète').', '
                            .'immatriculée sous le numéro '.$placeholder('SIREN / numéro d’immatriculation').'.',
                            'Directeur de la publication : '.$placeholder('nom du directeur de la publication').'.',
                            'Contact : '.config('goodtriplove.legal.contact_email').'.',
                        ],
                        'Hébergement' => [
                            'Le site est hébergé par '.$placeholder('hébergeur').', '.$placeholder('adresse de l’hébergeur').'.',
                        ],
                        'Nature du service' => [
                            'GoodTripLove est une plateforme de découverte qui référence des lieux et met en avant '
                            .'des vidéos publiques publiées par leurs créateurs sur des plateformes tierces. '
                            .'GoodTripLove n’héberge aucune vidéo : la lecture s’effectue toujours dans le lecteur '
                            .'officiel de la plateforme d’origine.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Legal notice',
                    'body' => [
                        'Publisher' => [
                            'GoodTripLove.com is published by '.$placeholder('company name').', '
                            .$placeholder('legal form').', registered office at '.$placeholder('full address').', '
                            .'company number '.$placeholder('company registration number').'.',
                            'Publication director: '.$placeholder('publication director').'.',
                            'Contact: '.config('goodtriplove.legal.contact_email').'.',
                        ],
                        'Hosting' => [
                            'The site is hosted by '.$placeholder('host').', '.$placeholder('host address').'.',
                        ],
                        'Nature of the service' => [
                            'GoodTripLove is a discovery platform that lists places and surfaces public videos '
                            .'published by their creators on third-party platforms. GoodTripLove hosts no video: '
                            .'playback always happens in the original platform’s official player.',
                        ],
                    ],
                ],
            ],

            'terms' => [
                'fr' => [
                    'title' => 'Conditions générales d’utilisation',
                    'body' => [
                        'Objet' => [
                            'Les présentes conditions régissent l’accès et l’utilisation du site GoodTripLove '
                            .'et de ses applications mobiles.',
                        ],
                        'Comptes' => [
                            'La création d’un compte est gratuite. L’adresse e-mail doit être vérifiée par un code '
                            .'à 6 chiffres. Les comptes professionnels permettent de proposer une fiche '
                            .'d’établissement, publiée après validation par l’équipe.',
                            'Chaque utilisateur est responsable de la confidentialité de ses identifiants. '
                            .'Les comptes disposant de droits d’administration doivent activer la double '
                            .'authentification.',
                        ],
                        'Contenus proposés par les utilisateurs' => [
                            'En proposant une vidéo ou une fiche, l’utilisateur déclare disposer des droits '
                            .'nécessaires ou que le contenu est licitement accessible au public. '
                            .'Aucun contenu n’est publié sans validation préalable.',
                        ],
                        'Suspension' => [
                            'Un compte peut être suspendu en cas de manquement aux présentes conditions, '
                            .'de signalement fondé ou d’activité automatisée abusive.',
                        ],
                        'Limitation de responsabilité' => [
                            'Les informations relatives aux lieux (horaires, adresses, prix) sont fournies à titre '
                            .'indicatif et peuvent évoluer. GoodTripLove ne saurait être tenu responsable du contenu '
                            .'des vidéos tierces, qui relève de leurs auteurs et des plateformes qui les hébergent.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Terms of use',
                    'body' => [
                        'Purpose' => [
                            'These terms govern access to and use of the GoodTripLove website and mobile apps.',
                        ],
                        'Accounts' => [
                            'Creating an account is free. The email address must be verified with a 6-digit code. '
                            .'Business accounts may submit a place listing, which is published after review.',
                            'Each user is responsible for keeping their credentials confidential. Accounts with '
                            .'administration rights must enable two-factor authentication.',
                        ],
                        'User-submitted content' => [
                            'By submitting a video or a listing, the user declares that they hold the necessary '
                            .'rights or that the content is lawfully available to the public. No content is '
                            .'published without prior review.',
                        ],
                        'Suspension' => [
                            'An account may be suspended for breach of these terms, a substantiated report, '
                            .'or abusive automated activity.',
                        ],
                        'Limitation of liability' => [
                            'Place information (opening hours, addresses, prices) is indicative and may change. '
                            .'GoodTripLove is not responsible for the content of third-party videos, which remains '
                            .'the responsibility of their authors and of the platforms hosting them.',
                        ],
                    ],
                ],
            ],

            'privacy' => [
                'fr' => [
                    'title' => 'Politique de confidentialité',
                    'body' => [
                        'Responsable de traitement' => [
                            'Le responsable de traitement est '.$placeholder('raison sociale').'. '
                            .'Contact : '.$placeholder('adresse e-mail du responsable / DPO').'.',
                        ],
                        'Données collectées' => [
                            'Compte : nom, adresse e-mail, langue, rôle, dates de connexion et adresse IP de '
                            .'connexion. Fiches professionnelles : informations de l’établissement fournies par '
                            .'son responsable. Sécurité : journaux de connexion et d’événements de sécurité. '
                            .'Audience : compteurs de vues agrégés, associés à un identifiant de visite haché, '
                            .'sans adresse IP en clair.',
                        ],
                        'Finalités et bases légales' => [
                            'Exécution du service (contrat), sécurité du service et prévention des abus '
                            .'(intérêt légitime), mesure d’audience et lecteurs tiers (consentement), '
                            .'obligations légales de conservation.',
                        ],
                        'Durées de conservation' => [
                            'Journaux de sécurité : '.config('security.logs.retention_days').' jours. '
                            .'Historique de consultation : '.config('core_operations.history_retention_days').' jours. '
                            .'Preuves de consentement et d’acceptation : '.$placeholder('durée à valider').'. '
                            .'Comptes : jusqu’à leur suppression par l’utilisateur.',
                        ],
                        'Vos droits' => [
                            'Accès, rectification, effacement, limitation, opposition et portabilité. '
                            .'La suppression du compte et l’export des données sont accessibles depuis l’espace '
                            .'utilisateur ou sur demande à l’adresse ci-dessus. Vous pouvez introduire une '
                            .'réclamation auprès de l’autorité de contrôle compétente.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Privacy policy',
                    'body' => [
                        'Controller' => [
                            'The data controller is '.$placeholder('company name').'. '
                            .'Contact: '.$placeholder('controller / DPO email address').'.',
                        ],
                        'Data collected' => [
                            'Account: name, email address, language, role, login dates and login IP address. '
                            .'Business listings: the details supplied by the business. Security: login and '
                            .'security event logs. Audience: aggregated view counters tied to a hashed visit '
                            .'identifier, with no IP address stored in clear.',
                        ],
                        'Purposes and legal bases' => [
                            'Providing the service (contract), securing it and preventing abuse (legitimate '
                            .'interest), audience measurement and third-party players (consent), and statutory '
                            .'retention obligations.',
                        ],
                        'Retention' => [
                            'Security logs: '.config('security.logs.retention_days').' days. '
                            .'Viewing history: '.config('core_operations.history_retention_days').' days. '
                            .'Consent and acceptance evidence: '.$placeholder('period to be validated').'. '
                            .'Accounts: until deleted by the user.',
                        ],
                        'Your rights' => [
                            'Access, rectification, erasure, restriction, objection and portability. '
                            .'Account deletion and data export are available from the user area or on request '
                            .'at the address above. You may lodge a complaint with the competent supervisory '
                            .'authority.',
                        ],
                    ],
                ],
            ],

            'cookies' => [
                'fr' => [
                    'title' => 'Politique cookies et traceurs',
                    'body' => [
                        'Principe' => [
                            'Aucun lecteur vidéo tiers et aucune mesure d’audience n’est chargé avant votre choix. '
                            .'Les pages affichent une miniature ; le lecteur YouTube n’est créé qu’après votre '
                            .'accord explicite.',
                        ],
                        'Catégories' => [
                            'Nécessaires : session, sécurité, préférence de langue. Ces cookies ne peuvent pas '
                            .'être désactivés car le site ne fonctionnerait pas sans eux.',
                            'Lecteurs vidéo tiers : cookies déposés par YouTube lors de la lecture d’une vidéo.',
                            'Mesure d’audience interne : compteurs de consultation, conservés de façon agrégée.',
                        ],
                        'Votre choix' => [
                            'Accepter, refuser ou personnaliser sont accessibles de la même manière. '
                            .'Le refus n’empêche pas la navigation. Votre choix est conservé avec la version de '
                            .'la présente politique et peut être modifié à tout moment via le lien '
                            .'« Gérer mes cookies » présent en pied de page.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Cookie policy',
                    'body' => [
                        'Principle' => [
                            'No third-party video player and no audience measurement is loaded before your choice. '
                            .'Pages show a thumbnail; the YouTube player is only created after your explicit '
                            .'agreement.',
                        ],
                        'Categories' => [
                            'Strictly necessary: session, security, language preference. These cannot be disabled '
                            .'because the site would not work without them.',
                            'Third-party video players: cookies set by YouTube when a video is played.',
                            'Internal audience measurement: view counters, kept in aggregate form.',
                        ],
                        'Your choice' => [
                            'Accept, reject and customise are equally reachable. Refusing does not block browsing. '
                            .'Your choice is stored together with the version of this policy and can be changed at '
                            .'any time from the “Manage cookies” link in the footer.',
                        ],
                    ],
                ],
            ],

            'third-party-content' => [
                'fr' => [
                    'title' => 'Vidéos et contenus tiers',
                    'body' => [
                        'Comment les vidéos sont affichées' => [
                            'GoodTripLove identifie des vidéos publiques via les API officielles des plateformes '
                            .'et les affiche à l’aide de leurs mécanismes d’intégration officiels. '
                            .'Aucune vidéo n’est téléchargée, copiée ou réhébergée.',
                            'Le titre, la chaîne et les statistiques affichés proviennent de la plateforme '
                            .'d’origine. Un lien permet toujours d’accéder à la vidéo sur cette plateforme.',
                        ],
                        'Une vidéo librement accessible n’est pas une vidéo libre de droits' => [
                            'Le fait qu’une vidéo soit visible gratuitement ne signifie pas qu’elle soit libre de '
                            .'droits. Les droits restent ceux de leurs auteurs.',
                        ],
                        'Créateurs' => [
                            'Tout créateur peut demander la correction des informations associées à ses vidéos ou '
                            .'leur retrait de la plateforme via le formulaire de signalement.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Videos and third-party content',
                    'body' => [
                        'How videos are displayed' => [
                            'GoodTripLove identifies public videos through the platforms’ official APIs and '
                            .'displays them using their official embedding mechanisms. No video is downloaded, '
                            .'copied or re-hosted.',
                            'The title, channel and statistics shown come from the original platform. A link to '
                            .'the video on that platform is always available.',
                        ],
                        'Freely viewable is not copyright free' => [
                            'A video being free to watch does not make it free of rights. Rights remain with '
                            .'their authors.',
                        ],
                        'Creators' => [
                            'Any creator may request correction of the information associated with their videos, '
                            .'or their removal from the platform, through the reporting form.',
                        ],
                    ],
                ],
            ],

            'intellectual-property' => [
                'fr' => [
                    'title' => 'Propriété intellectuelle',
                    'body' => [
                        'Éléments du site' => [
                            'La marque GoodTripLove, le logo, la charte graphique, les textes éditoriaux et la '
                            .'structure du site sont protégés. Toute reproduction non autorisée est interdite.',
                        ],
                        'Contenus des tiers' => [
                            'Les vidéos, miniatures, noms de chaînes et marques cités appartiennent à leurs '
                            .'titulaires respectifs et sont utilisés dans le cadre des mécanismes d’intégration '
                            .'autorisés par les plateformes d’origine.',
                        ],
                        'Contenus fournis par les établissements' => [
                            'En transmettant des textes ou des images, un établissement garantit détenir les '
                            .'droits nécessaires et autorise leur affichage sur GoodTripLove.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Intellectual property',
                    'body' => [
                        'Site elements' => [
                            'The GoodTripLove name, logo, visual identity, editorial texts and site structure are '
                            .'protected. Unauthorised reproduction is prohibited.',
                        ],
                        'Third-party content' => [
                            'Videos, thumbnails, channel names and trademarks belong to their respective owners '
                            .'and are used within the embedding mechanisms allowed by the original platforms.',
                        ],
                        'Business-supplied content' => [
                            'By supplying text or images, a business warrants that it holds the necessary rights '
                            .'and authorises their display on GoodTripLove.',
                        ],
                    ],
                ],
            ],

            'content-reporting' => [
                'fr' => [
                    'title' => 'Signalement et retrait de contenu',
                    'body' => [
                        'Comment signaler' => [
                            'Un bouton « Signaler » est présent sur chaque vidéo et chaque fiche. '
                            .'Un formulaire détaillé est également accessible depuis le pied de page. '
                            .'Chaque signalement reçoit un numéro de référence.',
                        ],
                        'Traitement' => [
                            'Les signalements suivent un cycle documenté : réception, tri, examen, décision, '
                            .'notification. Chaque décision est enregistrée avec son motif, la date et le '
                            .'modérateur concerné, dans un journal non modifiable.',
                        ],
                        'Décisions possibles' => [
                            'Aucune action, masquage, désindexation, retrait, limitation de visibilité, '
                            .'suspension de compte, ou rétablissement après recours.',
                        ],
                        'Recours' => [
                            'L’auteur du signalement et la personne concernée peuvent demander un réexamen de la '
                            .'décision à l’adresse '.config('goodtriplove.legal.contact_email').'.',
                        ],
                    ],
                ],
                'en' => [
                    'title' => 'Content reporting and removal',
                    'body' => [
                        'How to report' => [
                            'A “Report” button is available on every video and every listing. A detailed form is '
                            .'also reachable from the footer. Every report receives a reference number.',
                        ],
                        'Handling' => [
                            'Reports follow a documented cycle: received, triage, review, decision, notification. '
                            .'Every decision is recorded with its reason, the date and the moderator involved, '
                            .'in a log that cannot be altered.',
                        ],
                        'Possible decisions' => [
                            'No action, hide, de-index, remove, limit visibility, suspend the account, or restore '
                            .'after appeal.',
                        ],
                        'Appeal' => [
                            'Both the reporter and the person concerned may request a review of the decision at '
                            .config('goodtriplove.legal.contact_email').'.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
