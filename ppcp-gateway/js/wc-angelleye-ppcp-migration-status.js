jQuery(document).ready(function ($) {
    function updateProgressBar() {
        var progressBar = $('.percentage_display_bar');
        $.ajax({
            url: ppcp_migration_progress.ajax_url,
            type: 'POST',
            data: {
                action: 'update_progress_bar'
            },
            success: function (response) {
                progressBar.css('width', response.percentage + '%');
                if (response.status === 'complete') {
                    window.location.reload();
                }
            }
        });
    }
    setInterval(updateProgressBar, 30000);
});
