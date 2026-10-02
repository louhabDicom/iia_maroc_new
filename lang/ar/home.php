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
            'kicker' => 'المؤتمر الدولي',
            'logos_alt' => 'ARABCIA 2026 ومنظموه',
            'cta_programme' => 'البرنامج',
            'cta_sponsor' => 'كن شريكاً',
        ],

        'why' => [
            'eyebrow' => 'لماذا هذا المؤتمر؟',
            'title' => 'لقاء نستبق به ما هو آت',
            'lede' => 'المؤتمر السنوي ARABCIA :year، الذي تنظّمه جمعية ARABCIA ويستضيفه في المغرب المعهد الدولي للرقابة الداخلية، يجمع بالرباط المهنيين والمؤسسات التي تساهم في تطوير التدقيق الداخلي في العالم العربي وخارجه. وتحمل هذه الدورة عنوان «التدقيق الداخلي، شريك موثوق في تحويل المنظمات وتحقيق المرونة给他们».',
            'body' => 'في ظل توسع الذكاء الاصطناعي وتراجع الأمن السيبراني والتحولات الاقتصادية والاجتماعية، انفرضت على المنظمات إعادة التفكير في أساليب حكمرتها. وفي هذا السياق، يتكيف التدقيق الداخلي ويصير رافعة للأداء داخل المؤسسات. وسيبحث المؤتمر عن حلول ملموسة لهذه الرهانات، ويبيّن كيف يمكن للتدقيق الداخلي، بصفته محرّكاً للابتكار، أن يكون أداة فعّالة في خدمة الحكامة والقدرة على الصمود والأداء.',
            'image_alt' => 'مندوبون في نقاش ضمن قاعة المعرض.',
            'inset_alt' => 'جلسة عامة من ARABCIA قيد الانعقاد.',
            'inset_title' => 'ARABCIA :year',
            'inset_subtitle' => 'التدقيق الداخلي، شريك موثوق',
        ],

        'inscription' => [
            'title' => 'التسجيل',
            'ttc' => 'التعرفة الشاملة',
            'fx' => 'التعرفة بالعملة الأجنبية',
            'members' => 'المنتسبون',
            'non_members' => 'غير المنتسبين',
            'reserve' => 'احجز مشاركتك بـ :currency',
        ],

        'programme' => [
            'title' => 'برنامج المؤتمر',
            'download' => 'تحميل',
            'day' => 'اليوم :number',
            'expand' => 'عرض تفاصيل الجلسة',
            'collapse' => 'إخفاء تفاصيل الجلسة',
        ],

        'speakers' => [
            'eyebrow' => 'المتدخلون',
            'keynote' => 'متدخل رئيسي',
            'speaker' => 'متدخل',
            'previous' => 'المتدخلون السابقون',
            'next' => 'المتدخلون اللاحقون',
        ],

    ],
    'partners_title' => 'SPONSORS',

    'video_fallback' => 'لا يستطيع متصفحك عن تشغيل هذا الفيديو.',

    'video_play' => 'شغّل الفيديو',

    'video_lede' => 'ما تتناوله دورة 2026، بكلمات المنظمين.',

    'video_title' => 'المؤتمر في تسعون ثانية',

    'count_seconds' => 'ثوانٍ',

    'count_minutes' => 'دقائق',

    'count_hours' => 'ساعات',

    'count_days' => 'أيام',

    'countdown_label' => 'الوقت المتبقّي حتى المؤتمر',

    'stats' => [
        'attendees' => 'المشاركون',
        'workshops' => 'ورشات العمل',
        'days' => 'أيام',
    ],
    'about_subtitle' => 'مؤتمر لـ',
    'about_title' => 'حول المؤتمر',
    'audience_title' => 'الفئة المستهدفة',
    'contact_cta' => 'اتصل بنا',
    'editions_title' => 'الدورات السابقة',
    'editions_lede' => 'اجتمعت أجيال من المدققين الداخليين في الرباط، عامًا بعد عام، وهذه بعض لقطات من الدورات السابقة.',
    'editions_caption' => 'ARABCIA — دورة سابقة (:number)',
    'editions_alt' => 'صورة من دورة سابقة، رقم :number',
    'organisers_eyebrow' => 'المنظمون',
    'organisers_lede' => 'الاتحاد العربي لمعاهد التدقيق الداخلي هو من يعقد المؤتمر، والمعهد الدولي للتدقيق الداخلي هو من يستضيفه في المملكة المغربية.',
    'organisers_title' => 'من خلف مؤتمر ARABCIA 2026',
    'format_title' => 'التنظيم',
    'hero_cta' => 'تسجيل',
    'hero_secondary' => 'عرض البرنامج',
    'join_us' => 'انضم إلينا!',
    'programme_title' => 'نظرة على البرنامج',
    'registration_title' => 'التسجيل',
    'speakers_more' => 'عرض كل المتحدثين',
    'speakers_title' => 'خبراء ذوو شهرة دولية',
    'sponsors_title' => 'الشركاء',
    'stats.attendees' => 'المشاركون المتوقعون',
    'stats.days' => 'أيام',
    'stats.labs' => 'مختبرات الابتكار',
    'stats.workshops' => 'ورشات',
    'stats_title' => 'في أرقام',
    'theme_title' => 'الموضوع',
    'venue_title' => 'المكان والتواريخ',
];
