<?php

return [
    'title' => 'Présentation',
    'languages_value' => 'Français / Anglais (traduction simultanée)',
    'download_pdf' => 'Télécharger la fiche technique (PDF)',

    'intro' => [
        'title' => 'Présentation de la conférence ARABCIA :year',
        'lede' => 'Une conférence internationale dédiée aux enjeux actuels de l’audit interne, de la gouvernance, des risques et de la transformation des organisations.',
        'alt' => 'Un intervenant à la tribune devant une salle comble',

        // The theme, in three paragraphs. The first states the theme, the
        // second explains why it matters now, the third says what the two days
        // are for. Read in order they are the page's argument.
        'theme_title' => 'Thème de la conférence',
        'paragraphs' => [
            'La Conférence annuelle ARABCIA :year place au cœur de ses travaux un thème qui traverse toutes les organisations : « L’Audit Interne : partenaire de confiance dans la transformation et la résilience des organisations ».',
            'Dans un environnement marqué par l’accélération des transformations, la volatilité des risques et la pression sur la gouvernance, l’audit interne n’est plus un simple contrôle : il devient un acteur de confiance, capable d’éclairer les décisions et de renforcer la capacité des organisations à s’adapter.',
            'Pendant deux jours, décideurs, auditeurs, experts et partenaires partageront leurs expériences, leurs méthodes et leurs visions pour construire ensemble des organisations plus résilientes et mieux gouvernées.',
        ],
    ],

    'highlights' => [
        'objectives_title' => 'Objectifs',
        'objectives_text' => 'Partager les bonnes pratiques et favoriser les échanges entre professionnels.',
        'scope_title' => 'Thème',
        'scope_text' => 'L’audit interne : partenaire de confiance dans la transformation et la résilience des organisations.',
        'international_title' => 'Dimension internationale',
        'international_text' => 'Une expertise et des retours d’expérience de haut niveau venus du monde entier.',
    ],

    // The conference, read as a three-step journey. Each step is one verb, so
    // the reader can follow the arc without reading the descriptions.
    'journey' => [
        'eyebrow' => 'Un parcours en trois temps',
        'title' => 'Comprendre, agir, démontrer',
        'lede' => 'Deux journées structurées autour de trois mouvements : comprendre les mutations en cours, agir avec des méthodes concrètes et démontrer la valeur créée par l’audit interne.',
        'steps' => [
            [
                'key' => 'understand',
                'icon' => 'fa-chart-line',
                'title' => 'Comprendre',
                'text' => 'Décrypter les évolutions réglementaires, les nouveaux risques et les attentes des parties prenantes.',
            ],
            [
                'key' => 'act',
                'icon' => 'fa-chart-line',
                'title' => 'Agir',
                'text' => 'Partager des méthodes, des outils et des retours d’expérience pour transformer l’audit interne.',
            ],
            [
                'key' => 'demonstrate',
                'icon' => 'fa-chart-line',
                'title' => 'Démontrer',
                'text' => 'Illustrer par des cas concrets et des résultats mesurables la valeur créée par l’audit interne.',
            ],
        ],
    ],

    'organisations' => [
        'eyebrow' => 'Les organisateurs',
        'title' => 'Deux institutions, une même mission',
        'lede' => 'ARABCIA et l’IIA Maroc œuvrent ensemble pour le développement de la profession d’audit interne au Maroc et dans la région.',
        'organiser_role' => 'Organisateur',
        'organiser_desc' => 'Le réseau des Auditeurs Internes Arabes, pour une profession plus forte et plus connectée.',
        'host_role' => 'Institut hôte',
        'host_desc' => 'L’Institut des Auditeurs Internes au Maroc, acteur de référence pour la profession.',

        // Detailed profiles, keyed by the organisation `code` so the view can
        // enrich whatever rows the edition publishes. `stats` is a list of
        // [label, value]; `cta` sits on the card's own link.
        'profiles' => [
            'ARABCIA' => [
                'stats' => [
                    ['label' => 'Créée en', 'value' => 'décembre 2022'],
                    ['label' => 'Siège', 'value' => 'Royaume d’Arabie saoudite'],
                    ['label' => 'Membres', 'value' => '13 pays arabes'],
                ],
                'cta' => 'Découvrir ARABCIA',
            ],
            'IIA_MAROC' => [
                'stats' => [
                    ['label' => 'Créé en', 'value' => '1985'],
                    ['label' => 'Adhérents', 'value' => 'environ 900'],
                    ['label' => 'Expérience', 'value' => 'plus de 40 ans'],
                ],
                'note' => 'Membre de IIA Global, UFAI, AFIIA, ECIIA et ARABCIA.',
                'cta' => 'Découvrir IIA Maroc',
            ],
        ],
    ],

    'network' => [
        'eyebrow' => 'Réseau',
        'title' => 'Un réseau international au service de la profession',
        'lede' => 'ARABCIA fédère les instituts et associations d’audit interne arabes et internationaux pour renforcer les échanges, le partage d’expertise et la montée en compétences.',
        'cta' => 'En savoir plus',
        'alt' => 'Carte du monde illustrant le réseau international',
    ],

    'sheet' => [
        'title' => 'Fiche technique',
        'lede' => 'Toutes les informations clés sur la conférence',
        'theme' => 'Thème',
        'dates' => 'Dates',
        'venue' => 'Lieu',
        'organisers' => 'Organisateurs',
        'host' => 'Institut hôte',
        'languages' => 'Langues',
        'audience' => 'Public cible',
    ],

    'audience' => [
        'eyebrow' => 'Public cible',
        'title' => 'Une communauté de décideurs, auditeurs et experts réunie autour des grands enjeux de demain.',
        'lede' => 'Auditeurs internes, responsables des risques, de la conformité et de la gouvernance, dirigeants, conseils d’administration, secteur public, régulateurs, instituts, étudiants, experts et partenaires.',
        'brief' => 'Professionnels de l’audit interne, dirigeants, experts',
        'alt' => 'Une salle de conférence pleine de participants',
    ],
];