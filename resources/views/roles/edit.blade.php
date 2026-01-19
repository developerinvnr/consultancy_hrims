@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Edit Role</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">Edit Role</li>
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
                            <h5 class="card-title mb-0">Edit Role: <span class="text-primary">{{ $role->name }}</span></h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ri-arrow-left-line me-1"></i> Back to Roles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form id="role-form" method="POST" action="{{ route('roles.update', $role->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" 
                                           id="role_name" name="role_name" 
                                           value="{{ old('role_name', $role->name) }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="guard_name" class="form-label">Guard Name <span class="text-danger">*</span></label>
                                    <select class="form-select form-control-sm" id="guard_name" name="guard_name" required>
                                        <option value="web" {{ $role->guard_name == 'web' ? 'selected' : '' }}>web</option>
                                        <option value="api" {{ $role->guard_name == 'api' ? 'selected' : '' }}>api</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Permissions <span class="text-danger">*</span></h6>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="select-all">
                                            <i class="ri-checkbox-circle-line me-1"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="deselect-all">
                                            <i class="ri-checkbox-blank-circle-line me-1"></i> Deselect All
                                        </button>
                                    </div>
                                </div>
                                <div class="alert alert-danger d-none" id="permission-error">
                                    Please select at least one permission.
                                </div>
                                
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
                                                           name="permission[]" 
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
                                            No permissions found. Please run the permission seeder first.
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm">Cancel</a>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-save-line me-1"></i> Update Role
                                    </button>
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
        // Initialize group checkboxes based on permissions
        function initGroupCheckboxes() {
            $('.group-checkbox').each(function() {
                const groupClass = $(this).data('group');
                const allChecked = $(`.permission-checkbox.${groupClass}:checked`).length === 
                                  $(`.permission-checkbox.${groupClass}`).length;
                $(this).prop('checked', allChecked);
            });
        }
        
        // Call on page load
        initGroupCheckboxes();
        validatePermissions();

        // Group checkbox functionality
        $('.group-checkbox').on('change', function() {
            const groupClass = $(this).data('group');
            const isChecked = $(this).prop('checked');
            
            $(`.permission-checkbox.${groupClass}`).prop('checked', isChecked);
            validatePermissions();
        });

        // Permission checkbox functionality
        $('.permission-checkbox').on('change', function() {
            const groupClass = $(this).attr('class').split(' ').find(c => c.startsWith('group-'));
            const allChecked = $(`.permission-checkbox.${groupClass}:checked`).length === 
                              $(`.permission-checkbox.${groupClass}`).length;
            
            $(`#group-${groupClass.split('-')[1]}`).prop('checked', allChecked);
            validatePermissions();
        });

        // Select all button
        $('#select-all').click(function() {
            $('input[type="checkbox"]').prop('checked', true);
            validatePermissions();
        });

        // Deselect all button
        $('#deselect-all').click(function() {
            $('input[type="checkbox"]').prop('checked', false);
            validatePermissions();
        });

        // Validate at least one permission is selected
        function validatePermissions() {
            const hasPermission = $('input[name="permission[]"]:checked').length > 0;
            if (!hasPermission) {
                $('#permission-error').removeClass('d-none');
                return false;
            } else {
                $('#permission-error').addClass('d-none');
                return true;
            }
        }

        // Form submission handler
        $('#role-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const url = form.attr('action');
            
            // Validate permissions
            if (!validatePermissions()) {
                return;
            }
            
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();
            
            // Submit form via AJAX
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.href = response.redirect;
                    }
                },
                error: function(xhr) {
                    console.error('Submit AJAX error:', xhr.responseJSON);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            const input = form.find(`[name="${field}"]`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(messages[0]).show();
                        });
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });
    });
</script>
@endsection