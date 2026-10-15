<?php

namespace App\Http\Requests;

use App\Models\TaskAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskAssignmentRequest extends FormRequest
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
            'farmer_id' => [
                'required',
                'integer',
                Rule::exists('farmers', 'id')->whereNull('deleted_at'),
            ],
            'task_template_id' => [
                'required',
                'integer',
                Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
            ],
            'farm_location_id' => [
                'required',
                'integer',
                Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
            ],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'priority' => ['required', 'string', 'in:normal,important,urgent'],
            'status' => ['nullable', 'string', 'in:assigned,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance with business rule checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled(['farmer_id', 'task_template_id', 'farm_location_id', 'scheduled_date'])) {
                $duplicateExists = TaskAssignment::where('farmer_id', $this->farmer_id)
                    ->where('task_template_id', $this->task_template_id)
                    ->where('farm_location_id', $this->farm_location_id)
                    ->whereDate('scheduled_date', $this->scheduled_date)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add(
                        'farmer_id',
                        'এই কৃষকের জন্য একই তারিখে ও একই লোকেশনে এই কাজটি ইতিমধ্যে বরাদ্দ করা আছে (ডুপ্লিকেট প্রতিরোধ)।'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'farmer_id.required' => 'কৃষক নির্বাচন করা আবশ্যক।',
            'farmer_id.exists' => 'নির্বাচিত কৃষকটি বিদ্যমান নেই।',
            'task_template_id.required' => 'কাজের টেমপ্লেট নির্বাচন করা আবশ্যক।',
            'task_template_id.exists' => 'নির্বাচিত কাজের টেমপ্লেটটি বিদ্যমান নেই।',
            'farm_location_id.required' => 'ফার্ম লোকেশন নির্বাচন করা আবশ্যক।',
            'farm_location_id.exists' => 'নির্বাচিত লোকেশনটি বিদ্যমান নেই।',
            'scheduled_date.required' => 'বরাদ্দের তারিখ নির্বাচন করা আবশ্যক।',
            'scheduled_date.date' => 'বরাদ্দের তারিখটি সঠিক ফরম্যাটে প্রদান করুন।',
            'start_time.required' => 'শুরুর সময় প্রদান করা আবশ্যক।',
            'start_time.date_format' => 'শুরুর সময় HH:MM ফরম্যাটে প্রদান করুন।',
            'end_time.date_format' => 'শেষ সময় HH:MM ফরম্যাটে প্রদান করুন।',
            'end_time.after' => 'শেষ সময় অবশ্যই শুরুর সময়ের পরবর্তী হতে হবে।',
            'priority.required' => 'কাজের অগ্রাধিকার নির্ধারণ করা আবশ্যক।',
            'priority.in' => 'অগ্রাধিকার অবশ্যই সাধারণ (normal), গুরুত্বপূর্ণ (important) বা জরুরি (urgent) হতে হবে।',
            'status.in' => 'অবস্থা অবশ্যই বরাদ্দকৃত (assigned), চলমান (in_progress), সম্পন্ন (completed) বা বাতিল (cancelled) হতে হবে।',
        ];
    }
}
