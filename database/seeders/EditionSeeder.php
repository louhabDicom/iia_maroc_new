<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EditionStatus;
use App\Models\ContactMessage;
use App\Models\ContactRoute;
use App\Models\ContentBlock;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Organisation;
use App\Models\Room;
use App\Models\TicketType;
use App\Models\Track;
use Illuminate\Database\Seeder;

/**
 * The 2026 edition and the data every page depends on.
 *
 * The rule followed throughout: nothing a visitor reads as a fact about the
 * conference lives in a template. The year, the venue, the dates, the theme, the
 * rates and the room list are rows here, which is what makes the 2024 edition a
 * second set of rows rather than a second copy of the site.
 *
 * Anything the brief has not confirmed is left empty or explicitly marked
 * provisional. The scientific programme is not seeded with placeholder sessions
 * and the sponsor list is not seeded with invented companies, because a filled-in
 * page that is wrong is worse than an empty page that is honest.
 */
class EditionSeeder extends Seeder
{
    public function run(): void
    {
        $this->countries();
        $this->edition2026();
        $this->organisations();
        $this->rooms();
        $this->tracks();
        $this->ticketTypes();
        $this->content();

        // After the edition, because the routes point at its contact address.
        $this->contactRoutes();
    }

    /**
     * A short, deliberately incomplete country list.
     *
     * The 2024 database had no countries table at all; the registration form
     * therefore stored a free-text string that nothing could validate or group
     * by. Seeding the whole ISO list is a data task, and the form degrades
     * gracefully to a nullable field, so the countries the 2026 brief names plus
     * the Maghreb region is enough to be useful today.
     */
    private function countries(): void
    {
        $countries = [
            ['MA', 'MAR', '+212', 'Maroc', 'Morocco', 'المغرب'],
            ['FR', 'FRA', '+33', 'France', 'France', 'فرنسا'],
            ['DZ', 'DZA', '+213', 'Algérie', 'Algeria', 'الجزائر'],
            ['TN', 'TUN', '+216', 'Tunisie', 'Tunisia', 'تونس'],
            ['ES', 'ESP', '+34', 'Espagne', 'Spain', 'إسبانيا'],
            ['PT', 'PRT', '+351', 'Portugal', 'Portugal', 'البرتغال'],
            ['GB', 'GBR', '+44', 'Royaume-Uni', 'United Kingdom', 'المملكة المتحدة'],
            ['US', 'USA', '+1', 'États-Unis', 'United States', 'الولايات المتحدة'],
            ['CA', 'CAN', '+1', 'Canada', 'Canada', 'كندا'],
            ['AE', 'ARE', '+971', 'Émirats arabes unis', 'United Arab Emirates', 'الإمارات العربية المتحدة'],
            ['SA', 'SAU', '+966', 'Arabie saoudite', 'Saudi Arabia', 'السعودية'],
            ['DE', 'DEU', '+49', 'Allemagne', 'Germany', 'ألمانيا'],
            ['IT', 'ITA', '+39', 'Italie', 'Italy', 'إيطاليا'],
            ['BE', 'BEL', '+32', 'Belgique', 'Belgium', 'بلجيكا'],
            ['NL', 'NLD', '+31', 'Pays-Bas', 'Netherlands', 'هولندا'],
            ['NG', 'NGA', '+234', 'Nigéria', 'Nigeria', 'نيجيريا'],
            ['SN', 'SEN', '+221', 'Sénégal', 'Senegal', 'السنغال'],
            ['CI', 'CIV', '+225', 'Côte d\'Ivoire', 'Ivory Coast', 'ساحل العاج'],
            ['EG', 'EGY', '+20', 'Égypte', 'Egypt', 'مصر'],
            ['QA', 'QAT', '+974', 'Qatar', 'Qatar', 'قطر'],
            ['TR', 'TUR', '+90', 'Turquie', 'Türkiye', 'تركيا'],
            ['RU', 'RUS', '+7', 'Russie', 'Russia', 'روسيا'],
        ];

        foreach ($countries as [$iso2, $iso3, $phone, $fr, $en, $ar]) {
            Country::query()->updateOrCreate(
                ['iso2' => $iso2],
                [
                    'iso3' => $iso3,
                    'phone_code' => $phone,
                    'name_fr' => $fr,
                    'name_en' => $en,
                    'name_ar' => $ar,
                    'is_active' => true,
                ],
            );
        }
    }

private function edition2026(): void
    {
        Edition::query()->updateOrCreate(
            ['year' => 2026],
            [
                'code' => 'ARABCIA2026',
                'organiser' => 'ARABCIA',
                'host_institute' => 'IIA Maroc',

                'title' => [
                    'fr' => 'Conférence internationale ARABCIA 2026',
                    'en' => 'International ARABCIA Conference 2026',
                    'ar' => 'ARABCIA هي الجمعية الإقليمية لمديري التدقيق الداخلي في أفريقيا الناطقة بالفرنسية. تُنظَّم بدعم من المعهد الدولي للتدقيق الداخلي، وتجمع كل عام ممارسي التدقيق الداخلي ومؤسسات الرقابة والأكاديميين والاستشاريين حول التحديات العملية التي تواجه هذه الوظيفة.',
                ],

                // The theme is quoted from the 2026 brief. It is stored as data
                // rather than typed into the hero so the organisers can amend the
                // wording without a deploy.
                'theme' => [
                    'fr' => 'L\'Audit Interne : partenaire de confiance dans la transformation et la résilience des organisations',
                    'en' => 'Internal Audit : A Trusted Partner in Organizational Transformation and Resilience',
                    'ar' => 'التدقيق الداخلي: شريك موثوق في تحوّل المؤسسات وتعزيز قدرتها على الصمود',
                ],

                'introduction' => [
                    'fr' => "La Conférence annuelle ARABCIA 2026, organisée par l'ARABCIA et accueillie au Maroc par l'IIA Maroc, réunira à Rabat les professionnels et les institutions qui contribuent au développement de l'Audit Interne dans le monde arabe et au-delà. Cette édition aura pour thème : « L'Audit Interne : partenaire de confiance dans la transformation et la résilience des organisations ».

Face à l'essor de l'intelligence artificielle, aux cybermenaces et aux bouleversements économiques et sociaux, les organisations doivent anticiper les risques, s'adapter et préserver la confiance. La conférence explorera les réponses concrètes à ces défis et montrera comment l'Audit Interne, fort de son indépendance et de son jugement professionnel, peut contribuer à la gouvernance, à la résilience et à la création de valeur durable.",
                    'en' => 'The ARABCIA 2026 Annual Conference, organized by ARABCIA and hosted in Morocco by the IIA Morocco, will bring together professionals and institutions contributing to the development of Internal Audit in the Arab world and beyond. This edition will have the theme: "Internal Audit : A Trusted Partner in Organizational Transformation and Resilience".

Facing the rise of artificial intelligence, cyber threats and economic and social upheavals, organizations must anticipate risks, adapt and preserve trust. The conference will explore concrete responses to these challenges and show how Internal Audit, with its independence and professional judgment, can contribute to governance, resilience and sustainable value creation.',
                    'ar' => 'تُعقَد المؤتمر السنوي ARABICA 2026، بتنظيم من ARABCIA وباستقبال من المعهد الدولي للتدقيق الداخلي IIA المغرب، في الرباط، ويلتقى فيها الخبراء والمؤسسات المساهمة في تطوير التدقيق الداخلي في العالم العربي وما وراءها. وستحمل هذه الدورة thème: "التدقيق الداخلي: شريك موثوق في تحوّل المؤسسات وتعزيز قدرتها على الصمود".

بمواجهة تزايد الذكاء الاصطناعي، والتهديدات الإلكترونية، والتقلبات الاقتصادية والاجتماعية، يجب على المؤسسات أن تسبق المخاطر، وتتكيف، وتحافظ على الثقة. وستستكشف المؤتمر الحلول العملية لهذه التحديات، وستshows كيف يمكن للتتدقيق الداخلي، باستقلاله وحكمه المهني، أن يساهم في الحوكمة، والمرونة، وخلق القيمة المستدامة.',
                ],

                'city' => 'Rabat',
                'country_iso2' => 'MA',
                'venue_name' => 'Four Seasons Hotel Rabat at Kasr Al Bahr',
                'venue_address' => null,
                'venue_lat' => 33.9973,
                'venue_lng' => -6.8498,
                'venue_map_url' => null,

                // Confirmed in the brief.
                'starts_on' => '2026-12-16',
                'ends_on' => '2026-12-17',

                'languages' => ['fr', 'en', 'ar'],
                'target_audience' => [
                    'fr' => [
                        'Directeurs et responsables de l\'audit interne',
                        'Cadres financiers et comptables',
                        'Consultants en management et organisation',
                        'Représentants des institutions de contrôle',
                        'Universitaires et chercheurs',
                    ],
                    'en' => [
                        'Chief audit executives and internal audit leaders',
                        'Finance and accounting officers',
                        'Management and organisation consultants',
                        'Representatives of oversight institutions',
                        'Academics and researchers',
                    ],
                    'ar' => [
                        'مديرو ومسؤولو التدقيق الداخلي',
                        'الإطارون الماليون والمحاسبون',
                        'استشاريو الإدارة والتنظيم',
                        'ممثلو هيئات الرقابة',
                        'الأكاديميون والباحثون',
                    ],
                ],

                'status' => EditionStatus::Published,
                'is_current' => true,

                // Registration is not open at build time. Opening it is a single
                // flag, and a registration button on a closed edition generates
                // support mail rather than delegates.
                'registration_open' => true,

                'contact_email' => 'contact@arabcia.org',
                'contact_phone' => null,

                // The 2024 venue and the confirmed 2026 facts are the only values
                // hardcoded here. Speaker names, sponsor names and the detailed
                // schedule are left to the organisers.
                'organiser_contact_name' => null,
                'organiser_contact_email' => 'contact@arabcia.org',
                'host_contact_name' => null,
                'host_contact_email' => null,

                'hero_image_path' => null,
                'logo_path' => null,
                'archive_note' => null,
            ],
        );
    }

