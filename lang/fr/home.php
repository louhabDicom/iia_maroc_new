<?php

declare(strict_types=1);

return [
    // ---------------------------------------------------------------------
    // Landing page
    // ---------------------------------------------------------------------
    //
    // Copy for the redesigned home page, band by band, in the order the bands
    // appear. It lives in one group rather than being spread across the
    // pricing / programme / speakers files because a band can quote from
    // several of those, and a phrase that only ever appears on the landing page
    // does not belong in a file that also serves the standalone pages.
    //
    'landing' => [

        'hero' => [
            'kicker' => 'CONFÉRENCE INTERNATIONALE',
            'logos_alt' => 'ARABCIA 2026 et ses organisateurs',
            'cta_programme' => 'Programme',
            'cta_sponsor' => 'Devenir sponsor',
        ],

        'why' => [
            'eyebrow' => 'Pourquoi cette conférence ?',
            'title' => 'Un rendez-vous pour anticiper',
            'lede' => 'La conférence annuelle ARABCIA :year, organisée par l\'ARABCIA et accueillie au Maroc par l\'IIA Maroc, réunira à Rabat les professionnels et les institutions qui contribuent au développement de l\'Audit Interne dans le monde arabe et au-delà. Cette édition aura pour thème « L\'Audit Interne, partenaire de confiance dans la transformation et la résilience des organisations ».',
            'body' => 'Face à l\'essor de l\'intelligence artificielle, aux cybersécurités et aux bouleversements économiques et sociaux, les organisations sont amenées à repenser leurs gouvernances. L\'audit interne, dans ce contexte, s\'adapte et devient un levier de performance pour les entreprises. La conférence explorera les réponses concrètes à ces défis et montrera comment l\'Audit Interne, en tant que moteur d\'innovation, constitue un outil puissant au service de la gouvernance, de la résilience et de la performance.',
            'image_alt' => 'Délégués en pleine conversation sur le hall d\'exposition.',
            'inset_alt' => 'Plénière ARABCIA, session en cours.',
            'inset_title' => 'ARABCIA :year',
            'inset_subtitle' => 'L\'Audit interne, partenaire de confiance',
        ],

        'inscription' => [
            'title' => 'Inscription',
            'ttc' => 'Tarif TTC',
            'fx' => 'Tarif en devises',
            'members' => 'Adhérents',
            'non_members' => 'Non adhérents',
            'reserve' => 'Réservez votre participation en :currency',
        ],

        'programme' => [
            'title' => 'Programme de la conférence',
            'download' => 'Télécharger',
            'more' => 'Voir plus',
            'days_label' => 'Journées du programme',
            'day' => 'Jour :number',
            'expand' => 'Afficher le détail de la session',
            'collapse' => 'Replier le détail de la session',
        ],

        'speakers' => [
            'eyebrow' => 'Intervenants',
            'keynote' => 'Keynote',
            'speaker' => 'Intervenant',
            'previous' => 'Intervenants précédents',
            'next' => 'Intervenants suivants',
        ],

    ],
    'partners_title' => 'SPONSORS',

    'video_fallback' => 'Votre navigateur ne peut pas lire cette vidéo.',

    'video_play' => 'Lancer la vidéo',

    'video_lede' => 'Ce dont traite l’édition 2026, selon les organisateurs eux-mêmes.',

    'video_title' => 'La conférence en quatre-vingt-dix secondes',

    'count_seconds' => 'Secondes',

    'count_minutes' => 'Minutes',

    'count_hours' => 'Heures',

    'count_days' => 'Jours',

    'countdown_label' => 'Temps restant avant la conférence',

    // --- Homepage counters -------------------------------------------------
    // The three figures the brief names for 2026: 300 delegates, 18 workshops
    // and 2 days. The values themselves come from the edition row and the
    // published programme (see HomeController), never from here — these are
    // only the labels.
    'stats' => [
        'attendees' => 'Participants',
        'workshops' => 'Ateliers',
        'days' => 'Jours',
    ],
    'about_subtitle' => 'Une conférence pour',
    'about_title' => 'À propos',
    'audience_title' => 'Public cible',
    'contact_cta' => 'Nous contacter',
    'editions_alt' => 'Photograph from a previous ARABCIA edition, :number',
    'editions_caption' => 'ARABCIA — édition précédente (:number)',
    'editions_lede' => 'Des générations d’auditeurs internes se sont réunies à Rabat, année après année. Voici quelques moments de ces éditions précédentes.',
    'editions_title' => 'Les éditions précédentes',
    'organisers_eyebrow' => 'Organisateurs',
    'organisers_lede' => 'ARABCIA convoque la conférence ; IIA Maroc l’accueille au Royaume du Maroc.',
    'organisers_title' => 'Qui porte ARABCIA 2026',
    'format_title' => 'Format',
    'hero_cta' => 'S\'inscrire',
    'hero_secondary' => 'Voir le programme',
    'join_us' => 'Rejoignez-nous !',
    'programme_title' => 'Aperçu du programme',
    'registration_title' => 'Inscription',
    'speakers_more' => 'Voir tous les intervenants',
    'speakers_title' => 'Des experts de renommée internationale',
    'sponsors_title' => 'Partenaires',
    'stats.attendees' => 'Participants attendus',
    'stats.days' => 'Journées',
    'stats.labs' => 'Laboratoires d\'innovation',
    'stats.workshops' => 'Ateliers',
    'stats_title' => 'En chiffres',
    'theme_title' => 'Thème',
    'venue_title' => 'Lieu et dates',
];
