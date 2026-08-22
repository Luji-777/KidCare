<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'numeric'  => 'يجب أن يكون :attribute رقماً.',
    'string'   => 'يجب أن يكون :attribute نصاً.',
    'email'    => 'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.',
    'unique'   => 'قيمة :attribute مستخدمة من قبل.',
    'exists'   => ':attribute المحدد غير صحيح.',
    'array'    => 'يجب أن يكون :attribute مصفوفة.',
    'date'     => 'حقل :attribute ليس تاريخاً صحيحاً.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'min'      => [
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min.',
        'string'  => 'يجب أن يتكون :attribute من :min أحرف على الأقل.',
    ],
    'max'      => [
        'numeric' => 'لا يجب أن تتعدى قيمة :attribute الـ :max.',
        'string'  => 'لا يجب أن يتعدى :attribute الـ :max حرفاً.',
    ],
    'digits_between' => 'يجب أن يكون عدد أرقام :attribute بين :min و :max رقماً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    | هنا قمنا بجمع كل المدخلات الخاصة بالـ Form Requests والـ Controllers
    |--------------------------------------------------------------------------
    */
    'attributes' => [
    
        'phone_number'          => 'رقم الهاتف',
        'password'              => 'كلمة المرور',
        'email'                 => 'البريد الإلكتروني',
        'address'               => 'العنوان',
        'date'                  => 'التاريخ',
        'time'                  => 'الوقت',
        'currency'              => 'العملة',
        'fcm_token'             => 'رمز الإشعارات',
        'status'                => 'الحالة',

        'first_name'            => 'الاسم الأول',
        'last_name'             => 'الاسم الأخير',
        'profile_picture'       => 'الصورة الشخصية',
        'cv'                    => 'السيرة الذاتية',
        'otp'                   => 'رمز التحقق (OTP)',
        'department_id'         => 'القسم الطبي',
        'commission_percentage' => 'نسبة عمولة الطبيب',

        'day_of_week'           => 'اليوم من الأسبوع',
        'start_time'            => 'وقت البدء',
        'end_time'              => 'وقت الانتهاء',

        'child_id'              => 'معرّف الطفل',
        'image'                 => 'صورة الطفل',
        'birth_date'            => 'تاريخ الميلاد',
        'height'                => 'الطول',
        'weight'                => 'الوزن',

        'appointment_id'        => 'رقم الموعد',
        'price'                 => 'السعر',

        'additions'             => 'قائمة الإضافات والتكاليف',
        'additions.*.item_name' => 'اسم الخدمة الإضافية',
        'additions.*.price'     => 'سعر الخدمة الإضافية',
    ],
];
