<?php

namespace App\Http\Requests;

use App\Models\Farmer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFarmerRequest extends FormRequest
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
        $farmerId = $this->route('farmer') instanceof Farmer
            ? $this->route('farmer')->id
            : $this->route('farmer');

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('farmers', 'phone')->ignore($farmerId)->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'nid' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'primary_activity' => ['required', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:70'],
            'farm_location_id' => ['nullable', 'exists:farm_locations,id'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'app_access_enabled' => ['nullable'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],
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
            'full_name.required' => 'কৃষকের পূর্ণ নাম প্রদান করা আবশ্যক।',
            'phone.required' => 'মোবাইল নম্বর প্রদান করা আবশ্যক।',
            'phone.unique' => 'এই মোবাইল নম্বরটি ইতিমধ্যে অন্য একজন কৃষকের জন্য নিবন্ধিত রয়েছে।',
            'address.required' => 'বর্তমান ঠিকানা প্রদান করা আবশ্যক।',
            'primary_activity.required' => 'প্রধান কার্যক্রম নির্বাচন করা আবশ্যক।',
            'profile_photo.image' => 'প্রোফাইল ছবি একটি বৈধ ছবি ফাইল হতে হবে।',
            'profile_photo.max' => 'ছবির সাইজ সর্বোচ্চ ২ মেগাবাইট হতে পারবে।',
            'emergency_contact_name.required' => 'জরুরি যোগাযোগকারীর নাম আবশ্যক।',
            'emergency_contact_phone.required' => 'জরুরি যোগাযোগকারীর মোবাইল নম্বর আবশ্যক।',
            'emergency_contact_relationship.required' => 'জরুরি যোগাযোগকারীর সাথে সম্পর্ক আবশ্যক।',
        ];
    }
}
