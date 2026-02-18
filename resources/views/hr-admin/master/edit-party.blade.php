@extends('layouts.guest')

@section('title', 'Edit Party - ' . $candidate->candidate_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-edit mr-2"></i>
                        Edit Party: {{ $candidate->candidate_name }} ({{ $candidate->candidate_code }})
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('hr-admin.master.index', ['type' => $candidate->requisition_type]) }}"
                            class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Master
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('hr-admin.update-party', $candidate) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="editPartyForm">
                        @csrf
                        @method('PUT')

                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs" id="editPartyTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="personal-tab" data-bs-toggle="tab" href="#personal" role="tab">
                                    <i class="fas fa-user"></i> Personal Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="work-tab" data-bs-toggle="tab" href="#work" role="tab">
                                    <i class="fas fa-briefcase"></i> Work Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="bank-tab" data-bs-toggle="tab" href="#bank" role="tab">
                                    <i class="fas fa-university"></i> Bank Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#documents" role="tab">
                                    <i class="fas fa-file-alt"></i> Documents & Agreements
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="reporting-tab" data-bs-toggle="tab" href="#reporting" role="tab">
                                    <i class="fas fa-sitemap"></i> Reporting Changes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab">
                                    <i class="fas fa-history"></i> Edit History
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content p-3 border border-top-0 rounded-bottom" style="background: #fff;">

                            <!-- Personal Info Tab -->
                            <div class="tab-pane active" id="personal" role="tabpanel">
                                @include('hr-admin.master.edit-partials.personal-info', ['candidate' => $candidate])
                            </div>

                            <!-- Work Details Tab -->
                            <div class="tab-pane" id="work" role="tabpanel">
                                @include('hr-admin.master.edit-partials.work-details', ['candidate' => $candidate])
                            </div>

                            <!-- Bank Details Tab -->
                            <div class="tab-pane" id="bank" role="tabpanel">
                                @include('hr-admin.master.edit-partials.bank-details', ['candidate' => $candidate])
                            </div>

                            <!-- Documents Tab -->
                            <div class="tab-pane" id="documents" role="tabpanel">
                                @include('hr-admin.master.edit-partials.documents', ['candidate' => $candidate])
                            </div>

                            <!-- Reporting Changes Tab -->
                            <div class="tab-pane" id="reporting" role="tabpanel">
                                @include('hr-admin.master.edit-partials.reporting', ['candidate' => $candidate])
                            </div>

                            <!-- History Tab -->
                            <div class="tab-pane" id="history" role="tabpanel">
                                @include('hr-admin.master.edit-partials.history', ['candidate' => $candidate])
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 text-right">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Update Party Details
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script_section')
<script>
    $(document).ready(function() {
        // File input label update
        $(document).on('change', '.custom-file-input', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
        // Form validation
        $('#editPartyForm').validate({
            rules: {
                candidate_name: {
                    required: true
                },
                candidate_email: {
                    email: true // no required here
                },
                mobile_no: {
                    digits: true,
                    minlength: 10,
                    maxlength: 10
                },
                contract_start_date: {
                    date: true
                },
                contract_end_date: {
                    date: true
                }
            }
        });


        // Show confirmation before submitting
        $('#submitBtn').click(function(e) {
            if ($('#editPartyForm').valid()) {
                return confirm('Are you sure you want to update this party\'s details? Changes will be logged.');
            }
        });
    });
</script>
@endsection