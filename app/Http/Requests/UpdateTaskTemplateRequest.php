<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'instructions' => ['required', 'string'],
            'required_photo_count' => ['required', 'integer', 'min:0', 'max:10'],
            'require_realtime_photo' => ['nullable'],
            'allow_gallery_upload' => ['nullable'],
            'require_geofence' => ['nullable'],
            'recurrence_type' => ['required', 'string', 'in:once,daily,weekly,monthly,এককালীন,দৈনিক,সাপ্তাহিক,মাসিক'],
            'status' => ['required', 'string', 'in:active,inactive'],
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
            'name.required' => 'কাজের নাম প্রদান করা আবশ্যক।',
            'category.required' => 'কাজের শ্রেণি নির্বাচন করা আবশ্যক।',
            'instructions.required' => 'কাজের নির্দেশনা প্রদান করা আবশ্যক।',
            'required_photo_count.required' => 'প্রয়োজনীয় ছবির সংখ্যা নির্ধারণ করা আবশ্যক।',
            'required_photo_count.integer' => 'ছবির সংখ্যা একটি পূর্ণসংখ্যা হতে হবে।',
            'required_photo_count.min' => 'ছবির সংখ্যা সর্বনিম্ন ০ হতে পারবে।',
            'required_photo_count.max' => 'ছবির সংখ্যা সর্বোচ্চ ১০টি হতে পারবে।',
            'recurrence_type.required' => 'কাজের পুনরাবৃত্তির ধরন নির্বাচন করা আবশ্যক।',
            'status.required' => 'অবস্থা নির্ধারণ করা আবশ্যক।',
        ];
    }
}
