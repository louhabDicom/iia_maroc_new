<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | رسائل التحقق
    |--------------------------------------------------------------------------
    |
    | قسم مجلد Laravel الأصلي يحتوي على اللغة الإنجليزية فقط، ويُدمج فوقه ملف
    | التطبيق، فتحصل English على رسائل الإطار تلقائيًا بينما يظهر أي مفتاح
    | ناقص هنا للزائر على شكل `validation.required` حرفيًا. لذلك يجب أن تحمل
    | كل لغة من لغات الموقع مجموعتها الكاملة.
    |
    | إضافة قاعدة تعني إضافة رسالتها هنا في التغيير نفسه.
    | tests/Feature/ValidationMessageCoverageTest.php يفشل عند نقص أي رسالة.
    |
    |--------------------------------------------------------------------------
    | قواعد التطبيق
    |--------------------------------------------------------------------------
    |
    | `phone` رسالة واحدة لكل سبب عن قصد: إخبار المُدخل بأن رقمًا ليس مغربيًا
    | أو أنه مسجّل بالفعل يحوّل استمارة التسجيل إلى دليل لأرقام هواتف الحضور.
    |
    */

    'phone' => 'رقم الهاتف غير صالح. الصيغة المتوقعة : 06 12 34 56 78 أو +212 6 12 34 56 78.',
    'phone_taken' => 'رقم الهاتف هذا مرتبط بحساب بالفعل.',
    'email_taken' => 'عنوان البريد الإلكتروني مستخدم بالفعل.',

    /*
    |--------------------------------------------------------------------------
    | القواعد القياسية
    |--------------------------------------------------------------------------
    */

    'accepted' => 'يجب قبول حقل :attribute.',
    'accepted_if' => 'يجب قبول حقل :attribute عندما تكون قيمة :other هي :value.',
    'after' => 'يجب أن يكون حقل :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون حقل :attribute تاريخًا بعد :date أو مساويًا له.',
    'alpha' => 'يجب أن يحتوي حقل :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي حقل :attribute على حروف وأرقام وشرطات فقط.',
    'alpha_num' => 'يجب أن يحتوي حقل :attribute على حروف وأرقام فقط.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'before' => 'يجب أن يكون حقل :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون حقل :attribute تاريخًا قبل :date أو مساويًا له.',
    'between' => [
        'array' => 'يجب أن يحتوي حقل :attribute على عدد عناصر بين :min و:max.',
        'file' => 'يجب أن يكون حجم حقل :attribute بين :min و:max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute بين :min و:max.',
        'string' => 'يجب أن يحتوي حقل :attribute على عدد أحرف بين :min و:max.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد حقل :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute ليس تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون حقل :attribute تاريخًا مساويًا لـ :date.',
    'date_format' => 'لا يطابق حقل :attribute الصيغة :format.',
    'decimal' => 'يجب أن يحتوي حقل :attribute على :decimal منازل عشرية.',
    'declined' => 'يجب رفض حقل :attribute.',
    'different' => 'يجب أن يكون حقل :attribute مختلفًا عن :other.',
    'digits' => 'يجب أن يتكون حقل :attribute من :digits أرقام.',
    'digits_between' => 'يجب أن يتكون حقل :attribute من عدد أرقام بين :min و:max.',
    'distinct' => 'قيمة حقل :attribute مكررة.',
    'email' => 'يجب أن يكون حقل :attribute بريدًا إلكترونيًا صالحًا.',
    'ends_with' => 'يجب أن ينتهي حقل :attribute بـ :values.',
    'enum' => 'القيمة المختارة لحقل :attribute غير صالحة.',
    'exists' => 'القيمة المختارة لحقل :attribute غير صالحة.',
    'file' => 'يجب أن يكون حقل :attribute ملفًا.',
    'filled' => 'يجب تعبئة حقل :attribute.',
    'gt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون حجم حقل :attribute أكثر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من :value.',
        'string' => 'يجب أن يحتوي حقل :attribute على أكثر من :value حرفًا.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :value عنصر على الأقل.',
        'file' => 'يجب أن يكون حجم حقل :attribute :value كيلوبايت على الأقل.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يحتوي حقل :attribute على :value حرف على الأقل.',
    ],
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'in' => 'القيمة المختارة لحقل :attribute غير صالحة.',
    'in_array' => 'يجب أن يوجد حقل :attribute ضمن :other.',
    'integer' => 'يجب أن يكون حقل :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون حقل :attribute عنوان IP صالحًا.',
    'json' => 'يجب أن يكون حقل :attribute نص JSON صالحًا.',
    'list' => 'يجب أن يكون حقل :attribute قائمة.',
    'lowercase' => 'يجب أن يكون حقل :attribute بأحرف صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أقل من :value عنصر.',
        'file' => 'يجب أن يكون حجم حقل :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أصغر من :value.',
        'string' => 'يجب أن يحتوي حقل :attribute على أقل من :value حرفًا.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :value عنصر.',
        'file' => 'يجب ألا يكون حجم حقل :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أصغر من أو تساوي :value.',
        'string' => 'يجب ألا يحتوي حقل :attribute على أكثر من :value حرفًا.',
    ],
    'max' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يكون حجم حقل :attribute أكبر من :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة حقل :attribute أكبر من :max.',
        'string' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max حرفًا.',
    ],
    'max_digits' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max أرقام.',
    'mimes' => 'يجب أن يكون حقل :attribute ملفًا من نوع :values.',
    'mimetypes' => 'يجب أن يكون حقل :attribute ملفًا من نوع :values.',
    'min' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :min عنصر على الأقل.',
        'file' => 'يجب أن يكون حجم حقل :attribute :min كيلوبايت على الأقل.',
        'numeric' => 'يجب ألا تكون قيمة حقل :attribute أصغر من :min.',
        'string' => 'يجب ألا يقل حقل :attribute عن :min حرفًا.',
    ],
    'min_digits' => 'يجب أن يحتوي حقل :attribute على :min أرقام على الأقل.',
    'multiple_of' => 'يجب أن تكون قيمة حقل :attribute من مضاعفات :value.',
    'not_in' => 'القيمة المختارة لحقل :attribute غير صالحة.',
    'not_regex' => 'صيغة حقل :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون حقل :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن تحتوي كلمة المرور على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي كلمة المرور على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي كلمة المرور على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت كلمة المرور هذه في تسريب بيانات. يرجى اختيار كلمة مرور أخرى.',
    ],
    'present' => 'يجب أن يكون حقل :attribute موجودًا.',
    'prohibited' => 'حقل :attribute ممنوع.',
    'regex' => 'صيغة حقل :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات لـ :values.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون قيمة :other هي :value.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كانت قيمة :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عندما يكون :values موجودًا.',
    'required_with_all' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'حقل :attribute مطلوب عندما لا يكون :values موجودًا.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا يكون أي من :values موجودًا.',
    'same' => 'يجب أن يتطابق حقل :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :size عنصر.',
        'file' => 'يجب أن يكون حجم حقل :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute :size.',
        'string' => 'يجب أن يحتوي حقل :attribute على :size حرف.',
    ],
    'starts_with' => 'يجب أن يبدأ حقل :attribute بـ :values.',
    'string' => 'يجب أن يكون حقل :attribute نصًا.',
    'timezone' => 'يجب أن يكون حقل :attribute منطقة زمنية صالحة.',
    'unique' => 'قيمة حقل :attribute مستخدمة من قبل.',
    'uploaded' => 'تعذر رفع حقل :attribute.',
    'uppercase' => 'يجب أن يكون حقل :attribute بأحرف كبيرة.',
    'url' => 'يجب أن يكون حقل :attribute رابطًا صالحًا.',
    'uuid' => 'يجب أن يكون حقل :attribute معرف UUID صالحًا.',

    'attributes' => [],

];