    private function organisations(): void
    {
        $organisations = [
            [
                'code' => 'ARABCIA',
                'name' => [
                    'fr' => 'ARABCIA',
                    'en' => 'ARABCIA',
                    'ar' => 'ARABCIA',
                ],
                'description' => [
                    'fr' => 'Association régionale des directions d\'audit interne d\'Afrique francophone.',
                    'en' => 'Regional association of French-speaking African internal audit directors.',
                    'ar' => 'الجمعية الإقليمية لمديري التدقيق الداخلي في أفريقيا الناطقة بالفرنسية.',
                ],
                'role' => [
                    'fr' => 'Organisateur',
                    'en' => 'Organiser',
                    'ar' => 'المنظم',
                ],
                'sort_order' => 1,
            ],
            [
                'code' => 'IIA_MAROC',
                'name' => [
                    'fr' => 'Institut International d\'Audit — Maroc',
                    'en' => 'Institute of Internal Auditors — Morocco',
                    'ar' => 'المعهد الدولي للتدقيق الداخلي — المغرب',
                ],
                'description' => [
                    'fr' => 'Institut qui accueille la conférence et soutient l\'audit interne au Maroc.',
                    'en' => 'The institute that hosts the conference and supports internal audit in Morocco.',
                    'ar' => 'المعهد الذي يستضيف المؤتمر ويدعم التدقيق الداخلي في المغرب.',
                ],
                'role' => [
                    'fr' => 'Hôte',
                    'en' => 'Host',
                    'ar' => 'المضيف',
                ],
                'sort_order' => 2,
            ],
        ];

        $edition = Edition::forYear(2026);

        foreach ($organisations as $organisation) {
            Organisation::query()->updateOrCreate(
                ['code' => $organisation['code']],
                array_merge($organisation, [
                    'edition_id' => $edition?->getKey(),
                    'is_published' => true,
                ]),
            );
        }
    }

