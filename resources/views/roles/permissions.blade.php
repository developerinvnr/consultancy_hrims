@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Role Permissions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">{{ $role->name }} - Permissions</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- End page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">Manage Permissions for: 
                                <span class="badge bg-primary">{{ $role->name }}</span>
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ri-arrow-left-line me-1"></i> Back to Roles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('roles.permissions.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            @forelse($groupedPermissions as $groupName => $permissions)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <div class="form-check">
                                            <input class="form-check-input group-checkbox" 
                                                   type="checkbox" 
                                                   id="group-{{ $loop->index }}"
                                                   data-group="group-{{ $loop->index }}">
                                            <label class="form-check-label fw-semibold" for="group-{{ $loop->index }}">
                                                {{ ucfirst($groupName) }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        @foreach($permissions as $permission)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input permission-checkbox group-{{ $loop->parent->index }}"
                                                   type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->id }}"
                                                   id="permission-{{ $permission->id }}"
                                                   {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-12">
                                <div class="alert alert-info">
                                    No permissions found. Please create permissions first.
                                </div>
                            </div>
                            @endforelse
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="select-all">
                                            <i class="ri-checkbox-circle-line me-1"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="deselect-all">
                                            <i class="ri-checkbox-blank-circle-line me-1"></i> Deselect All
                                        </button>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="ri-save-line me-1"></i> Update Permissions
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header .form-check {
        margin-bottom: 0;
    }
    
    .form-check-label {
        font-size: 0.85rem;
        cursor: pointer;
    }
    
    .permission-checkbox {
        margin-right: 0.5rem;
    }
    
    .group-checkbox {
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('script_section')
<script>
    $(document).ready(function() {
        // Group checkbox functionality
        $('.group-checkbox').on('change', function() {
            const groupClass = $(this).data('group');
            const isChecked = $(this).prop('checked');
            
            $(`.permission-checkbox.${groupClass}`).prop('checked', isChecked);
        });

        // Permission checkbox functionality
        $('.permission-checkbox').on('change', function() {
            const groupClass = $(this).attr('class').split(' ').find(c => c.startsWith('group-'));
            const allChecked = $(`.permission-checkbox.${groupClass}:checked`).length === 
                              $(`.permission-checkbox.${groupClass}`).length;
            
            $(`#group-${groupClass.split('-')[1]}`).prop('checked', allChecked);
        });

        // Select all button
        $('#select-all').click(function() {
            $('input[type="checkbox"]').prop('checked', true);
        });

        // Deselect all button
        $('#deselect-all').click(function() {
            $('input[type="checkbox"]').prop('checked', false);
        });
    });
</script>
@endsection