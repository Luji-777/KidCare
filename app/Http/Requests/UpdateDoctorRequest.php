<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {

        $doctorId = $this->route('doctor');

        return [
            'first_name' => 'sometimes|required|string|max:50',
            'last_name'  => 'sometimes|required|string|max:50',
            'address'    => 'sometimes|required|string|max:255',
            'education'  => 'sometimes|required|string|max:255',
            'fee'        => 'sometimes|required|numeric|min:0',
            'commission_percentage' => 'sometimes|required|numeric|min:0|max:100',
            'experience_years' => 'sometimes|required|integer|min:0',
            'department_id'    => 'sometimes|required|exists:departments,id',

            'email' => 'sometimes|required|email|unique:doctors,email,' . $doctorId,
            'phone_number' => 'sometimes|required|string|unique:doctors,phone_number,' . $doctorId,

            'password' => 'nullable|string|min:8|confirmed',

            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cv' => 'nullable|file|mimes:pdf|max:5120',
        ];
    }
}
