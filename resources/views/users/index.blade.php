@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Users</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">User Management</a></li>
                        <li class="breadcrumb-item active">User List</li>
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
                            <h5 class="card-title mb-0">User Management</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <!-- Search Form -->
                                <form method="GET" action="{{ route('users.index') }}" class="d-flex me-2">
                                    <input type="text" name="search" class="form-control form-control-sm me-2"
                                        placeholder="Search..." value="{{ request('search') }}">
                                    <select name="status" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="">All Status</option>
                                        <option value="A" {{ request('status') == 'A' ? 'selected' : '' }}>Active</option>
                                        <option value="P" {{ request('status') == 'P' ? 'selected' : '' }}>Pending</option>
                                        <option value="D" {{ request('status') == 'D' ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm me-2">
                                        <i class="ri-search-line"></i>
                                    </button>
                                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </form>

                                <button class="btn btn-success btn-sm export-users" title="Export to Excel">
                                    <i class="ri-file-excel-2-line me-1"></i>
                                </button>
                                <button class="btn btn-primary btn-sm add-user" title="Add New User">
                                    <i class="ri-add-line me-1"></i> Add User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100" id="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Roles</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->status === 'A')
                                        <span class="badge bg-success">Active</span>
                                        @elseif($user->status === 'P')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($user->status === 'D')
                                        <span class="badge bg-danger text-dark">Disabled</span>
                                        @else
                                        <span>-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->roles && $user->roles->count() > 0)
                                        @foreach($user->roles as $role)
                                        <span class="badge bg-primary">{{ $role->name }}</span>
                                        @endforeach
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-xs btn-info edit-user" data-id="{{ $user->id }}" title="Edit">
                                                <i class="bx bx-pencil fs-8"></i>
                                            </button>
                                            <button class="btn btn-xs btn-warning change-password" data-id="{{ $user->id }}" title="Change Password">
                                                <i class="ri-lock-password-line fs-14"></i>
                                            </button>
                                            <a href="{{ url('/user/' . $user->id . '/permission') }}"
                                                class="btn btn-xs btn-secondary d-inline-flex align-items-center justify-content-center"
                                                title="Manage Permissions">
                                                <i class="ri-shield-keyhole-line fs-14"></i>
                                            </a>
                                            {{--<button class="btn btn-xs btn-danger delete-user" data-id="{{ $user->id }}" title="Delete">
                                                <i class="bx bx-trash fs-14"></i>
                                            </button>--}}
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No users found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} entries
                        </div>
                        <div>
                            {{ $users->appends(request()->except('page'))->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="user-form" action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Select Employee</label>
                            <div class="col-sm-8">
                                <select name="user_name" id="user_name" class="form-select form-select-sm" style="width: 100%">
                                    <option value="">Select Employee</option>
                                    @foreach ($employee_list as $employee)
                                    <option value="{{ $employee->emp_name }}"
                                        data-emp_id="{{ $employee->employee_id }}"
                                        data-emp_code="{{ $employee->emp_code }}"
                                        data-email="{{ $employee->emp_email }}"
                                        data-department="{{ $employee->emp_department }}"
                                        data-reporting_id="{{ $employee->emp_reporting ?? 0 }}">
                                        {{ $employee->emp_name }} - {{ $employee->emp_code }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="user_name_error"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Name</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                                <input type="hidden" name="emp_id" id="emp_id">
                                <input type="hidden" name="reporting_id" id="reporting_id" value="0">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Email</label>
                            <div class="col-sm-8">
                                <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Emp Code</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="emp_code" name="emp_code" readonly>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Department</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="department" readonly>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Status</label>
                            <div class="col-sm-8">
                                <select class="form-select form-control-sm" id="user_status" name="user_status" required>
                                    <option value="A">Active</option>
                                    <option value="P">Pending</option>
                                    <option value="D">Disabled</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Roles</label>
                            <div class="col-sm-8">
                                <select class="form-select select2 form-select-sm" id="roles" name="roles[]" multiple style="width: 100%">
                                    @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Password</label>
                            <div class="col-sm-8">
                                <input type="password" class="form-control form-control-sm" id="password" name="password" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Confirm Password</label>
                            <div class="col-sm-8">
                                <input type="password" class="form-control form-control-sm" id="password_confirmation" name="password_confirmation" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="save-btn">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-user-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Name</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="edit_name" name="name" required readonly>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Email</label>
                            <div class="col-sm-8">
                                <input type="email" class="form-control form-control-sm" id="edit_email" name="email" required readonly>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Status</label>
                            <div class="col-sm-8">
                                <select class="form-select form-control-sm" id="edit_user_status" name="user_status" required>
                                    <option value="A">Active</option>
                                    <option value="P">Pending</option>
                                    <option value="D">Disabled</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Roles</label>
                            <div class="col-sm-8">
                                <select class="form-select select2 form-control-sm" id="edit_roles" name="roles[]" multiple style="width: 100%">
                                    @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">New Password (Optional)</label>
                            <div class="col-sm-8">
                                <input type="password" class="form-control form-control-sm" id="edit_password" name="password">
                                <small class="text-muted">Leave blank to keep current password</small>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Confirm Password</label>
                            <div class="col-sm-8">
                                <input type="password" class="form-control form-control-sm" id="edit_password_confirmation" name="password_confirmation">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="update-btn">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="change-password-form" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" id="password_user_id">
                    <div class="modal-body">
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">New Password</label>
                            <div class="col-sm-8">
                                <input type="password" class="form-control form-control-sm" id="new_password" name="password" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-4 col-form-label">Confirm Password</label>
                            <div class="col-sm-8">
                                <input type="password" class="form-control form-control-sm" id="password_confirmation" name="password_confirmation" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirm-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



@section('script_section')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: 'Select roles',
            allowClear: true,
            width: '100%',
                        theme: 'bootstrap-5'

        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Employee selection handler for Add User modal
        $('#user_name').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#name').val(selectedOption.val());
                $('#emp_id').val(selectedOption.data('emp_id'));
                $('#email').val(selectedOption.data('email'));
                $('#emp_code').val(selectedOption.data('emp_code'));
                $('#department').val(selectedOption.data('department'));
                $('#reporting_id').val(selectedOption.data('reporting_id') || 0);
                // Clear any previous errors
                $(this).removeClass('is-invalid');
                $('#user_name_error').text('').hide();
            } else {
                $('#name').val('');
                $('#emp_id').val('');
                $('#email').val('');
                $('#emp_code').val('');
                $('#department').val('');
            }
        });

        // Add user button click handler
        // Add user button click handler - Update this function
        $('.add-user').click(function() {
            const form = $('#user-form');
            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();
            $('#roles').val(null).trigger('change');
            $('#user_name').val('').trigger('change');
            $('#userModalLabel').text('Add New User');
            form.attr('action', '{{ route("users.store") }}');
            $('#save-btn').text('Save User');

            // Destroy and reinitialize Select2
            $('#roles').select2('destroy');
            $('#roles').select2({
                placeholder: 'Select roles',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#userModal') // Important: Set dropdown parent to modal
            });

            $('#userModal').modal('show');
        });

        // Edit user button click handler
        $(document).on('click', '.edit-user', function() {
            const userId = $(this).data('id');
            const form = $('#edit-user-form');

            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();
            $('#edit_roles').val(null).trigger('change');
            $('#edit_password').val('');
            $('#edit_password_confirmation').val('');

            $('#editUserModalLabel').text('Edit User');
            form.attr('action', '{{ url("users") }}/' + userId);
            $('#update-btn').text('Update User');

            $.ajax({
                url: "{{ url('users') }}/" + userId + "/edit",
                type: 'GET',
                success: function(response) {
                    const userData = response.data || response;
                    if (userData && (userData.user || userData.id)) {
                        const user = userData.user || userData;
                        $('#edit_name').val(user.name || '');
                        $('#edit_email').val(user.email || '');
                        $('#edit_user_status').val(user.status || 'A');

                        if (userData.userRoles) {
                            $('#edit_roles').val(userData.userRoles).trigger('change');
                        }
                        $('#editUserModal').modal('show');
                    } else {
                        console.error('Invalid user data structure:', userData);
                        alert('Invalid user data received');
                    }
                },
                error: function(xhr) {
                    console.error('Edit AJAX error:', xhr.responseText, xhr.status);
                    alert('Error loading user data: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        });

        // Change password button click handler
        $(document).on('click', '.change-password', function() {
            const userId = $(this).data('id');
            const form = $('#change-password-form');

            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();

            $('#password_user_id').val(userId);
            form.attr('action', '{{ url("users") }}/' + userId + '/password');
            $('#changePasswordModal').modal('show');
        });

        // Form submission handler for add user form
        $('#user-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            const method = 'POST';

            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();

            $.ajax({
                url: url,
                type: method,
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#userModal').modal('hide');
                        // Reload the page to show updated data
                        window.location.reload();
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Submit AJAX error:', xhr.responseJSON);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            let input = form.find(`[name="${field}"]`);
                            if (input.length === 0) {
                                input = form.find(`[name="${field}[]"]`);
                            }
                            if (input.length > 0) {
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(messages[0]).show();
                            }
                        });
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });

        // Form submission handler for edit user form
        $('#edit-user-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            const method = 'PUT';

            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();

            $.ajax({
                url: url,
                type: method,
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#editUserModal').modal('hide');
                        // Reload the page to show updated data
                        window.location.reload();
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Submit AJAX error:', xhr.responseJSON);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            let input = form.find(`[name="${field}"]`);
                            if (input.length === 0) {
                                input = form.find(`[name="${field}[]"]`);
                            }
                            if (input.length > 0) {
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(messages[0]).show();
                            }
                        });
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });

        // Form submission handler for password change form
        $('#change-password-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            const method = 'PUT';

            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();

            $.ajax({
                url: url,
                type: method,
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#changePasswordModal').modal('hide');
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Password change AJAX error:', xhr.responseJSON);
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

        // Delete user button click handler
        let deleteUserId;
        $(document).on('click', '.delete-user', function() {
            deleteUserId = $(this).data('id');
            $('#deleteModal').modal('show');
        });

        // Confirm delete button click handler
        $('#confirm-delete').click(function() {
            $.ajax({
                url: "{{ url('users') }}/" + deleteUserId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        // Reload the page to show updated data
                        window.location.reload();
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Delete AJAX error:', xhr.responseText);
                    alert('Error deleting user');
                }
            });
        });

        // Export button click handler
        $(document).on('click', '.export-users', function() {
            const filters = {
                search: $('input[name="search"]').val() || '',
                status: $('select[name="status"]').val() || ''
            };

            const form = $('<form>', {
                action: "{{ route('users.export') }}",
                method: 'POST',
            }).appendTo('body');

            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));

            $.each(filters, function(key, value) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: value
                }));
            });

            form.submit();
            form.remove();
        });
    });
</script>
@endsection