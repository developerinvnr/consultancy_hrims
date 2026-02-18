<div class="table-responsive">
    <table class="table table-sm table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Field Changed</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Changed By</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse($candidate->editHistory ?? [] as $history)
            <tr>
                <td>{{ $history->created_at->format('d-M-Y H:i') }}</td>
                <td>{{ $history->field_name }}</td>
                <td class="text-muted">{{ Str::limit($history->old_value, 50) }}</td>
                <td class="text-primary">{{ Str::limit($history->new_value, 50) }}</td>
                <td>{{ $history->user->name ?? 'N/A' }}</td>
                <td>{{ $history->reason ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No edit history available</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>