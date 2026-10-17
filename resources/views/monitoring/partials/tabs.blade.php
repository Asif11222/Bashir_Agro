<div class="tabs">
    <a href="{{ route('admin.monitoring', array_filter(['search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" 
       data-status="" 
       class="tab {{ !$status ? 'active' : '' }}">
        সব ({{ $scopedTotalCount }})
    </a>
    <a href="{{ route('admin.monitoring', array_filter(['status' => 'in_progress', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" 
       data-status="in_progress" 
       class="tab {{ $status === 'in_progress' ? 'active' : '' }}">
        চলমান ({{ $inProgressCount }})
    </a>
    <a href="{{ route('admin.monitoring', array_filter(['status' => 'assigned', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" 
       data-status="assigned" 
       class="tab {{ $status === 'assigned' ? 'active' : '' }}">
        বরাদ্দকৃত ({{ $assignedCount }})
    </a>
    <a href="{{ route('admin.monitoring', array_filter(['status' => 'completed', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" 
       data-status="completed" 
       class="tab {{ $status === 'completed' ? 'active' : '' }}">
        সম্পন্ন ({{ $completedCount }})
    </a>
    @if($lateCount > 0)
    <a href="{{ route('admin.monitoring', array_filter(['status' => 'late', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" 
       data-status="late" 
       class="tab {{ $status === 'late' ? 'active' : '' }}">
        দেরি ({{ $lateCount }})
    </a>
    @endif
    <a href="{{ route('admin.monitoring', array_filter(['status' => 'cancelled', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" 
       data-status="cancelled" 
       class="tab {{ $status === 'cancelled' ? 'active' : '' }}">
        বাতিল ({{ $cancelledCount }})
    </a>
</div>
