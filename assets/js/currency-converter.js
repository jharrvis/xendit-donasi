jQuery(document).ready(function($) {
    var form = $('#wp-xendit-donation-form');
    var messageContainer = form.find('.donation-message');
    var amountInput = form.find('#amount');
    var currencyRadios = form.find('input[name="currency"]');
    var selectedCurrencyInput = $('#selected_currency');
    var modal = $('#donation-confirmation-modal');
    
    // Current exchange rate
    var currentRate = 0;
    
    // Initialize
    loadExchangeRate();
    
    /**
     * Load current exchange rate
     */
    function loadExchangeRate() {
        $.ajax({
            url: wp_xendit_donation.ajax_url,
            type: 'POST',
            data: {
                action: 'get_exchange_rate',
                nonce: wp_xendit_donation.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentRate = parseFloat(response.data.rate);
                    $('#current-exchange-rate').text(formatCurrency(currentRate, 'IDR'));
                }
            }
        });
    }
    
    /**
     * Handle currency selection
     */
    currencyRadios.on('change', function() {
        var selectedCurrency = $(this).val();
        selectedCurrencyInput.val(selectedCurrency);
        
        // Show/hide currency info
        if (selectedCurrency === 'USD') {
            $('.usd-info').slideDown();
            $('#suggested-amounts-idr').hide();
            $('#suggested-amounts-usd').show();
            $('.idr-min').hide();
            $('.usd-min').show();
        } else {
            $('.usd-info').slideUp();
            $('#suggested-amounts-usd').hide();
            $('#suggested-amounts-idr').show();
            $('.usd-min').hide();
            $('.idr-min').show();
        }
        
        // Clear selected amounts
        $('.amount-option').removeClass('selected');
        $('.custom-amount-container').hide();
        amountInput.val('');
        updateConversionPreview();
    });
    
    /**
     * Handle amount selection
     */
    $(document).on('click', '.amount-option', function() {
        var $this = $(this);
        $('.amount-option').removeClass('selected');
        $this.addClass('selected');

        if ($this.hasClass('custom-amount')) {
            $('.custom-amount-container').show();
            amountInput.focus();
        } else {
            $('.custom-amount-container').hide();
            amountInput.val($this.data('amount'));
            updateConversionPreview();
        }
    });
    
    /**
     * Handle amount input change
     */
    amountInput.on('input', function() {
        updateConversionPreview();
    });
    
    /**
     * Update conversion preview
     */
    function updateConversionPreview() {
        var amount = parseFloat(amountInput.val());
        var currency = selectedCurrencyInput.val();
        
        if (isNaN(amount) || amount <= 0) {
            $('.conversion-preview').hide();
            return;
        }
        
        if (currency === 'USD' && currentRate > 0) {
            var convertedAmount = amount * currentRate;
            var originalFormatted = formatCurrency(amount, 'USD');
            var convertedFormatted = formatCurrency(convertedAmount, 'IDR');
            
            $('.original-amount').text(originalFormatted);
            $('.converted-amount').text(convertedFormatted);
            $('.conversion-preview').show();
        } else {
            $('.conversion-preview').hide();
        }
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount, currency) {
        if (currency === 'USD') {
            return '$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            return 'Rp ' + amount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        }
    }
    
    /**
     * Form submission with confirmation
     */
    form.on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return false;
        }
        
        var currency = selectedCurrencyInput.val();
        
        if (currency === 'USD') {
            showConfirmationModal();
        } else {
            submitDonation();
        }
    });
    
    /**
     * Show confirmation modal for USD donations
     */
    function showConfirmationModal() {
        var donorName = $('#donor_name').val();
        var donorEmail = $('#donor_email').val();
        var amount = parseFloat(amountInput.val());
        var currency = selectedCurrencyInput.val();
        
        // Fill donor info
        $('#confirm-donor-name').text(donorName);
        $('#confirm-donor-email').text(donorEmail);
        
        // Fill conversion details
        if (currency === 'USD') {
            var convertedAmount = amount * currentRate;
            var conversionHtml = `
                <div class="conversion-detail">
                    <p><strong>Jumlah Donasi:</strong> ${formatCurrency(amount, 'USD')}</p>
                    <p><strong>Kurs Saat Ini:</strong> 1 USD = ${formatCurrency(currentRate, 'IDR')}</p>
                    <p><strong>Konversi ke IDR:</strong> ${formatCurrency(convertedAmount, 'IDR')}</p>
                </div>
                <div class="conversion-notice">
                    <small><i class="dashicons dashicons-info"></i> Pembayaran akan diproses dalam Rupiah sesuai kurs di atas</small>
                </div>
            `;
            $('#currency-conversion-details').html(conversionHtml);
            $('#final-idr-amount').text(formatCurrency(convertedAmount, 'IDR'));
        }
        
        modal.show();
    }
    
    /**
     * Modal close handlers
     */
    $('.close, #cancel-donation').on('click', function() {
        modal.hide();
    });
    
    $(window).on('click', function(e) {
        if (e.target == modal[0]) {
            modal.hide();
        }
    });
    
    /**
     * Confirm donation
     */
    $('#confirm-donation').on('click', function() {
        modal.hide();
        submitDonation();
    });
    
    /**
     * Submit donation
     */
    function submitDonation() {
        showMessage('Memproses donasi Anda...', 'loading');
        
        var formData = form.serialize();
        
        $.ajax({
            type: 'POST',
            url: wp_xendit_donation.ajax_url,
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    setTimeout(function() {
                        if (response.data.invoice_url) {
                            window.location.href = response.data.invoice_url;
                        }
                    }, 2000);
                } else {
                    showMessage(response.data.message || 'Terjadi kesalahan', 'error');
                }
            },
            error: function() {
                showMessage('Terjadi kesalahan koneksi', 'error');
            }
        });
    }
    
    /**
     * Validate form
     */
    function validateForm() {
        var donorName = $('#donor_name').val().trim();
        var donorEmail = $('#donor_email').val().trim();
        var amount = parseFloat(amountInput.val());
        var currency = selectedCurrencyInput.val();
        
        if (!donorName) {
            showMessage('Nama lengkap harus diisi', 'error');
            return false;
        }
        
        if (!donorEmail || !isValidEmail(donorEmail)) {
            showMessage('Email yang valid harus diisi', 'error');
            return false;
        }
        
        if (isNaN(amount) || amount <= 0) {
            showMessage('Jumlah donasi harus diisi', 'error');
            return false;
        }
        
        var minAmount = currency === 'USD' ? 1 : 10000;
        if (amount < minAmount) {
            var minFormatted = formatCurrency(minAmount, currency);
            showMessage('Jumlah donasi minimal ' + minFormatted, 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        messageContainer.removeClass('success error loading').addClass(type);
        messageContainer.text(message).show();
        
        if (type === 'success' || type === 'error') {
            setTimeout(function() {
                messageContainer.fadeOut();
            }, 5000);
        }
    }
});