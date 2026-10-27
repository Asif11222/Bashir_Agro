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
        $rules = [
            'recurrence_type' => ['nullable', 'string', 'in:once,daily,weekly,monthly'],
            'farmer_id' => [
                'required',
                'integer',
                Rule::exists('farmers', 'id')->whereNull('deleted_at'),
            ],
            'farm_location_id' => [
                'required',
                'integer',
                Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
            ],
            'status' => ['nullable', 'string', 'in:assigned,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        $recurrence = $this->input('recurrence_type', 'once');

        if ($recurrence === 'once') {
            $rules['task_template_id'] = [
                'required',
                'integer',
                Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
            ];
            $rules['scheduled_date'] = ['required', 'date'];
            $rules['start_time'] = ['required', 'date_format:H:i'];
            $rules['end_time'] = ['nullable', 'date_format:H:i', 'after:start_time'];
            $rules['priority'] = ['required', 'string', 'in:normal,important,urgent'];
        } elseif ($recurrence === 'daily') {
            $rules['start_date'] = ['required', 'date'];
            $rules['end_date'] = ['required', 'date', 'after_or_equal:start_date'];
            $rules['daily_tasks'] = ['required', 'array', 'min:1'];
            $rules['daily_tasks.*.task_template_id'] = [
                'required',
                'integer',
                Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
            ];
            $rules['daily_tasks.*.farm_location_id'] = [
                'nullable',
                'integer',
                Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
            ];
            $rules['daily_tasks.*.start_time'] = ['required', 'date_format:H:i'];
            $rules['daily_tasks.*.end_time'] = ['nullable', 'date_format:H:i'];
            $rules['daily_tasks.*.priority'] = ['required', 'string', 'in:normal,important,urgent'];
            $rules['daily_tasks.*.notes'] = ['nullable', 'string', 'max:500'];
        } elseif ($recurrence === 'weekly') {
            $rules['start_date'] = ['required', 'date'];
            $rules['weeks_count'] = ['required', 'integer', 'min:1', 'max:24'];
            $rules['weekly_timing_mode'] = ['nullable', 'string', 'in:same,by_day,by_week,custom'];

            $hasCustomSchedule = $this->has('custom_schedule') && is_array($this->input('custom_schedule')) && count($this->input('custom_schedule')) > 0;

            if ($hasCustomSchedule) {
                $rules['weekly_days'] = ['nullable', 'array'];
                $rules['weekly_days.*'] = ['nullable', 'string', 'in:sat,sun,mon,tue,wed,thu,fri,0,1,2,3,4,5,6'];
                $rules['weekly_tasks'] = ['nullable', 'array'];
                $rules['weekly_tasks.*.task_template_id'] = [
                    'nullable',
                    'integer',
                    Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
                ];
                $rules['weekly_tasks.*.farm_location_id'] = [
                    'nullable',
                    'integer',
                    Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
                ];
                $rules['weekly_tasks.*.start_time'] = ['nullable', 'date_format:H:i'];
                $rules['weekly_tasks.*.end_time'] = ['nullable', 'date_format:H:i'];
                $rules['weekly_tasks.*.priority'] = ['nullable', 'string', 'in:normal,important,urgent'];
                $rules['weekly_tasks.*.notes'] = ['nullable', 'string', 'max:500'];

                $rules['custom_schedule'] = ['required', 'array', 'min:1'];
                $rules['custom_schedule.*.scheduled_date'] = ['required', 'date'];
                $rules['custom_schedule.*.task_template_id'] = [
                    'required',
                    'integer',
                    Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
                ];
                $rules['custom_schedule.*.farm_location_id'] = [
                    'nullable',
                    'integer',
                    Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
                ];
                $rules['custom_schedule.*.start_time'] = ['required', 'date_format:H:i'];
                $rules['custom_schedule.*.end_time'] = ['nullable', 'date_format:H:i'];
                $rules['custom_schedule.*.priority'] = ['nullable', 'string', 'in:normal,important,urgent'];
                $rules['custom_schedule.*.notes'] = ['nullable', 'string', 'max:500'];
            } else {
                $rules['weekly_days'] = ['required', 'array', 'min:1'];
                $rules['weekly_days.*'] = ['required', 'string', 'in:sat,sun,mon,tue,wed,thu,fri,0,1,2,3,4,5,6'];
                $rules['weekly_tasks'] = ['required', 'array', 'min:1'];
                $rules['weekly_tasks.*.task_template_id'] = [
                    'required',
                    'integer',
                    Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
                ];
                $rules['weekly_tasks.*.farm_location_id'] = [
                    'nullable',
                    'integer',
                    Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
                ];
                $rules['weekly_tasks.*.start_time'] = ['required', 'date_format:H:i'];
                $rules['weekly_tasks.*.end_time'] = ['nullable', 'date_format:H:i'];
                $rules['weekly_tasks.*.priority'] = ['required', 'string', 'in:normal,important,urgent'];
                $rules['weekly_tasks.*.notes'] = ['nullable', 'string', 'max:500'];
            }
        } elseif ($recurrence === 'monthly') {
            $rules['start_date'] = ['required', 'date'];
            $rules['months_count'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['monthly_days'] = ['required', 'array', 'min:1'];
            $rules['monthly_days.*'] = ['required', 'integer', 'min:1', 'max:31'];
            $rules['monthly_tasks'] = ['required', 'array', 'min:1'];
            $rules['monthly_tasks.*.task_template_id'] = [
                'required',
                'integer',
                Rule::exists('task_templates', 'id')->whereNull('deleted_at'),
            ];
            $rules['monthly_tasks.*.farm_location_id'] = [
                'nullable',
                'integer',
                Rule::exists('farm_locations', 'id')->whereNull('deleted_at'),
            ];
            $rules['monthly_tasks.*.start_time'] = ['required', 'date_format:H:i'];
            $rules['monthly_tasks.*.end_time'] = ['nullable', 'date_format:H:i'];
            $rules['monthly_tasks.*.priority'] = ['required', 'string', 'in:normal,important,urgent'];
            $rules['monthly_tasks.*.notes'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance with business rule checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $recurrence = $this->input('recurrence_type', 'once');
            if ($recurrence === 'once') {
                if ($this->filled(['farmer_id', 'task_template_id', 'farm_location_id', 'scheduled_date', 'start_time'])) {
                    $duplicateExists = TaskAssignment::where('farmer_id', $this->farmer_id)
                        ->where('task_template_id', $this->task_template_id)
                        ->where('farm_location_id', $this->farm_location_id)
                        ->whereDate('scheduled_date', $this->scheduled_date)
                        ->where('start_time', $this->start_time)
                        ->whereIn('status', ['assigned', 'in_progress'])
                        ->exists();

                    if ($duplicateExists) {
                        $validator->errors()->add(
                            'farmer_id',
                            'এই কৃষকের জন্য একই তারিখে ও একই সময়ে এই কাজটি ইতিমধ্যে বরাদ্দ করা আছে (ডুপ্লিকেট প্রতিরোধ)।'
                        );
                    }
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
            'start_date.required' => 'শুরুর তারিখ নির্বাচন করা আবশ্যক।',
            'end_date.required' => 'শেষের তারিখ নির্বাচন করা আবশ্যক।',
            'end_date.after_or_equal' => 'শেষের তারিখ অবশ্যই শুরুর তারিখ বা তার পরবর্তী হতে হবে।',
            'daily_tasks.required' => 'অন্তত একটি দৈনিক কাজ নির্বাচন করতে হবে।',
            'weeks_count.required' => 'সপ্তাহ সংখ্যা উল্লেখ করা আবশ্যক।',
            'weekly_days.required' => 'সপ্তাহের অন্তত একটি দিন নির্বাচন করা আবশ্যক।',
            'weekly_tasks.required' => 'অন্তত একটি সাপ্তাহিক কাজ নির্বাচন করতে হবে।',
            'months_count.required' => 'মাস সংখ্যা উল্লেখ করা আবশ্যক।',
            'monthly_days.required' => 'মাসের অন্তত একটি দিন নির্বাচন করা আবশ্যক।',
            'monthly_tasks.required' => 'অন্তত একটি মাসিক কাজ নির্বাচন করতে হবে।',
            'custom_schedule.required' => 'অন্তত একটি কাজের শিডিউল থাকা আবশ্যক।',
            'custom_schedule.min' => 'অন্তত একটি কাজের শিডিউল থাকা আবশ্যক।',
            'custom_schedule.*.scheduled_date.required' => 'কাজের তারিখ নির্ধারণ করা আবশ্যক।',
            'custom_schedule.*.task_template_id.required' => 'কাজের ধরন নির্বাচন করা আবশ্যক।',
            'custom_schedule.*.task_template_id.exists' => 'নির্বাচিত কাজের টেমপ্লেটটি সঠিক নয়।',
            'custom_schedule.*.start_time.required' => 'শুরুর সময় প্রদান করা আবশ্যক।',
            'custom_schedule.*.start_time.date_format' => 'শুরুর সময় HH:MM ফরম্যাটে প্রদান করুন।',
            'custom_schedule.*.end_time.date_format' => 'শেষ সময় HH:MM ফরম্যাটে প্রদান করুন।',
        ];
    }
}
