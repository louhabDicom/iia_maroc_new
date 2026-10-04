<?php

declare(strict_types=1);

/*
| The authenticator-app enrolment flow, replacing SMS phone verification.
|
| `tradeoff` and `recovery.warning` are the two that matter most and the two most
| often left out. A delegate who does not realise they should have saved the
| codes finds out on the day their phone is flat, and by then this page cannot
| help them.
*/

return [
    'code_hint' => 'أدخل الرمز المكوّن من :digits أرقام الذي تعرضه تطبيقتك.',
    'code_label' => 'الرمز من تطبيقتك',
    'code_required' => 'أدخل الرمز الذي تعرضه تطبيقتك.',
    'done_heading' => 'تم إعداد التطبيق',
    'done_lede' => 'تم تأكيد حسابك.مرحبًا بك في ARABCIA 2026.',
    'done_title' => 'تم إعداد التطبيق',
    'heading' => 'إعداد تطبيق المصادقة لديك',
    'invalid_code' => 'الرمز غير صحيح. انتظر ظهور رمز جديد ثم أعد المحاولة.',
    'lede' => 'تطبيق المصادقة ينتج الرموز على هاتفك أنت. وهو مجاني وغير محدود، ولا يحتاج إلى أي مزوّد رسائل نصية.',

    'not_enrolled' => 'يجب عليك إعداد تطبيق المصادقة.',

    'pending_notice' => 'تم إنشاء حسابك، ولا يزال عليك إعداد تطبيق المصادقة قبل الحجز.',

    'qr_alt' => 'رمز QR للإعداد الخاص بـ :issuer',

    'recovery' => [
        'already_shown' => 'لا تظهر هذه الرموز إلا مرة واحدة. لديك :count رمز احتياطي.',
        'continue' => 'متابعة',
        'heading' => 'الرموز الاحتياطية',
        'lede' => 'احتفظ بهذه الرموز في مكان غير هاتفك. كل رمز يُستعمل مرة واحدة فقط.',
        'remaining' => 'لديك :count رمز احتياطي.',
        'title' => 'الرموز الاحتياطية',
        'warning' => 'صوّرها أو اطبعها. إذا فقدت هاتفك وهذه الرموز، لا يمكن لفريق الدعم سوى استعادة حسابك.',
    ],

    'step_install' => 'ثبّت تطبيق مصادقة',
    'step_install_lede' => 'Google Authenticator أو Microsoft Authenticator أو 1Password على هاتفك.',

    'step_manual' => 'أو أدخل المفتاح يدويًا',
    'step_manual_lede' => 'إذا تعذّر عليك مسح رمز QR، أدخل هذا المفتاح في تطبيقك.',

    'step_scan' => 'امسح رمز QR',
    'step_scan_lede' => 'مع :issuer، ستعرض تطبيقك رمزًا جديدًا كل 30 ثانية.',

    'submit' => 'إكمال الإعداد',
    'title' => 'إعداد المصادقة',
    'tradeoff' => 'هذه الطريقة مجانية، لكنها تفترض وجود هاتف احتياطي وهذه الرموز. بدون أحدهما، لا يمكن استرجاع حسابك دون مساعدتنا.',
];
