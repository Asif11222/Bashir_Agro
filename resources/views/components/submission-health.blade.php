{{-- Render from the already eager-loaded submission; do not query per row. --}}
@if($assignment->status === 'completed' && $assignment->submission)
    @php
        $geoOk = $assignment->submission->geofence_passed;
        $late = $assignment->submission->is_late;
    @endphp
    <div style="margin-top:4px;font-size:10px;line-height:1.4;color:{{ (!$geoOk || $late) ? 'var(--red)' : 'var(--g700)' }}">
        {{ $geoOk ? '✓ সঠিক লোকেশন' : '✕ লোকেশনের বাইরে' }}
        · {{ $late ? 'সময় পেরিয়েছে' : 'সময়ের মধ্যে' }}
    </div>
@endif
