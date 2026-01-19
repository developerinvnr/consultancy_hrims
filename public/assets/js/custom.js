$(document).ready(function () {
    // Fix for app.js error - check if element exists
    const verticalHoverBtn = document.getElementById('vertical-hover');
    if (verticalHoverBtn) {
        verticalHoverBtn.addEventListener('click', function() {
            // Your click handler code
        });
    }

    // Active menu items
    $('#navbar-nav .nav-item .nav-link').on('click', function () {
        $('#navbar-nav .nav-item').removeClass('active');
        $(this).closest('.nav-item').addClass('active');
    });

    // Auto-set active based on current URL
    var currentUrl = window.location.pathname;
    $('#navbar-nav .nav-item .nav-link').each(function () {
        var linkUrl = $(this).attr('href');
        if (linkUrl && currentUrl.includes(linkUrl.replace('#', ''))) {
            $(this).closest('.nav-item').addClass('active');
        }
    });
});