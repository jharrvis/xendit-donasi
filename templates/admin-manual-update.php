<div class="wrap">
    <h1>Manual Status Update</h1>
    
    <div class="card">
        <h2>Update Status Donasi</h2>
        <p>Gunakan fitur ini jika webhook tidak berfungsi dan status donasi perlu diupdate secara manual.</p>
        
        <form method="post">
            <?php wp_nonce_field('manual_update_status'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">External ID</th>
                    <td>
                        <input type="text" name="external_id" class="regular-text" placeholder="donation-1234567890-123" required />
                        <p class="description">ID eksternal donasi (lihat di detail donasi atau tabel donasi)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Status Baru</th>
                    <td>
                        <select name="new_status" required>
                            <option value="">Pilih Status</option>
                            <option value="PAID">PAID (Berhasil)</option>
                            <option value="EXPIRED">EXPIRED (Kedaluwarsa)</option>
                            <option value="FAILED">FAILED (Gagal)</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="update_status" class="button-primary" value="Update Status" />
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2>Cek Status Invoice di Xendit</h2>
        <p>Cek status terkini dari invoice di server Xendit</p>
        
        <table class="form-table">
            <tr>
                <th scope="row">Invoice ID</th>
                <td>
                    <input type="text" id="invoice_id_check" class="regular-text" placeholder="64f5c4e1234567890abcdef1" />
                    <button type="button" id="check_xendit_status" class="button">Cek Status</button>
                    <p class="description">Masukkan Invoice ID dari Xendit (lihat di detail donasi)</p>
                </td>
            </tr>
        </table>
        
        <div id="xendit_status_result"></div>
    </div>
    
    <div class="card">
        <h2>Troubleshooting Webhook</h2>
        <p><strong>URL Webhook:</strong> <code><?php echo home_url('/wp-json/wp-xendit-donation/v1/callback'); ?></code></p>
        <p><strong>Callback Token:</strong> <code><?php echo esc_html(get_option('wp_xendit_donation_callback_token', '')); ?></code></p>
        
        <h4>Langkah Troubleshooting:</h4>
        <ol>
            <li>Pastikan URL webhook di atas sudah dimasukkan di Xendit Dashboard</li>
            <li>Pastikan callback token di atas sudah dimasukkan di Xendit Dashboard</li>
            <li>Cek WordPress debug log di <code>/wp-content/debug.log</code></li>
            <li>Test webhook dengan tool seperti ngrok jika localhost</li>
            <li>Pastikan SSL/HTTPS aktif (Xendit hanya mengirim ke HTTPS)</li>
        </ol>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#check_xendit_status').on('click', function() {
        var button = $(this);
        var result = $('#xendit_status_result');
        var invoiceId = $('#invoice_id_check').val();
        
        if (!invoiceId) {
            alert('Masukkan Invoice ID');
            return;
        }
        
        button.prop('disabled', true).text('Checking...');
        result.html('<p>Mengecek status...</p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'check_xendit_status',
                invoice_id: invoiceId,
                nonce: '<?php echo wp_create_nonce('xendit_check_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    result.html(
                        '<div class="notice notice-success">' +
                        '<h4>Status Invoice:</h4>' +
                        '<p><strong>Status:</strong> ' + data.status + '</p>' +
                        '<p><strong>External ID:</strong> ' + (data.external_id || 'N/A') + '</p>' +
                        '<p><strong>Amount:</strong> ' + (data.amount || 'N/A') + '</p>' +
                        '<p><strong>Paid At:</strong> ' + (data.paid_at || 'Not paid yet') + '</p>' +
                        '<p><strong>Created:</strong> ' + (data.created || 'N/A') + '</p>' +
                        '</div>'
                    );
                } else {
                    result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                result.html('<div class="notice notice-error"><p>Terjadi kesalahan saat mengecek status</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('Cek Status');
            }
        });
    });
});
</script>