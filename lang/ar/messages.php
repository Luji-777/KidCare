<?php

return [
    // Appointment
    'upcoming_success'       => 'تم جلب المواعيد القادمة بنجاح.',
    'past_success'           => 'تم جلب المواعيد السابقة بنجاح.',
    'upcoming_child_success' => 'تم جلب المواعيد القادمة للطفل بنجاح.',
    'past_child_success'     => 'تم جلب المواعيد السابقة للطفل بنجاح.',
    'index_success'       => 'تم جلب قائمة المواعيد بنجاح.',
    'show_success'        => 'تم جلب تفاصيل الموعد بنجاح.',
    'unauthorized'        => 'غير مصرح لك بالقيام بهذا الإجراء.',
    'cannot_cancel_past'  => 'لا يمكن إلغاء موعد قد انتهى تاريخه بالفعل.',
    'cancel_full_refund'  => 'تم إلغاء الموعد، وجاري إعادة المبلغ بالكامل لحسابكم.',
    'cancel_fee_deducted' => 'تم إلغاء الموعد، وجاري إعادة المبلغ بعد خصم 25% كرسوم إلغاء متأخر.',
    'notif_cancel_fee'    => 'تم إلغاء الموعد بنجاح. تم خصم 25% رسوم إلغاء. المبلغ المسترد: :amount',
    'notif_cancel_full'   => 'تم إلغاء الموعد بنجاح. جاري استرداد كامل المبلغ. المبلغ المسترد: :amount',
    'cannot_book_past'             => 'لا يمكنك حجز موعد بتاريخ ووقت قد مضى.',
    'doctor_not_available_day'     => 'الطبيب غير متاح للعمل في هذا اليوم.',
    'outside_working_hours'        => 'الوقت المختار خارج ساعات العمل الرسمية للطبيب.',
    'time_already_booked'          => 'هذا الموعد محجوز مسبقاً من قِبل مستخدم آخر.',
    'slot_temporarily_locked'      => 'هذا الوقت مغلق مؤقتاً لإتمام عملية الدفع.',
    'appointment_locked_success'   => 'تم قفل الموعد مؤقتاً بنجاح، يرجى الانتقال إلى صفحة الدفع لإتمام الحجز.',
    'appointment_updated_success'  => 'تم تحديث بيانات الموعد بنجاح.',
    'no_doctors_in_department'     => 'لا يوجد أطباء مسجلين في هذا القسم حالياً.',
    'closest_appointments_fetched' => 'تم جلب أقرب المواعيد المتاحة لكل طبيب بنجاح.',
    'availability_deleted_success' => 'تم حذف الوقت بنجاح',
    // Auth & OTP
    'phone_not_registered'       => 'رقم الهاتف هذا غير مسجل في سجلاتنا. يرجى التحقق من الرقم أو إنشاء حساب جديد.',
    'otp_sent_success'           => 'تم إرسال رمز تحقق جديد إلى هاتفك بنجاح.',
    'otp_invalid_expired'        => 'رمز التحقق المدخل غير صحيح أو انتهت صلاحيته.',
    'phone_verified_success'     => 'تم التحقق من رقم الهاتف بنجاح.',
    'doctor_not_found'           => 'لم يتم العثور على بيانات الطبيب.',
    'user_not_found'             => 'لم يتم العثور على بيانات المستخدم.',
    'password_updated_success'   => 'تم تحديث كلمة المرور بنجاح.',
    'invalid_credentials'        => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
    'login_welcome_back'         => 'تم تسجيل الدخول بنجاح. أهلاً بعودتك!',
    'logout_succssfuly'          => 'تم تسجيل الخروج بجاح.',
    'no_active_session'          => 'لم يتم العثور على جلسة نشطة.',

    // Doctor CRUD
    'doctors_fetched_success'    => 'تم جلب قائمة الأطباء بنجاح.',
    'doctor_created_success'     => 'تم إنشاء الملف الشخصي للطبيب بنجاح.',
    'doctor_fetched_success'     => 'تم جلب بيانات الطبيب بنجاح.',
    'doctor_updated_success'     => 'تم تحديث الملف الشخصي للطبيب بنجاح.',
    'doctor_deleted_success'     => 'تم حذف الطبيب والملفات المتعلقة به بنجاح.',
    'account_permanently_deleted' => 'تم حذف الحساب بنجاح',

    // Additions & Favorites
    'additions_recorded_success' => 'تم إنهاء الموعد وتسجيل التكاليف الإضافية بنجاح.',
    'favorite_removed'           => 'تمت إزالته من المفضلة.',
    'favorite_added'             => 'تمت إضافته إلى المفضلة.',
    'favorites_fetched_success'  => 'تم جلب قائمة الأطباء المفضلين بنجاح.',

    //Doctor
    'no_appointments' => 'لا يوجد مواعيد في هذا اليوم',
    'all_appointments_cancelled' => 'تم الغاء جميع المواعيد بنجاح',
    'appointment_cancelled' => 'تم الغاء الموعد بنجاح',

    //Addition
    'addition_added_successfully'=>'تمت الاضافة بنجاح',
    'addition_deleted_successfully'=>'تم الحذف بنجاح',

    // Doctor Availability
    'doctor_time_conflict'           => 'يوجد طبيب آخر متاح في نفس هذا القسم خلال الوقت المختار.',
    'availability_added_success'     => 'تم إضافة أوقات الدوام بنجاح.',
    'availabilities_fetched_success' => 'تم جلب أوقات دوام الطبيب بنجاح.',
    'no_available_times'             => 'لا توجد أوقات متاحة للحجز في هذا اليوم.',
    'available_times_fetched_success' => 'تم جلب الفترات الزمنية المتاحة بنجاح.',
    'availability_deleted_success' => 'تم الحذف بنجاح',

    // Appointment Summary & Checkout
    'appointment_details_not_found'        => 'لم يتم العثور على تفاصيل الموعد.',
    'appointment_not_found'                => 'الموعد المحدد غير موجود.',
    'appointment_session_expired'          => 'انتهت صلاحية جلسة الحجز المؤقت أو أنها غير موجودة.',
    'unauthorized_transaction'             => 'إجراء غير مصرح به لهذه المعاملة المالية.',
    'payment_processing_wait'              => 'عملية الدفع قيد المعالجة الآن. يرجى الانتظار.',
    'stripe_init_failed'                   => 'فشل في تهيئة بوابة دفع Stripe: ',
    'cannot_view_summary_for_cancelled_appointment' => 'لا يمكن عرض ملخص الحساب لموعد ملغى.',
    'cannot_complete_payment_for_cancelled_appointment' => 'لا يمكن إتمام عملية الدفع لموعد ملغى.',

    // Firebase Push Notifications
    'notification_appointment_confirmed_title' => 'تم تأكيد الموعد بنجاح',
    'notification_appointment_confirmed_body'  => 'تمت عملية الدفع وتأكيد موعد طفلك في العيادة بنجاح.',

    // Children Management
    'child_added_success'      => 'تم إضافة ملف الطفل بنجاح.',
    'child_updated_success'    => 'تم تحديث بيانات الطفل بنجاح.',
    'children_fetched_success' => 'تم جلب قائمة الأطفال بنجاح.',
    'child_fetched_success'    => 'تم جلب بيانات الطفل بنجاح.',
    'child_not_found'          => 'ملف الطفل غير موجود أو غير مصرح لك بالوصول إليه.',
    'child_deleted_success'    => 'تم حذف ملف الطفل بنجاح.',

    //Growth
    'invalid_date_format'   => 'صيغة التاريخ غير صحيحة. يرجى استخدام يوم-شهر-سنة (مثال: 05-06-2026).',
    'future_date_error'     => 'لا يمكن أن يكون التاريخ في المستقبل. يرجى اختيار تاريخ اليوم أو تاريخ سابق.',
    'growth_record_added'   => 'تم إضافة سجل النمو بنجاح.',
    'bmi_underweight'       => 'وزن أقل من الطبيعي - ينصح بمراجعة طبيب الأطفال لمتابعة التغذية المكملة.',
    'bmi_healthy'           => 'وزن مثالي وصحي - نمو طفلك يسير بشكل ممتاز ومطابق للمعدلات العالمية.',
    'bmi_overweight'        => 'زيادة في الوزن - ينصح بتنظيم الوجبات وتقليل السكريات والنشويات للطفل.',
    'male'   => 'ذكر',
    'female' => 'أنثى',

    // Departments & Department Doctors
    'departments_fetched_success'        => 'تم جلب أقسام العيادة بنجاح.',
    'department_not_found'               => 'القسم الطبي المطلوب غير موجود.',
    'department_doctors_fetched_success' => 'تم جلب أطباء القسم المحدد بنجاح.',

    //Parent
    'parent_fetched_success' => 'تم جلب بيانات الأهل بنجاح.',
    'token_saved_successfully'     => 'تم حفظ رمز Firebase بنجاح.',
    'profile_updated_successfully' => 'تم تحديث البيانات الشخصية بنجاح.',

    //Diadnosis and Reciept
    'Diagnosis_add_success' => 'تم اضافة التشخيص بنجاح.',
    'Medication_add_success' => 'تم اضافة الوصفة بنجاح.',
    'Growth_add_success' => 'تم اضافة تقرير النمو بنجاح.',
    'Medical_requests'  => 'تم اضافة المتطلبات الطبية بنجاح.',


    'receptionist_not_found' => 'موظف الاستقبال غير موجود.',
    'password_updated_successfully' => 'تم تحديث كلمة السر بنجاح.',

    // Statuses (Values from Database)
    'pending'                => 'قيد الانتظار',
    'confirmed'              => 'مؤكد',
    'completed'              => 'مكتمل',
    'cancelled_by_clinic'              => 'ملغي من قبل العيادة',
    'cancelled_by_patient'              => 'ملغي من قبل المريض',

    'error' => 'خطأ',

    'paid_online'    => 'مدفوع إلكترونياً',
    'partially_paid' => 'مدفوع جزئياً',
    'fully_paid'     => 'مدفوع بالكامل',

    'days' => [
        'monday'    => 'الإثنين',
        'tuesday'   => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday'  => 'الخميس',
        'friday'    => 'الجمعة',
        'saturday'  => 'السبت',
        'sunday'    => 'الأحد',
    ],

    'departments_names' => [
        'Pediatrics' => 'قسم الأطفال',
        'Dentistry'  => 'طب الأسنان',
        'Psychiatry' => 'الطب النفسي',
    ],

    'vaccine_schedule_created_successfully' => 'تم الإعلان عن موعد اللقاح بنجاح.',
    'schedule_not_found'                   => 'موعد اللقاح غير موجود.',
    'schedule_status_updated'               => 'تم تحديث حالة موعد اللقاح بنجاح.',
    'vaccine_already_given_to_child'        => 'هذا اللقاح تم توثيقه لهذا الطفل من قبل.',
    'vaccination_recorded_successfully'     => 'تم تسجيل إعطاء اللقاح للطفل بنجاح.',
    'vaccine_schedule_already_exists' => 'تم الإعلان عن موعد لهذا اللقاح في نفس اليوم من قبل.',
    'vaccine_created_successfully' => 'تم إضافة اللقاح بنجاح'
];
