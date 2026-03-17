
@extends('layouts.guest')
    @section('content')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">TAT Report (Action Wise)</h4>
                <a href="{{ route('reports.tat.export', request()->query()) }}"
                    class="btn btn-sm btn-success">
                    Export Excel
                </a>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="card mb-2 shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.tat') }}" class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label form-label-sm">Financial Year</label>
                                <select name="financial_year" class="form-select form-select-sm">
                                    <option value="2024-2025" {{ $financialYear=='2024-2025'?'selected':'' }}>2024-2025</option>
                                    <option value="2025-2026" {{ $financialYear=='2025-2026'?'selected':'' }}>2025-2026</option>
                                </select>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label form-label-sm">Month</label>
                                <select name="month" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="4" {{ $month==4?'selected':'' }}>Apr</option>
                                    <option value="5" {{ $month==5?'selected':'' }}>May</option>
                                    <option value="6" {{ $month==6?'selected':'' }}>Jun</option>
                                    <option value="7" {{ $month==7?'selected':'' }}>Jul</option>
                                    <option value="8" {{ $month==8?'selected':'' }}>Aug</option>
                                    <option value="9" {{ $month==9?'selected':'' }}>Sep</option>
                                    <option value="10" {{ $month==10?'selected':'' }}>Oct</option>
                                    <option value="11" {{ $month==11?'selected':'' }}>Nov</option>
                                    <option value="12" {{ $month==12?'selected':'' }}>Dec</option>
                                    <option value="1" {{ $month==1?'selected':'' }}>Jan</option>
                                    <option value="2" {{ $month==2?'selected':'' }}>Feb</option>
                                    <option value="3" {{ $month==3?'selected':'' }}>Mar</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label form-label-sm">Department</label>
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ $departmentId==$dept->id?'selected':'' }}>
                                        {{ $dept->department_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label form-label-sm">Requisition Type</label>
                                <select name="requisition_type" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="Contractual" {{ $requisitionType=='Contractual'?'selected':'' }}>Contractual</option>
                                    <option value="TFA" {{ $requisitionType=='TFA'?'selected':'' }}>TFA</option>
                                    <option value="CB" {{ $requisitionType=='CB'?'selected':'' }}>CB</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label form-label-sm">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="Pending HR Verification">Pending HR Verification</option>
                                    <option value="Pending Approval">Pending Approval</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-sm btn-primary w-100">
                                    Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row mb-3">
                    @foreach($summaries as $key => $s)
                    <div class="col-md-4 mb-2">
                        <div class="card shadow-sm border-start border-primary border-3">
                            <div class="card-body p-2">
                                <h6 class="text-primary text-capitalize">{{ str_replace('_',' ', $key) }}</h6>
                                <small>Total: {{ $s['total'] }}</small><br>
                                <small>Avg: {{ $s['avg'] }} Days</small><br>
                                <small>≤1 Day: {{ $s['within_1'] }}</small><br>
                                <small>1-3 Days: {{ $s['within_3'] }}</small><br>
                                <small>>3 Days: {{ $s['above_3'] }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Req ID</th>
                                <th>Candidate</th>
                                <th>Submission</th>

                                {{-- Stage Dates --}}
                                @foreach($stages as $key => $s)
                                <th>{{ ucfirst(str_replace('_',' ', $key)) }}</th>
                                @endforeach

                                {{-- Stage TAT --}}
                                @foreach($stages as $key => $s)
                                <th>{{ ucfirst(str_replace('_',' ', $key)) }} TAT</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $index => $row)
                            <tr>
                                <td>{{ $records->firstItem() + $index }}</td>

                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $row->requisition_id }}
                                    </span>
                                </td>

                                <td>{{ $row->candidate_name }}</td>

                                <td>
                                    {{ $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('d-M-Y') : '-' }}
                                </td>

                                {{-- ✅ Stage Dates --}}
                                @foreach($stages as $key => $s)
                                <td>
                                    {{ $row->{$s['to']} ? \Carbon\Carbon::parse($row->{$s['to']})->format('d-M-Y') : '-' }}
                                </td>
                                @endforeach

                                {{-- ✅ Stage TAT --}}
                                @foreach($stages as $key => $s)
                                @php
                                $tat = ($row->{$s['from']} && $row->{$s['to']})
                                ? \Carbon\Carbon::parse($row->{$s['from']})
                                ->diffInDays($row->{$s['to']})
                                : null;
                                @endphp

                                <td class="text-center">
                                    @if($tat !== null)
                                    <span class="badge 
                        @if($tat <= 1) bg-success
                        @elseif($tat <= 3) bg-warning
                        @else bg-danger
                        @endif
                    ">
                                        {{ $tat < 1 ? 'Within 1 Day' : round($tat).' Days' }}
                                    </span>
                                    @else
                                    -
                                    @endif
                                </td>
                                @endforeach

                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $records->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
 @endsection