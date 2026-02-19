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
                        <input type="hidden" name="active_tab" id="active_tab" value="">


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
@push('scripts')
<script>
$(document).ready(function () {

    console.log("Script Loaded");

    // Get active tab inside THIS form only
    let activePane = $('#editPartyForm .tab-pane.active');

    if (activePane.length) {
        $('#active_tab').val(activePane.attr('id'));
        console.log("Initial tab:", activePane.attr('id'));
    }

    // When tab changes (Bootstrap 5)
    $('#editPartyTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        let tabId = $(e.target).attr('href').replace('#', '');
        $('#active_tab').val(tabId);
        console.log("Tab switched to:", tabId);
    });

});
</script>
@endpush


