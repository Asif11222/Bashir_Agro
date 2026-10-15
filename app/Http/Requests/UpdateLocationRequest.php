<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['required', 'integer', 'min:5', 'max:1000'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'farmer_id' => ['nullable', 'exists:farmers,id'],
            'boundary' => ['nullable'],
            'farmer_ids' => ['nullable', 'array'],
            'farmer_ids.*' => ['exists:farmers,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'লোকেশন নাম প্রদান করা আবশ্যক।',
            'type.required' => 'লোকেশনের ধরন নির্বাচন করা আবশ্যক।',
            'address.required' => 'প্রশাসনিক ঠিকানা প্রদান করা আবশ্যক।',
            'area.numeric' => 'জমির পরিমাণ একটি বৈধ সংখ্যা হতে হবে।',
            'latitude.between' => 'অক্ষাংশ (Latitude) -৯০ থেকে ৯০ এর মধ্যে হতে হবে।',
            'longitude.between' => 'দ্রাঘিমাংশ (Longitude) -১৮০ থেকে ১৮০ এর মধ্যে হতে হবে।',
            'geofence_radius_meters.required' => 'বাফার রেডিয়াস প্রদান করা আবশ্যক।',
            'geofence_radius_meters.min' => 'বাফার রেডিয়াস কমপক্ষে ৫ মিটার হতে হবে।',
            'status.required' => 'অবস্থা নির্ধারণ করা আবশ্যক।',
        ];
    }
}
