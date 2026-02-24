<div class="modal fade" id="helpManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class='bx bx-book-open me-2'></i> HR Help Manual
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <iframe 
                    src="{{ asset('help-manuals/Peepal_Bonsai_User_Manual.pdf') }}#toolbar=1"
                    style="width:100%; height:75vh; border:none;">
                </iframe>
            </div>

            <div class="modal-footer">
                <a href="{{ route('download.manual') }}" 
                   class="btn btn-primary btn-sm">
                    <i class='bx bx-download'></i> Download
                </a>

                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#helpManualModal').on('hidden.bs.modal', function () {
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    });
});
</script>
@endpush
