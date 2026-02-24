{{-- resources/views/reports/partials/attendance-filter.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Attendance Report - Coming Soon
        </div>
        <form method="GET" action="#" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Month</label>
                        <select name="month" class="form-control">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year" class="form-control">
                            @foreach(range(date('Y') - 2, date('Y') + 1) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary" disabled>
                                <i class="fas fa-search"></i> Generate Report
                            </button>
                            <button type="button" class="btn btn-success" disabled>
                                <i class="fas fa-file-excel"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>