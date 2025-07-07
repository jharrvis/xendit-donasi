jQuery(document).ready(function($) {
    var form = $('#wp-xendit-donation-form');
    var messageContainer = form.find('.donation-message');
    var amountInput = form.find('#amount');
    var amountOptions = form.find('.amount-option');
    var customAmountContainer = form.find('.custom-amount-container');

    // Handle amount selection
    amountOptions.on('click', function() {
        var $this = $(this);
        amountOptions.removeClass('selected');
        $this.addClass('selected');

        if ($this.hasClass('custom-amount')) {
            customAmountContainer.show();
            amountInput.focus();
        } else {
            customAmountContainer.hide();
            amountInput.val($this.data('amount'));
        }
    });

    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();

        // Validate form
        if (!validateForm()) {
            return false;
        }

        // Show loading message
        showMessage('Memproses donasi Anda...', 'loading');

        // Submit form data via AJAX
        $.ajax({
            type: 'POST',
            url: wp_xendit_donation.ajax_url,
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    // Redirect to Xendit invoice page after a short delay
                    setTimeout(function() {
                        window.location.href = response.data.invoice_url;
                    }, 2000);
                } else {
                    showMessage('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Terjadi kesalahan saat memproses donasi. Silakan coba lagi.', 'error');
            }
        });
    });

    // Function to validate the form
    function validateForm() {
        var isValid = true;
        var donorName = form.find('#donor_name').val();
        var donorEmail = form.find('#donor_email').val();
        var amount = amountInput.val();
        var minimumAmount = parseInt(amountInput.attr('min'));

        // Reset previous error messages
        messageContainer.hide();

        // Basic validation
        if (!donorName || !donorEmail || !amount) {
            showMessage('Semua field yang ditandai dengan * wajib diisi', 'error');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(donorEmail)) {
            showMessage('Masukkan alamat email yang valid', 'error');
            isValid = false;
        } else if (parseInt(amount) < minimumAmount) {
            showMessage('Jumlah donasi minimal Rp ' + minimumAmount.toLocaleString('id-ID'), 'error');
            isValid = false;
        }

        return isValid;
    }

    // Function to show message
    function showMessage(message, type) {
        messageContainer
            .removeClass('success error loading')
            .addClass(type)
            .html(message)
            .show();
    }
});