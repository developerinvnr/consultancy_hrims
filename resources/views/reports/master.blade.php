@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Master Employee Report</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" onclick="exportReport()">
                        <i class="ri-file-excel-2-line"></i> Export Excel
                    </button>
                    {{--<button class="btn btn-sm btn-outline-secondary" onclick="toggleColumnVisibility()">
                        <i class="ri-eye-line"></i> Show/Hide Columns
                    </button>--}}
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('master') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Select Month-Year</label>
                    <input type="month" name="month_year" id="monthYear" class="form-control form-control-sm" 
                           value="{{ sprintf('%04d-%02d', $year, $month) }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Requisition Type</label>
                    <select name="requisition_type" class="form-select form-select-sm">
                        <option value="All" {{ $requisitionType == 'All' ? 'selected' : '' }}>All Types</option>
                        <option value="Contractual" {{ $requisitionType == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                        <option value="TFA" {{ $requisitionType == 'TFA' ? 'selected' : '' }}>TFA</option>
                        <option value="CB" {{ $requisitionType == 'CB' ? 'selected' : '' }}>CB</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Work Location</label>
                    <select name="work_location" class="form-select form-select-sm">
                        <option value="">All Locations</option>
                        @foreach($workLocations as $location)
                            <option value="{{ $location }}" {{ request('work_location') == $location ? 'selected' : '' }}>
                                {{ $location }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label form-label-sm">Quick Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search by name, code, PAN, Aadhaar..." 
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="ri-filter-line"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

  
    <!-- Report Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0" id="masterReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Party Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Work Location</th>
                            <th>Contract Start</th>
                            <th>Contract End</th>
                            <th>Monthly Salary</th>
                            {{--<th>Total Days</th>
                            <th>Paid Days</th>
                            <th>Absent Days</th>
                            <th>Extra Amount</th>
                            <th>Deduction Amount</th>
                            <th>Net Pay</th>--}}
                            <th>Bank Details</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($candidates as $index => $candidate)
                            @php
                                $salary = $candidate->salaryProcessings->first();
                            @endphp
                            <tr>
                                <td>{{ $candidates->firstItem() + $index }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $candidate->candidate_code }}</span>
                                </td>
                                <td>
                                    <div>{{ $candidate->candidate_name }}</div>
                                    <small class="text-muted">{{ $candidate->mobile_no }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $candidate->requisition_type }}</span>
                                </td>
                                <td>{{ $candidate->department->department_name ?? 'N/A' }}</td>
                                <td>{{ $candidate->requisition_type ?? 'N/A' }}</td>
                                <td>
                                    {{ $candidate->work_location_hq }}
                                </td>
                               
                                <td>{{ $candidate->contract_start_date ? date('d-m-Y', strtotime($candidate->contract_start_date)) : 'N/A' }}</td>
                                <td>{{ $candidate->contract_end_date ? date('d-m-Y', strtotime($candidate->contract_end_date)) : 'N/A' }}</td>
                                <td class="text-end">₹ {{ number_format($candidate->remuneration_per_month, 2) }}</td>
                                <!-- <td class="text-center">{{ $salary ? $salary->total_days : 0 }}</td>
                                <td class="text-center">{{ $salary ? $salary->paid_days : 0 }}</td>
                                <td class="text-center">{{ $salary ? $salary->absent_days : 0 }}</td>
                                <td class="text-end text-success">+ ₹ {{ number_format($salary ? $salary->extra_amount : 0, 2) }}</td>
                                <td class="text-end text-danger">- ₹ {{ number_format($salary ? $salary->deduction_amount : 0, 2) }}</td>
                                <td class="text-end fw-bold">₹ {{ number_format($salary ? $salary->net_pay : 0, 2) }}</td> -->
                                <td>
                                    <small>
                                        <div>{{ $candidate->bank_name }}</div>
                                        <div>Acc: {{ substr($candidate->bank_account_no, -4) }}****</div>
                                        <div>IFSC: {{ $candidate->bank_ifsc }}</div>
                                    </small>
                                </td>
                                <td>
                                    @if($salary)
                                        <span class="badge bg-success">Processed</span>
                                        <div class="text-muted small">
                                                    {{ optional(\Carbon\Carbon::parse($salary->processed_at))->format('d-m-Y') }}
                                        </div>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ri-inbox-line display-4"></i>
                                        <h5 class="mt-2">No employees found</h5>
                                        <p>Try adjusting your filters</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $candidates->firstItem() ?? 0 }} to {{ $candidates->lastItem() ?? 0 }} of {{ $candidates->total() }} entries
                </div>
                <div>
                    {{ $candidates->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="columnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Show/Hide Columns</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row" id="columnVisibilityList">
                    <!-- Columns will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyColumnVisibility()">Apply</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 48px;
        height: 48px;
    }
    
    .avatar-title {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        font-size: 20px;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .column-checkbox {
        margin-bottom: 10px;
    }
</style>
@endpush

@section('script_section')
<script>
    // Column visibility state
    const columnVisibility = {
        0: true,  // #
        1: true,  // Employee Code
        2: true,  // Name
        3: true,  // Type
        4: true,  // Department
        5: true,  // Designation
        6: true,  // Work Location
        7: true,  // Zone/Region
        8: true,  // Contract Start
        9: true,  // Contract End
        10: true, // Monthly Salary
        11: true, // Total Days
        12: true, // Paid Days
        13: true, // Absent Days
        14: true, // Extra Amount
        15: true, // Deduction Amount
        16: true, // Net Pay
        17: true, // Bank Details
        18: true  // Status
    };
    
    // Toggle column visibility modal
    function toggleColumnVisibility() {
        populateColumnVisibilityList();
        new bootstrap.Modal(document.getElementById('columnVisibilityModal')).show();
    }
    
    // Populate column visibility list
    function populateColumnVisibilityList() {
        const container = document.getElementById('columnVisibilityList');
        const columns = [
            'Serial Number', 'Employee Code', 'Name', 'Type', 'Department', 
            'Designation', 'Work Location','Contract Start', 
            'Contract End', 'Monthly Salary', 'Total Days', 'Paid Days', 
            'Absent Days', 'Extra Amount', 'Deduction Amount', 'Net Pay', 
            'Bank Details', 'Status'
        ];
        
        let html = '';
        columns.forEach((col, index) => {
            html += `
                <div class="col-md-6 col-lg-4 column-checkbox">
                    <div class="form-check">
                        <input class="form-check-input column-visibility-checkbox" 
                               type="checkbox" 
                               id="col${index}" 
                               data-index="${index}"
                               ${columnVisibility[index] ? 'checked' : ''}>
                        <label class="form-check-label" for="col${index}">
                            ${col}
                        </label>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // Apply column visibility changes
    function applyColumnVisibility() {
        const checkboxes = document.querySelectorAll('.column-visibility-checkbox');
        checkboxes.forEach(cb => {
            const index = parseInt(cb.dataset.index);
            columnVisibility[index] = cb.checked;
        });
        
        // Hide/show columns
        const table = document.getElementById('masterReportTable');
        const headers = table.querySelectorAll('thead th');
        const rows = table.querySelectorAll('tbody tr');
        
        headers.forEach((header, index) => {
            header.style.display = columnVisibility[index] ? '' : 'none';
        });
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                cell.style.display = columnVisibility[index] ? '' : 'none';
            });
        });
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('columnVisibilityModal')).hide();
    }
    
    // Export function
    function exportReport() {
        const monthYear = document.getElementById('monthYear').value;
        const requisitionType = document.querySelector('[name="requisition_type"]').value;
        const workLocation = document.querySelector('[name="work_location"]').value;
        const departmentId = document.querySelector('[name="department_id"]').value;
        const search = document.querySelector('[name="search"]').value;
        
        if (!monthYear) {
            toastr.error("Please select month-year first");
            return;
        }
        
        // Extract month and year from month-year input
        const [year, month] = monthYear.split('-');
        
        // Build export URL
        let url = `/master/export?month=${month}&year=${year}`;
        
        // Add filters
        if (requisitionType && requisitionType !== 'All') {
            url += `&requisition_type=${requisitionType}`;
        }
        if (workLocation) {
            url += `&work_location=${encodeURIComponent(workLocation)}`;
        }
        if (departmentId) {
            url += `&department_id=${departmentId}`;
        }
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }
        
        // Add CSRF token
        url += `&_token={{ csrf_token() }}`;
        
        // Show loading
        Swal.fire({
            title: 'Exporting Master Report',
            html: 'Please wait while we generate the Excel file...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Trigger download
        window.location.href = url;
        
        // Close loading after delay
        setTimeout(() => {
            Swal.close();
            toastr.success('Master report export started. Check your downloads.');
        }, 1000);
    }
    
    // Auto-submit form when main filters change
    document.getElementById('monthYear').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    document.querySelector('[name="requisition_type"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    
    // Debounced search
    let searchTimeout;
    document.querySelector('[name="search"]').addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length === 0 || this.value.length >= 2) {
                document.getElementById('filterForm').submit();
            }
        }, 500);
    });
    
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endsection