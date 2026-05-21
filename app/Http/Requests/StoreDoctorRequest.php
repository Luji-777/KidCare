<?php

namespace App\Http\Requests;

use App\Models\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
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
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:doctors,email',
            'phone_number' => 'required|string|unique:doctors,phone_number',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'address' => 'required|string|max:255',
            'experience_years' => 'required|integer|min:0',
            'education' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'profile_picture' => 'image|mimes:jpeg,png,jpg|max:2048',
            'fee' => 'required|numeric|min:0',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'cv' => 'nullable|file|mimes:pdf|max:5120',
        ];
    }
}
