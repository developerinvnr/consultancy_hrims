<div class="table-responsive">
<table class="table table-bordered table-sm">

<thead>
<tr>
<th>Party Code</th>
<th>Party Name</th>
<th>Contract End Date</th>
<th>Days Left</th>
</tr>
</thead>

<tbody>

@if($list->count())

@foreach($list as $c)

<tr>
<td>{{ $c->candidate_code }}</td>
<td>{{ $c->candidate_name }}</td>
<td>{{ \Carbon\Carbon::parse($c->contract_end_date)->format('d-M-Y') }}</td>
<td>{{ ceil(now()->diffInDays($c->contract_end_date)) }}</td>
</tr>

@endforeach

@else

<tr>
<td colspan="4" class="text-center text-muted">
No contracts expiring
</td>
</tr>

@endif

</tbody>

</table>
@if($list instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $list->appends(request()->query())->links('pagination::bootstrap-5') }}
@endif
</div>