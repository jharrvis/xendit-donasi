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

        <div class="form-row">
            <label>Jumlah Donasi *</label>
            <div class="suggested-amounts">
                <?php 
                $suggested_amounts = explode(',', $atts['suggested_amounts']);
                foreach($suggested_amounts as $amount) {
                    $amount = trim($amount);
                    echo '<button type="button" class="amount-option" data-amount="' . esc_attr($amount) . '">Rp ' . number_format(intval($amount), 0, ',', '.') . '</button>';
                }
                ?>
                <button type="button" class="amount-option custom-amount">Jumlah Lain</button>
            </div>
            <div class="custom-amount-container" style="display: none;">
                <input type="number" id="amount" name="amount" min="<?php echo esc_attr($atts['minimum_amount']); ?>" step="1000" placeholder="Masukkan jumlah donasi">
                <p class="min-amount-note">Minimum: Rp <?php echo number_format(intval($atts['minimum_amount']), 0, ',', '.'); ?></p>
            </div>
        </div>

        <div class="form-row">
            <label for="message">Pesan (opsional)</label>
            <textarea id="message" name="message" rows="3"></textarea>
        </div>

        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_xendit_donation_nonce'); ?>">
        <input type="hidden" name="action" value="submit_donation">

        <div class="form-row">
            <button type="submit" class="donation-submit-button"><?php echo esc_html($atts['button_text']); ?></button>
        </div>

        <div class="donation-message" style="display: none;"></div>
    </form>
</div>