@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle"></i> Import Results</h4>
                </div>
                <div class="card-body">
                    @if(isset($results) && !empty($results))
                        <div class="alert alert-info">
                            <h5>Import Summary</h5>
                            <p><strong>Successfully Imported:</strong> {{ $results['success'] ?? 0 }} candidates</p>
                            <p><strong>Failed:</strong> {{ $results['failed'] ?? 0 }} candidates</p>
                        </div>
                        
                        @if(!empty($importedCandidates))
                            <h5>Imported Candidates</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Candidate Code</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Requisition ID</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($importedCandidates as $candidate)
                                            <tr>
                                                <td>{{ $candidate['candidate_code'] ?? 'N/A' }}</td>
                                                <td>{{ $candidate['candidate_name'] ?? 'N/A' }}</td>
                                                <td>{{ $candidate['candidate_email'] ?? 'N/A' }}</td>
                                                <td>{{ $candidate['mobile_no'] ?? 'N/A' }}</td>
                                                <td>{{ $candidate['requisition_id'] ?? 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ route('import.candidates') }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-upload"></i> Upload Documents
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        
                        @if(!empty($results['errors']))
                            <h5>Errors</h5>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($results['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="mt-3">
                            <a href="{{ route('import.candidates') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to Import
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Go to Dashboard
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <p>No import results found. Please start a new import.</p>
                        </div>
                        <a href="{{ route('import.candidates') }}" class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Start New Import
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection