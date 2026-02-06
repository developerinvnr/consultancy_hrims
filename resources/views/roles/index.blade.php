@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Roles</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">User Management</a></li>
                        <li class="breadcrumb-item active">Roles List</li>
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
                            <h5 class="card-title mb-0">Role Management</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <!-- Search Form -->
                                <form method="GET" action="{{ route('roles.index') }}" class="d-flex me-2">
                                    <input type="text" name="search" class="form-control form-control-sm me-2" 
                                           placeholder="Search..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary btn-sm me-2">
                                        <i class="ri-search-line"></i>
                                    </button>
                                    <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </form>
                                
                                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i> Add Role
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100" id="role-table">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="25%">Role Name</th>
                                    <th width="15%">Guard Name</th>
                                    <th width="15%">Permissions Count</th>
                                    <th width="15%">Created At</th>
                                    <th width="25%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                <tr>
                                    <td>{{ $role->id }}</td>
                                    <td>
                                        <strong>{{ $role->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $role->guard_name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $role->permissions_count }}</span>
                                    </td>
                                    <td>{{ optional($role->created_at)->format('Y-m-d H:i') ?? '-' }}</td>

                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('roles.edit', $role->id) }}" 
                                               class="btn btn-xs btn-info" title="Edit">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                            <a href="{{ route('roles.permissions', $role->id) }}" 
                                               class="btn btn-xs btn-warning d-inline-flex align-items-center justify-content-center" 
                                               title="Manage Permissions">
                                                <i class="ri-shield-keyhole-line"></i>
                                            </a>
                                            <button class="btn btn-xs btn-danger delete-role" 
                                                    data-id="{{ $role->id }}" 
                                                    data-name="{{ $role->name }}"
                                                    title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No roles found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }} entries
                        </div>
                        <div>
                            {{ $roles->appends(request()->except('page'))->links() }}
                        </div>
                    </div>
                </div>
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
                    <p>Are you sure you want to delete the role <strong id="role-name"></strong>?</p>
                    <p class="text-danger mb-0" id="delete-message"></p>
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
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Delete role button click handler
        let deleteRoleId;
        $(document).on('click', '.delete-role', function() {
            deleteRoleId = $(this).data('id');
            const roleName = $(this).data('name');
            $('#role-name').text(roleName);
            $('#delete-message').text('');
            $('#deleteModal').modal('show');
        });

        // Confirm delete button click handler
        $('#confirm-delete').click(function() {
            $.ajax({
                url: "{{ url('roles') }}/" + deleteRoleId,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        // Reload the page to show updated data
                        window.location.reload();
                        alert(response.message);
                    } else {
                        $('#delete-message').text(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Delete AJAX error:', xhr.responseText);
                    alert('Error deleting role');
                }
            });
        });
    });
</script>
@endsection