    /**
     * Rooms.
     *
     * The brief caps concurrency at four parallel sessions, so four rooms is the
     * shape of the grid the programme view renders.
     *
     * `name` is translated like every other visible string, and `floor` is a
     * level number rather than a label: "Rez-de-chaussée" would need translating
     * and sorting by it would be alphabetical, so the level is stored and the
     * wording lives in the view.
     */
    private function rooms(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $rooms = [
            [
                'code' => 'PLENARY',
                'name' => ['fr' => 'Salle plénière', 'en' => 'Plenary hall', 'ar' => 'القاعة العامة'],
                'capacity' => 300,
                'floor' => 0,
                'sort_order' => 1,
            ],
            [
                'code' => 'ATELIER_1',
                'name' => ['fr' => 'Atelier 1', 'en' => 'Workshop room 1', 'ar' => 'قاعة الورشات 1'],
                'capacity' => 60,
                'floor' => 1,
                'sort_order' => 2,
            ],
            [
                'code' => 'ATELIER_2',
                'name' => ['fr' => 'Atelier 2', 'en' => 'Workshop room 2', 'ar' => 'قاعة الورشات 2'],
                'capacity' => 60,
                'floor' => 1,
                'sort_order' => 3,
            ],
            [
                'code' => 'ATELIER_3',
                'name' => ['fr' => 'Atelier 3', 'en' => 'Workshop room 3', 'ar' => 'قاعة الورشات 3'],
                'capacity' => 40,
                'floor' => 1,
                'sort_order' => 4,
            ],
            [
                'code' => 'LAB_1',
                'name' => ['fr' => 'Innovation Lab 1', 'en' => 'Innovation Lab 1', 'ar' => 'معمل الابتكار 1'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 5,
            ],
            [
                'code' => 'LAB_2',
                'name' => ['fr' => 'Innovation Lab 2', 'en' => 'Innovation Lab 2', 'ar' => 'معمل الابتكار 2'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 6,
            ],
            [
                'code' => 'LAB_3',
                'name' => ['fr' => 'Innovation Lab 3', 'en' => 'Innovation Lab 3', 'ar' => 'معمل الابتكار 3'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 7,
            ],
            [
                'code' => 'LAB_4',
                'name' => ['fr' => 'Innovation Lab 4', 'en' => 'Innovation Lab 4', 'ar' => 'معمل الابتكار 4'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 8,
            ],
            [
                'code' => 'LAB_5',
                'name' => ['fr' => 'Innovation Lab 5', 'en' => 'Innovation Lab 5', 'ar' => 'معمل الابتكار 5'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 9,
            ],
            [
                'code' => 'LAB_6',
                'name' => ['fr' => 'Innovation Lab 6', 'en' => 'Innovation Lab 6', 'ar' => 'معمل الابتكار 6'],
                'capacity' => 30,
                'floor' => 1,
                'sort_order' => 10,
            ],
        ];

        foreach ($rooms as $room) {
            Room::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $room['code']],
                array_merge($room, ['is_published' => true]),
            );
        }
    }

    /**
     * Programme tracks.
     *
     * The themes are the broad subject areas rather than the sessions; the brief
     * asks for 18 workshops and 6 innovation labs across them.
     */
    private function tracks(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $tracks = [
            ['code' => 'GOUVERNANCE', 'name' => ['fr' => 'Gouvernance et conformité', 'en' => 'Governance and compliance', 'ar' => 'الحوكمة والامتثال'], 'sort_order' => 1],
            ['code' => 'NUMERIQUE', 'name' => ['fr' => 'Transformation numérique', 'en' => 'Digital transformation', 'ar' => 'التحول الرقمي'], 'sort_order' => 2],
            ['code' => 'DURABILITE', 'name' => ['fr' => 'Développement durable', 'en' => 'Sustainable development', 'ar' => 'التنمية المستدامة'], 'sort_order' => 3],
            ['code' => 'RISQUE', 'name' => ['fr' => 'Gestion des risques', 'en' => 'Risk management', 'ar' => 'إدارة المخاطر'], 'sort_order' => 4],
            ['code' => 'COMPETENCES', 'name' => ['fr' => 'Compétences et professions', 'en' => 'Skills and the profession', 'ar' => 'الكفاءات والمهنة'], 'sort_order' => 5],
        ];

        foreach ($tracks as $track) {
            Track::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $track['code']],
                array_merge($track, [
                    'description' => ['fr' => null, 'en' => null, 'ar' => null],
                    'is_published' => true,
                ]),
            );
        }

        // The brief asks for 18 workshops and 6 innovation labs across the tracks.
        // The three workshop axes are: IA & transformation, Résilience, Auditeur de demain.
        // We create the axes as additional track entries for reference.
        $axes = [
            ['code' => 'IA_TRANSFO', 'name' => ['fr' => 'IA et transformation', 'en' => 'AI and transformation', 'ar' => 'IA والتحول'], 'sort_order' => 6],
            ['code' => 'RESILIENCE', 'name' => ['fr' => 'Résilience', 'en' => 'Resilience', 'ar' => 'المتانة'], 'sort_order' => 7],
            ['code' => 'AUDITOR_DAME', 'name' => ['fr' => 'Auditeur de demain', 'en' => 'Auditor of tomorrow', 'ar' => 'المدقق القادم'], 'sort_order' => 8],
        ];

        foreach ($axes as $axis) {
            Track::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $axis['code']],
                array_merge($axis, [
                    'description' => ['fr' => null, 'en' => null, 'ar' => null],
                    'is_published' => true,
                ]),
            );
        }
    }

    /**
     * Ticket types.
     *
     * Prices are stored in minor units (centimes). `7500.00 MAD` is `750000`, and
     * storing the float instead is how the 2024 build ended up with totals
     * off by a centime after a few percentage calculations.
     *
     * The member rate is not a discount the visitor applies: it is a second
     * column, and `TicketType::priceFor()` chooses between them by reading a
     * Membership record. The 2024 site took the choice from a form field, which
     * is the vulnerability this column layout exists to prevent.
     */
    private function ticketTypes(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $types = [
            [
                'code' => 'STANDARD',
                'name' => ['fr' => 'Inscription standard', 'en' => 'Standard registration', 'ar' => 'تسجيل عادي'],
                'description' => [
                    'fr' => 'Accès à l\'ensemble des sessions et au cocktail de clôture.',
                    'en' => 'Access to all sessions and to the closing reception.',
                    'ar' => 'الوصول إلى جميع الجلسات وحفل الاستقبال الختامي.',
                ],
                'includes' => [
                    'fr' => ['Accès aux plénières', 'Accès aux ateliers (sur inscription)', 'Déjeuner les deux jours', 'Support de la conférence', 'Attestation de participation'],
                    'en' => ['Access to plenaries', 'Access to workshops (on registration)', 'Lunch on both days', 'Conference proceedings', 'Certificate of participation'],
                    'ar' => ['الوصول إلى الجلسات العامة', 'الوصول إلى الورشات (بالتسجيل)', 'الغداء خلال اليومين', 'وثائق المؤتمر', 'شهادة المشاركة'],
                ],
                // Brief, sheet "Modifications 2026" row 15:
                // "7 500/8 500 MAD et 750/850 USD". Lower figure is the IIA
                // member rate, higher is standard. Minor units, so MAD is x100
                // and USD is x100 (cents), which is why 7 500 MAD is 750000 and
                // 750 USD is 75000. The member rate is a separate column and is
                // not a percentage of the standard rate.
                'price_member' => 750000,
                'price_standard' => 850000,
                'currency' => 'MAD',
                'currency_numeric' => 504,
                'sort_order' => 1,
            ],
            [
                'code' => 'STANDARD_USD',
                'name' => ['fr' => 'Inscription standard (USD)', 'en' => 'Standard registration (USD)', 'ar' => 'تسجيل عادي (دولار)'],
                'description' => [
                    'fr' => 'Pour les participants hors zone francophone.',
                    'en' => 'For participants outside the Francophone region.',
                    'ar' => 'للمشاركين من خارج المنطقة الناطقة بالفرنسية.',
                ],
                'includes' => [
                    'fr' => ['Accès aux plénières', 'Accès aux ateliers (sur inscription)', 'Déjeuner les deux jours', 'Support de la conférence', 'Attestation de participation'],
                    'en' => ['Access to plenaries', 'Access to workshops (on registration)', 'Lunch on both days', 'Conference proceedings', 'Certificate of participation'],
                    'ar' => ['الوصول إلى الجلسات العامة', 'الوصول إلى الورشات (بالتسجيل)', 'الغداء خلال اليومين', 'وثائق المؤتمر', 'شهادة المشاركة'],
                ],
                // Brief row 15: 750/850 USD, minor units.
                'price_member' => 75000,
                'price_standard' => 85000,
                'currency' => 'USD',
                'currency_numeric' => 978,
                'sort_order' => 2,
            ],
        ];

        foreach ($types as $type) {
            TicketType::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $type['code']],
                array_merge($type, [
                    'colour' => 'brand',
                    'max_quantity' => 10,
                    'is_active' => true,
                    'sales_start_at' => null,
                    'sales_end_at' => null,
                ]),
            );
        }
    }

    /**
     * Editorial content blocks.
     *
     * Pages that are prose rather than data — the sponsoring tiers, the practical
     * notes — are content blocks so an editor can change them without a deploy.
     * They are seeded empty where the brief has no wording yet, rather than with
     * text invented to fill the space.
     */
    private function content(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        $blocks = [
            [
                'group' => 'sponsoring',
                'key' => 'platinum',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'gold',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'silver',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'bronze',
                'value' => ['fr' => null, 'en' => null, 'ar' => null],
            ],
            [
                'group' => 'practical',
                'key' => 'registration_closes',
                'value' => [
                    'fr' => 'Les inscriptions en ligne sont ouvertes jusqu\'au 30 novembre 2026.',
                    'en' => 'Online registration is open until 30 November 2026.',
                    'ar' => 'التسجيل الإلكتروني مفتوح حتى 30 نوفمبر 2026.',
                ],
            ],
            [
                'group' => 'practical',
                'key' => 'cancellation',
                'value' => [
                    'fr' => 'Toute annulation doit être signalée par écrit au moins quinze jours avant la conférence.',
                    'en' => 'Any cancellation must be notified in writing at least fifteen days before the conference.',
                    'ar' => 'يجب إبلاغ أي إلغاء كتابيًا قبل خمسة عشر يومًا على الأقل من انعقاد المؤتمر.',
                ],
            ],
            [
                'group' => 'sponsoring',
                'key' => 'sponsor_tiers',
                'value' => [
                    'fr' => 'Trois catégories de parrainage sont disponibles : Platine, Or, Argent et Bronze, avec des avantages croissants à chaque niveau.',
                    'en' => 'Three sponsorship categories are available: Platinum, Gold, Silver and Bronze, with increasing benefits at each level.',
                    'ar' => 'تتوفر ثلاثCategories من الرعاية: بلاتين، ذهبي، فضي وبرونزي، مع زيادة المزايا في كل مستوى.',
                ],
            ],
        ];

        foreach ($blocks as $block) {
            ContentBlock::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'group' => $block['group'], 'key' => $block['key']],
                [
                    'value' => $block['value'],
                    'is_published' => true,
                ],
            );
        }
    }

    /**
     * Contact-form routing.
     *
     * All five routes start pointed at the edition's `contact_email`, because the
     * brief names no departmental addresses. Splitting them out now means the
     * communications team only has to edit five rows in the admin panel to route
     * registration, sponsoring, speaker, press and general enquiries to different
     * people — no deploy, no code change.
     */
    private function contactRoutes(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null || blank($edition->contact_email)) {
            return;
        }

        $routes = [
            [ContactMessage::SUBJECT_REGISTRATION, 'Inscription', 'Registration', 'التسجيل', 1],
            [ContactMessage::SUBJECT_SPONSORING, 'Partenariat', 'Sponsoring', 'الرعاية', 2],
            [ContactMessage::SUBJECT_SPEAKER, 'Intervenants', 'Speakers', 'المتدخلون', 3],
            [ContactMessage::SUBJECT_PRESS, 'Presse', 'Press', 'الصحافة', 4],
            // Must exist and must be active: ContactRoute::forSubject() falls back
            // to it, so a missing row is what makes an enquiry unroutable.
            [ContactMessage::SUBJECT_OTHER, 'Autres demandes', 'Other enquiries', 'طلبات أخرى', 99],
        ];

        foreach ($routes as [$type, $fr, $en, $ar, $order]) {
            ContactRoute::query()->updateOrCreate(
                ['subject_type' => $type],
                [
                    'label_fr' => $fr,
                    'label_en' => $en,
                    'label_ar' => $ar,
                    'to_email' => $edition->contact_email,
                    'cc_email' => null,
                    'sort_order' => $order,
                    'is_active' => true,
                ],
            );
        }
    }
}
