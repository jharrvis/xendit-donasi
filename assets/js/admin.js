jQuery(document).ready(function($) {
    // Konfirmasi reset data
    $('.reset-data-button').on('click', function() {
        return confirm('Apakah Anda yakin ingin mereset semua data donasi? Tindakan ini tidak dapat dibatalkan.');
    });
    
    // Toggle API key visibility
    $('.toggle-api-key').on('click', function(e) {
        e.preventDefault();
        var apiKeyField = $('input[name="wp_xendit_donation_api_key"]');
        
        if (apiKeyField.attr('type') === 'password') {
            apiKeyField.attr('type', 'text');
            $(this).text('Sembunyikan');
        } else {
            apiKeyField.attr('type', 'password');
            $(this).text('Tampilkan');
        }
    });
});