<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
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
    public function rules(): array
    {
        return [
        'child_id' => 'sometimes|exists:children,id',
        'doctor_id' => 'sometimes|exists:doctors,id',
        'date' => 'sometimes|date|after_or_equal:today',
        'time' => 'sometimes|date_format:H:i',
        'price' => 'nullable|numeric|min:0'
        ];
    }
}
