<!-- JAVASCRIPT -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
<script src="{{URL::to('/')}}/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="{{URL::to('/')}}/assets/libs/simplebar/simplebar.min.js"></script>
<script src="{{URL::to('/')}}/assets/libs/node-waves/waves.min.js"></script>
<script src="{{URL::to('/')}}/assets/libs/feather-icons/feather.min.js"></script>
<script src="{{URL::to('/')}}/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>

{{-- toastr js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/b-3.1.2/b-html5-3.1.2/fc-5.0.4/fh-4.0.1/datatables.min.js"></script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- App js -->
<script src="{{URL::to('/')}}/assets/js/app.js"></script>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<!-- Sweet Alerts js -->
<script src="{{URL::to('/')}}/assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="{{URL::to('/')}}/assets/js/select2.min.js"></script>
<!-- echarts js -->

<script src="{{URL::to('/')}}/assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
$(document).ready(function () {
    // If you want to set active on click
    $('#navbar-nav .nav-item .nav-link').on('click', function () {
        $('#navbar-nav .nav-item').removeClass('active'); // Remove from all
        $(this).closest('.nav-item').addClass('active');   // Add to clicked
    });

    // Optional: Auto-set active based on current URL
    var currentUrl = window.location.href;
    $('#navbar-nav .nav-item .nav-link').each(function () {
        if (this.href === currentUrl) {
            $(this).closest('.nav-item').addClass('active');
        }
    });
});
</script>
