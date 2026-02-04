@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Communication Controls</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pages</a></li>
                        <li class="breadcrumb-item active">Communication Controls</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
					<h5>Communication Controls</h5>
					{{--<div class="d-flex">
                        <div class="">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createControlModal">
                                Add New Control
                            </button>
                        </div>
                    </div>--}}
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Toggle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($controls as $control)
                                <tr>
                                    <td>{{ $control->control_key }}</td>
                                    <td>{{ $control->description ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $control->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $control->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('communication.toggle', $control->id) }}" method="POST" style="display: inline;" class="toggle-form">
                                            @csrf
                                            <div class="form-check form-switch">
                                                <input type="checkbox"
                                                    class="form-check-input toggle-switch"
                                                    id="toggle-{{ $control->id }}"
                                                    {{ $control->is_active ? 'checked' : '' }}
                                                    style="transform: scale(1.5); margin-right: 8px;">
                                                <label class="form-check-label" for="toggle-{{ $control->id }}" style="font-size: 1.1rem;"></label>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-muted">No communication controls found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Control Modal -->
    <div class="modal fade" id="createControlModal" tabindex="-1" aria-labelledby="createControlModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('communication.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createControlModalLabel">Add New Communication Control</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="key" class="form-label">Key *</label>
                            <input type="text" name="key" id="key" class="form-control form-control-sm" required maxlength="100">
                            @error('key')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control form-control-sm" rows="4" maxlength="255"></textarea>
                            @error('description')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_section')
<script>
    $(document).ready(function() {
        // Reset modal form when closed
        $('#createControlModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $(this).find('.text-danger').remove();
        });

        // Handle toggle switch
        $('.toggle-switch').on('change', function() {
            const $switch = $(this);
            const $form = $switch.closest('form');
            const $row = $switch.closest('tr');
            const $badge = $row.find('.badge');
            const action = $switch.is(':checked') ? 'activate' : 'deactivate';

            // Show confirmation
            if (!confirm(`Are you sure you want to ${action} this control?`)) {
                // Revert switch state if user cancels
                $switch.prop('checked', !$switch.is(':checked'));
                return;
            }

            // Disable the switch to prevent multiple clicks
            $switch.prop('disabled', true);

            // Show loading state
            $form.find('.form-check-label').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

            // Submit the form via AJAX
            $.ajax({
                url: $form.attr('action'),
                method: $form.attr('method'),
                data: $form.serialize(),
                success: function(response) {
                    // Update the badge dynamically
                    $badge.text(response.is_active ? 'Active' : 'Inactive');
                    $badge.removeClass('bg-success bg-danger');
                    $badge.addClass(response.is_active ? 'bg-success' : 'bg-danger');
                    // Show success message
                    //alert(response.message || 'Control updated successfully!');
                },
                error: function(xhr) {
                    // Revert switch state on error
                    $switch.prop('checked', !$switch.is(':checked'));
                    alert('Failed to update control: ' + (xhr.responseJSON?.message || 'An error occurred.'));
                },
                complete: function() {
                    // Re-enable the switch and clear loading state
                    $switch.prop('disabled', false);
                    $form.find('.form-check-label').text('');
                }
            });
        });
    });
</script>
@endsection
