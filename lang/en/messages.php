<?php

return [
    // Appointment
    'upcoming_success'       => 'Upcoming appointments fetched successfully.',
    'past_success'           => 'Past appointments fetched successfully.',
    'upcoming_child_success' => 'Child upcoming appointments fetched successfully.',
    'past_child_success'     => 'Child past appointments fetched successfully.',
    'index_success'       => 'Appointments list fetched successfully.',
    'show_success'        => 'Appointment details fetched successfully.',
    'unauthorized'        => 'Unauthorized access.',
    'cannot_cancel_past'  => 'Cannot cancel a past appointment.',
    'cancel_full_refund'  => 'Appointment canceled. Full refund has been initiated.',
    'cancel_fee_deducted' => 'Appointment canceled. Refund initiated with a 25% cancellation fee deducted.',
    'notif_cancel_fee'    => 'Appointment cancelled successfully. 25% cancellation fee deducted. Refund amount: :amount',
    'notif_cancel_full'   => 'Appointment cancelled successfully. Full refund initiated. Refund amount: :amount',
    'cannot_book_past'             => 'You cannot book an appointment in the past.',
    'doctor_not_available_day'     => 'Doctor is not available on this day.',
    'outside_working_hours'        => 'Time is outside doctor working hours.',
    'time_already_booked'          => 'This time slot is already booked.',
    'slot_temporarily_locked'      => 'This time is temporarily locked for payment.',
    'appointment_locked_success'   => 'Appointment locked temporarily. Proceed to checkout to pay.',
    'appointment_updated_success'  => 'Appointment updated successfully.',
    'no_doctors_in_department'     => 'There are currently no doctors registered in this department.',
    'closest_appointments_fetched' => 'Closest available appointments per doctor fetched successfully.',
    'availability_deleted_success' => 'Time deleted successfully',

    //Doctor
    'no_appointments' => 'No appointments found on this day',
    'all_appointments_cancelled' => 'All appointmrnts have been cancelled successfully',
    'appointment_cancelled' => 'Appointment has been cancelled successfully',

    //Addition
    'addition_added_successfully' => 'addition added successfully',
    'addition_deleted_successfully' => 'addition deleted successfully',

    // Auth & OTP
    'phone_not_registered'       => 'This phone number is not registered in our records. Please check the number or create a new account.',
    'otp_sent_success'           => 'A new verification code has been sent to your phone.',
    'otp_invalid_expired'        => 'The provided OTP is invalid or has expired.',
    'phone_verified_success'     => 'Phone number verified successfully.',
    'doctor_not_found'           => 'Doctor not found.',
    'user_not_found'             => 'User not found.',
    'password_updated_success'   => 'Password updated successfully.',
    'invalid_credentials'        => 'Invalid phone number or password.',
    'login_welcome_back'         => 'Login successful. Welcome back!',
    'logout_succssfuly'          => 'Logged out successfully. Your session has been terminated.',
    'no_active_session'          => 'No active session found.',

    // Doctor CRUD
    'doctors_fetched_success'    => 'Doctors list fetched successfully.',
    'doctor_created_success'     => 'Doctor profile created successfully.',
    'doctor_fetched_success'     => 'Doctor profile fetched successfully.',
    'doctor_updated_success'     => 'Doctor profile updated successfully.',
    'doctor_deleted_success'     => 'Doctor and their related files have been deleted successfully.',
    'account_permanently_deleted' => 'Account deleted successfully',


    // Additions & Favorites
    'additions_recorded_success' => 'The appointment was completed and the additional costs were successfully recorded.',
    'favorite_removed'           => 'Removed from favorites.',
    'favorite_added'             => 'Added to favorites.',
    'favorites_fetched_success'  => 'Favorite doctors list fetched successfully.',

    // Doctor Availability
    'doctor_time_conflict'           => 'There is another doctor available in this department during the chosen time.',
    'availability_added_success'     => 'Working hours added successfully.',
    'availabilities_fetched_success' => 'Doctor working hours fetched successfully.',
    'no_available_times'             => 'No available times for booking on this day.',
    'available_times_fetched_success' => 'Available time slots fetched successfully.',
    'availability_deleted_success' => 'Available time deleted successfully',

    // Appointment Summary & Checkout
    'appointment_details_not_found'        => 'Appointment details not found.',
    'appointment_not_found'                => 'Appointment is not found.',
    'appointment_session_expired'          => 'Appointment session expired or not found.',
    'unauthorized_transaction'             => 'Unauthorized action for this financial transaction.',
    'payment_processing_wait'              => 'Payment is already processing. Please wait.',
    'stripe_init_failed'                   => 'Stripe payment initialization failed: ',
    'cannot_view_summary_for_cancelled_appointment' => 'Cannot view the summary for a cancelled appointment.',
    'cannot_complete_payment_for_cancelled_appointment' => 'Cannot complete payment for a cancelled appointment.',
    'appointment_confirmed_success' => 'Your appointment has been confirmed successfully.',
    'payment_completed_successfully' => 'Payment has been completed successfully.',

    // Firebase Push Notifications
    'notification_appointment_confirmed_title' => 'Appointment Confirmed',
    'notification_appointment_confirmed_body'  => 'Your appointment has been confirmed successfully.',
    'notification_appointment_cancelled_title' => 'Appointment cancelled',
    'notification_appointment_cancelled_doctor_body' =>':child cancelled the appointment scheduled for :date at :time.',
    'notification_appointment_cancelled_parent_body' =>'Your child’s appointment on :date at :time has been cancelled by the doctor.',
    'notification_all_appointments_cancelled_title' =>'All Appointments Cancelled',
    'notification_all_appointments_cancelled_doctor_body' =>'All appointments on :date have been cancelled. Total cancelled appointments: :count.',
    'notification_single_appointment_cancelled_doctor_body' =>'The appointment for :child on :date at :time has been cancelled.',

    // Children Management
    'child_added_success'      => 'Child profile created successfully.',
    'child_updated_success'    => 'Child profile updated successfully.',
    'children_fetched_success' => 'Children list fetched successfully.',
    'child_fetched_success'    => 'Child profile fetched successfully.',
    'child_not_found'          => 'Child profile not found or you are not authorized to view it.',
    'child_deleted_success'    => 'Child profile deleted successfully.',

    //Growth
    'invalid_date_format'   => 'Invalid date format. Please use Day-Month-Year (e.g., 05-06-2026).',
    'future_date_error'     => 'The date cannot be in the future. Please select today or a past date.',
    'growth_record_added'   => 'Growth record added successfully.',
    'bmi_underweight'       => 'Underweight - It is recommended to consult a pediatrician to follow up on supplementary nutrition.',
    'bmi_healthy'           => 'Ideal and healthy weight - Your child\'s growth is progressing excellently and matching global rates.',
    'bmi_overweight'        => 'Overweight - It is recommended to organize meals and reduce sugars and carbohydrates for the child.',
    'male'   => 'Male',
    'female' => 'Female',

    // Departments & Department Doctors
    'departments_fetched_success'        => 'Clinic departments fetched successfully.',
    'department_not_found'               => 'The requested department was not found.',
    'department_doctors_fetched_success' => 'Doctors of the specified department fetched successfully.',


    // Parent
    'parent_fetched_success' => 'Parent profile fetched successfully',
    'token_saved_successfully'     => 'Token saved successfully.',
    'profile_updated_successfully' => 'Profile updated successfully.',

    //Diadnosis and Reciept
    'Diagnosis_add_success' => 'Diagnosis added successfully',
    'Medication_add_success' => 'Medication added successfully',
    'Growth_add_success' => 'Growth record added successfully',
    'Medical_requests'  => 'Medical requests added successfully',


    'receptionist_not_found' => 'Receptionist not found.',
    'password_updated_successfully' => 'Password updated successfully.',


    // Statuses (Values from Database)
    'pending'                => 'Pending',
    'confirmed'              => 'Confirmed',
    'completed'              => 'Completed',
    'cancelled_by_patient'              => 'Cancelled by patient',
    'cancelled_by_clinic'              => 'Cancelled by clinic',
    'missed'                 => 'Missed',
    'finished' => 'Finished',


    'error' => 'Error',

    'days' => [
        'monday'    => 'Monday',
        'tuesday'   => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday'  => 'Thursday',
        'friday'    => 'Friday',
        'saturday'  => 'Saturday',
        'sunday'    => 'Sunday',
    ],

    'departments_names' => [
        'Pediatrics' => 'Pediatrics',
        'Dentistry'  => 'Dentistry',
        'Psychiatry' => 'Psychiatry',
    ],

    'vaccine_schedule_created_successfully' => 'Vaccine schedule created successfully.',
    'schedule_not_found'                   => 'Vaccine schedule not found.',
    'schedule_status_updated'               => 'Vaccine schedule status updated successfully.',
    'vaccine_already_given_to_child'        => 'This vaccine has already been given to this child.',
    'vaccination_recorded_successfully'     => 'Child vaccination recorded successfully.',
    'vaccine_schedule_already_exists' => 'A schedule for this vaccine already exists on the selected date.',
    'vaccine_created_successfully' => 'Vaccine created successfully',

    'addition_added_successfully' => 'Addition added successfully.',
    'addition_deleted_successfully' => 'Addition deleted successfully.',
    'cannot_cancel_appointment' => 'Appointment cannot be cancelled.',
    'account_blocked' => 'Your account has been blocked.',
    'child_added_successfully' => 'Child added successfully.',
    'child_parent_mismatch' => 'The selected child does not belong to this parent.',
    'parent_id_required' => 'The parent ID field is required.',
    'unauthorized_role' => 'You are not authorized to perform this action with your current role.',
    'child_updated_successfully' => 'Child updated successfully.',
    'child_deleted_successfully' => 'Child deleted successfully.',
    'growth_record_deleted_successfully' => 'Growth record deleted successfully.',
    'record_not_found' => 'Record not found.',
    'parent_id_required_for_receptionists' => 'The parent_id field is required for receptionists.',
    'parent_profile_fetched_successfully' => 'Parent profile fetched successfully.',
    'parent_not_found' => 'Parent not found.',
    'phone_verified_and_account_created_successfully' => 'Phone number verified and account created successfully.',
    'profile_updated_successfully' => 'Profile updated successfully.',
    'account_deletion_failed' => 'Account deletion failed.',
    'parent_added_successfully' => 'Parent added successfully.',
    'appointment_booked_successfully' => 'Appointment booked successfully.',
    'cannot_cancel_this_appointment' => 'Cannot cancel this appointment.',
    'cancelled_by_clinic' => 'Cancelled by clinic.',
    'notif_clinic_cancelled_appointment' => 'Your appointment has been cancelled by the clinic.',
    'appointment_canceled_success' => 'Appointment canceled successfully.',
    'past_doctor_success' => 'Past doctors fetched successfully.',
    'upcoming_doctor_success' => 'Upcoming doctors fetched successfully.',
    'invalid_date_format' => 'Invalid date format. Please use Y-m-d.',
    'invalid_status_for_checkin' => 'Invalid status for check-in.',
    'checkin_today_only' => 'Check-in is only allowed for today\'s appointments.',
    'patient_checked_in_successfully' => 'Patient checked in successfully.',
    'account_blocked_notification' => 'Your account has been blocked. Please contact support.',
    'user_blocked_successfully' => 'User blocked successfully.',
    'user_tokens_revoked_successfully' => 'User tokens revoked successfully.',
    'user_tokens_revoked_successfully' => 'User tokens revoked successfully.',
];
