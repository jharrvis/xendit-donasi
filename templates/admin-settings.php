<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <strong>Shortcode:</strong> [xendit_donation_form] - Gunakan shortcode ini untuk menampilkan form donasi di halaman atau post.
            <br>
            <strong>Opsi Shortcode:</strong> title="Judul", description="Deskripsi", button_text="Teks Tombol", minimum_amount="10000", suggested_amounts="50000,100000,250000,500000"
        </p>
    </div>
    
    <form method="post" action="options.php">
        <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        submit_button('Simpan Pengaturan');
        ?>
    </form>
    
    <div class="xendit-donation-info">
        <h2>Cara Setup Webhook di Xendit Dashboard</h2>
        <div class="setup-steps">
            
            <div class="step-box" style="background: #e8f4fd; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa;">
                <h3>🚀 Langkah 1: Setup di Xendit Dashboard</h3>
                <ol>
                    <li>Login ke <a href="https://dashboard.xendit.co" target="_blank">Xendit Dashboard</a></li>
                    <li>Pilih mode <strong><?php echo get_option('wp_xendit_donation_mode', 'sandbox'); ?></strong> (sesuai setting di atas)</li>
                    <li>Pergi ke <strong>Settings</strong> → <strong>Webhooks</strong></li>
                    <li>Copy <strong>Webhook verification token</strong> yang sudah tersedia</li>
                    <li>Klik <strong>+ Add Webhook</strong> atau <strong>Add webhook URL</strong></li>
                    <li>Masukkan Webhook URL: <br>
                        <code style="background: #f1f1f1; padding: 5px 8px; border-radius: 3px;"><?php echo home_url('/wp-json/wp-xendit-donation/v1/callback'); ?></code>
                        <button type="button" class="button button-small copy-btn" data-text="<?php echo esc_attr(home_url('/wp-json/wp-xendit-donation/v1/callback')); ?>">Copy</button>
                    </li>
                    <li>Pilih Events: <code>invoice.paid</code>, <code>invoice.expired</code>, <code>invoice.failed</code></li>
                    <li>Save webhook</li>
                </ol>
            </div>
            
            <div class="step-box" style="background: #f0f9ff; padding: 15px; margin: 15px 0; border-left: 4px solid #0284c7;">
                <h3>📋 Langkah 2: Copy Token ke WordPress</h3>
                <ol>
                    <li>Dari halaman Webhooks Xendit, copy <strong>Webhook verification token</strong></li>
                    <li>Paste token tersebut ke field <strong>"Callback Token"</strong> di atas</li>
                    <li>Klik <strong>Simpan Pengaturan</strong></li>
                </ol>
                
                <div style="background: #fef3c7; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <strong>⚠️ Penting:</strong> Token dari screenshot Anda adalah: <code>0biC55zxoeugWYGOVMnJDqqJWU</code><br>
                    Paste token ini ke field "Callback Token" di atas.
                </div>
            </div>
            
            <div class="step-box" style="background: #f0fdf4; padding: 15px; margin: 15px 0; border-left: 4px solid #16a34a;">
                <h3>🧪 Langkah 3: Test Webhook</h3>
                <ul>
                    <li>Buat donasi test menggunakan form</li>
                    <li>Lakukan pembayaran dengan test data (gunakan Virtual Account: 12345678901)</li>
                    <li>Cek di <strong>Daftar Donasi</strong> apakah status berubah dari "Pending" ke "Berhasil"</li>
                    <li>Jika tidak berubah, cek troubleshooting di bawah</li>
                </ul>
            </div>
        </div>
        
        <div class="troubleshooting-info" style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3>🔧 Troubleshooting</h3>
            <p><strong>Jika status donasi tetap "Pending" setelah pembayaran berhasil:</strong></p>
            <ul>
                <li>✅ Pastikan token sudah benar (copy dari Xendit → paste ke WordPress)</li>
                <li>✅ Pastikan webhook URL sudah ditambahkan di Xendit Dashboard</li>
                <li>✅ Pastikan website menggunakan HTTPS (Xendit hanya mengirim ke HTTPS)</li>
                <li>✅ Cek WordPress debug log di <code>/wp-content/debug.log</code></li>
                <li>✅ Gunakan menu <strong>Manual Update</strong> untuk update status secara manual</li>
                <li>✅ Test dengan Virtual Account: <code>12345678901</code> (otomatis sukses)</li>
            </ul>
        </div>
        
        <div class="api-setup-info" style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-left: 4px solid #bee5eb;">
            <h3>🔑 Setup API Key</h3>
            <ol>
                <li>Di Xendit Dashboard, pergi ke <strong>Settings</strong> → <strong>API Keys</strong></li>
                <li>Copy <strong>Secret Key</strong> (yang dimulai dengan <code>xnd_development_</code> untuk test)</li>
                <li>Paste ke field <strong>Xendit API Key</strong> di atas</li>
                <li>Pilih mode <strong>Sandbox (Test)</strong></li>
            </ol>
        </div>
        
        <div class="current-config" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
            <h3>📋 Konfigurasi Saat Ini</h3>
            <table class="widefat">
                <tr>
                    <td><strong>Webhook URL:</strong></td>
                    <td><code><?php echo home_url('/wp-json/wp-xendit-donation/v1/callback'); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Mode:</strong></td>
                    <td><strong><?php echo get_option('wp_xendit_donation_mode', 'sandbox'); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>API Key Status:</strong></td>
                    <td>
                        <?php 
                        $api_key = get_option('wp_xendit_donation_api_key', '');
                        if (empty($api_key)) {
                            echo '<span style="color: red;">❌ Belum dikonfigurasi</span>';
                        } else {
                            echo '<span style="color: green;">✅ Sudah dikonfigurasi</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Callback Token Status:</strong></td>
                    <td>
                        <?php 
                        $callback_token = get_option('wp_xendit_donation_callback_token', '');
                        if (empty($callback_token)) {
                            echo '<span style="color: red;">❌ Belum dikonfigurasi</span>';
                        } else {
                            echo '<span style="color: green;">✅ Sudah dikonfigurasi</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy to clipboard functionality
    $('.copy-btn').on('click', function() {
        var text = $(this).data('text');
        
        // Create temporary textarea
        var temp = $('<textarea>');
        $('body').append(temp);
        temp.val(text).select();
        document.execCommand('copy');
        temp.remove();
        
        // Show feedback
        var btn = $(this);
        var originalText = btn.text();
        btn.text('Copied!').addClass('button-primary');
        
        setTimeout(function() {
            btn.text(originalText).removeClass('button-primary');
        }, 2000);
    });
});
</script>

<style>
.step-box {
    border-radius: 8px;
}
.step-box h3 {
    margin-top: 0;
    color: #1d4ed8;
}
.step-box ol, .step-box ul {
    margin-left: 20px;
}
.step-box li {
    margin-bottom: 8px;
}
.copy-btn {
    margin-left: 10px;
    font-size: 11px;
    height: 24px;
    line-height: 22px;
}
.current-config table td {
    padding: 8px 12px;
}
.current-config table code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>