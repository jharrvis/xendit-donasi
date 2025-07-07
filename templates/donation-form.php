<div class="wp-xendit-donation-form-container">
    <h2><?php echo esc_html($atts['title']); ?></h2>
    <p class="donation-description"><?php echo esc_html($atts['description']); ?></p>

    <form id="wp-xendit-donation-form" class="wp-xendit-donation-form">
        <div class="form-row">
            <label for="donor_name">Nama Lengkap *</label>
            <input type="text" id="donor_name" name="donor_name" required>
        </div>

        <div class="form-row">
            <label for="donor_email">Email *</label>
            <input type="email" id="donor_email" name="donor_email" required>
        </div>

        <div class="form-row">
            <label for="donor_phone">Nomor Telepon</label>
            <input type="tel" id="donor_phone" name="donor_phone">
        </div>

        <?php if (get_option('wp_xendit_donation_enable_usd', 1)): ?>
        <div class="form-row">
            <label>Mata Uang *</label>
            <div class="currency-selector">
                <label class="currency-option">
                    <input type="radio" name="currency" value="IDR" checked>
                    <span class="currency-label">
                        <strong>Rupiah (IDR)</strong>
                        <small>Mata uang Indonesia</small>
                    </span>
                </label>
                <label class="currency-option">
                    <input type="radio" name="currency" value="USD">
                    <span class="currency-label">
                        <strong>US Dollar (USD)</strong>
                        <small>Akan dikonversi ke Rupiah</small>
                    </span>
                </label>
            </div>
            <div class="currency-info usd-info" style="display: none;">
                <div class="conversion-notice">
                    <i class="dashicons dashicons-info"></i>
                    <span>Mata uang Dollar akan otomatis dikonversi ke Rupiah menggunakan kurs Bank BI saat ini</span>
                </div>
                <div class="current-rate">
                    Kurs saat ini: <strong>1 USD = <span id="current-exchange-rate"><?php echo WP_Xendit_Currency_Converter::format_currency(WP_Xendit_Currency_Converter::get_exchange_rate(), 'IDR'); ?></span></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-row">
            <label>Jumlah Donasi *</label>
            <div class="suggested-amounts" id="suggested-amounts-idr">
                <?php 
                $suggested_amounts = explode(',', WP_Xendit_Currency_Converter::get_suggested_amounts('IDR'));
                foreach($suggested_amounts as $amount) {
                    $amount = trim($amount);
                    echo '<button type="button" class="amount-option" data-amount="' . esc_attr($amount) . '" data-currency="IDR">Rp ' . number_format(intval($amount), 0, ',', '.') . '</button>';
                }
                ?>
                <button type="button" class="amount-option custom-amount">Jumlah Lain</button>
            </div>
            
            <?php if (get_option('wp_xendit_donation_enable_usd', 1)): ?>
            <div class="suggested-amounts" id="suggested-amounts-usd" style="display: none;">
                <?php 
                $usd_amounts = explode(',', WP_Xendit_Currency_Converter::get_suggested_amounts('USD'));
                foreach($usd_amounts as $amount) {
                    $amount = trim($amount);
                    echo '<button type="button" class="amount-option" data-amount="' . esc_attr($amount) . '" data-currency="USD">$' . number_format(intval($amount), 0, '.', ',') . '</button>';
                }
                ?>
                <button type="button" class="amount-option custom-amount">Custom Amount</button>
            </div>
            <?php endif; ?>
            
            <div class="custom-amount-container" style="display: none;">
                <input type="number" id="amount" name="amount" step="0.01" placeholder="Masukkan jumlah donasi">
                <div class="amount-info">
                    <p class="min-amount-note idr-min">Minimum: Rp <?php echo number_format(WP_Xendit_Currency_Converter::get_minimum_amount('IDR'), 0, ',', '.'); ?></p>
                    <p class="min-amount-note usd-min" style="display: none;">Minimum: $<?php echo WP_Xendit_Currency_Converter::get_minimum_amount('USD'); ?></p>
                </div>
                <div class="conversion-preview" style="display: none;">
                    <div class="conversion-details">
                        <span class="original-amount"></span>
                        <span class="conversion-arrow">→</span>
                        <span class="converted-amount"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row">
            <label for="message">Pesan (opsional)</label>
            <textarea id="message" name="message" rows="3"></textarea>
        </div>

        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_xendit_donation_nonce'); ?>">
        <input type="hidden" name="action" value="submit_donation">
        <input type="hidden" name="selected_currency" id="selected_currency" value="IDR">

        <div class="form-row">
            <button type="submit" class="donation-submit-button"><?php echo esc_html($atts['button_text']); ?></button>
        </div>

        <div class="donation-message" style="display: none;"></div>
    </form>

    <!-- Confirmation Modal -->
    <div id="donation-confirmation-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Konfirmasi Donasi</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="confirmation-details">
                    <div class="donor-info">
                        <h4>Informasi Donatur</h4>
                        <p><strong>Nama:</strong> <span id="confirm-donor-name"></span></p>
                        <p><strong>Email:</strong> <span id="confirm-donor-email"></span></p>
                    </div>
                    <div class="donation-info">
                        <h4>Detail Donasi</h4>
                        <div class="currency-conversion" id="currency-conversion-details">
                            <!-- Will be filled by JavaScript -->
                        </div>
                        <div class="final-amount">
                            <p><strong>Jumlah yang akan diproses:</strong> <span id="final-idr-amount"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-donation">Batal</button>
                <button type="button" class="btn btn-primary" id="confirm-donation">Konfirmasi Donasi</button>
            </div>
        </div>
    </div>
</div>