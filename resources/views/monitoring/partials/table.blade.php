<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>কৃষক</th>
                <th>কাজ</th>
                <th>লোকেশন</th>
                <th>সময়</th>
                <th>অগ্রাধিকার</th>
                <th>অবস্থা</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>
                        <b>{{ $assignment->farmer->full_name ?? '—' }}</b>
                        <br><small style="color:var(--muted)">{{ $assignment->farmer->farmer_code ?? '' }}</small>
                    </td>
                    <td>
                        <b>{{ $assignment->taskTemplate->name ?? '—' }}</b>
                        <br><small style="color:var(--g700)">{{ $assignment->taskTemplate->category ?? '' }}</small>
                    </td>
                    <td>
                        <span>{{ $assignment->location->name ?? '—' }}</span>
                    </td>
                    <td>
                        <b>{{ substr($assignment->start_time, 0, 5) }}</b>
                        @if($assignment->end_time)
                            - {{ substr($assignment->end_time, 0, 5) }}
                        @endif
                        <br><small style="color:var(--muted)">{{ $assignment->scheduled_date ? $assignment->scheduled_date->format('d/m/Y') : '' }}</small>
                    </td>
                    <td>
                        <span class="pill {{ $assignment->priority_badge_class }}">
                            <span class="dot"></span>{{ $assignment->priority_label }}
                        </span>
                    </td>
                    <td>
                        @php
                            $stClass = match($assignment->status) {
                                'completed' => '',
                                'in_progress' => 'blue',
                                'late', 'cancelled' => 'red',
                                default => 'blue',
                            };
                        @endphp
                        <span class="pill {{ $stClass }}">
                            <span class="dot"></span>{{ $assignment->status_label }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn" style="padding:4px 8px;font-size:11px">
                            দেখুন
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">
                        কোনো অপারেশনের তথ্য খুঁজে পাওয়া যায়নি।
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($assignments->hasPages())
    <div style="margin-top:16px;" class="pagination-wrapper">
        {{ $assignments->links() }}
    </div>
@endif
