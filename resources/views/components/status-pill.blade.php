@props([
    'status' => 'active',
    'text' => null,
])

@php
    $class = '';
    $label = $text;

    switch ($status) {
        case 'active':
        case 'completed':
        case 'পাস':
        case 'চালু':
        case 'অনুমোদিত':
            $class = '';
            $label = $label ?? ($status === 'active' ? 'সক্রিয়' : 'সম্পন্ন');
            break;
        case 'in_progress':
        case 'চলমান':
            $class = 'blue';
            $label = $label ?? 'চলমান';
            break;
        case 'pending':
        case 'অপেক্ষমাণ':
            $class = 'amber';
            $label = $label ?? 'অপেক্ষমাণ';
            break;
        case 'late':
        case 'দেরি':
        case 'inactive':
        case 'নিষ্ক্রিয়':
        case 'danger':
            $class = 'red';
            $label = $label ?? ($status === 'late' ? 'দেরি' : 'নিষ্ক্রিয়');
            break;
        default:
            $class = '';
            $label = $label ?? $status;
    }
@endphp

<span class="pill {{ $class }}">
    <span class="dot"></span>{{ $label }}
</span>
