<div class="wrap">
    <h1>Detail Donasi</h1>
    
    <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-donations'); ?>" class="button">&larr; Kembali ke Daftar Donasi</a>
    
    <div class="card" style="margin-top: 20px;">
        <h2>Informasi Donatur</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Nama</th>
                <td><?php echo esc_html($donation->donor_name); ?></td>
            </tr>
            <tr>
                <th scope="row">Email</th>
                <td><a href="mailto:<?php echo esc_attr($donation->donor_email); ?>"><?php echo esc_html($donation->donor_email); ?></a></td>
            </tr>
            <tr>
                <th scope="row">Nomor Telepon</th>
                <td><?php echo esc_html($donation->donor_phone ?: '-'); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>Informasi Donasi</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Jumlah</th>
                <td><strong>Rp <?php echo number_format($donation->amount, 0, ',', '.'); ?></strong></td>
            </tr>
            <tr>
                <th scope="row">Status</th>
                <td>
                    <?php
                    $status_labels = array(
                        'pending' => 'Pending',
                        'PAID' => 'Berhasil',
                        'EXPIRED' => 'Kedaluwarsa',
                        'FAILED' => 'Gagal'
                    );
                    $status_class = strtolower($donation->status);
                    ?>
                    <span class="status-<?php echo $status_class; ?>">
                        <?php echo isset($status_labels[$donation->status]) ? $status_labels[$donation->status] : $donation->status; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Pesan</th>
                <td><?php echo esc_html($donation->message ?: '-'); ?></td>
            </tr>
            <tr>
                <th scope="row">Tanggal Dibuat</th>
                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($donation->created_at)); ?></td>
            </tr>
            <?php if ($donation->updated_at): ?>
            <tr>
                <th scope="row">Terakhir Diupdate</th>
                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($donation->updated_at)); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="card">
        <h2>Informasi Teknis</h2>
        <table class="form-table">
            <tr>
                <th scope="row">External ID</th>
                <td><code><?php echo esc_html($donation->external_id); ?></code></td>
            </tr>
            <tr>
                <th scope="row">Invoice ID</th>
                <td><code><?php echo esc_html($donation->invoice_id); ?></code></td>
            </tr>
            <tr>
                <th scope="row">Invoice URL</th>
                <td><a href="<?php echo esc_url($donation->invoice_url); ?>" target="_blank">Buka Invoice</a></td>
            </tr>
        </table>
    </div>
    
    <?php if ($donation->status !== 'PAID'): ?>
    <div class="card">
        <h2>Update Status Manual</h2>
        <form method="post" action="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-manual-update'); ?>">
            <?php wp_nonce_field('manual_update_status'); ?>
            <input type="hidden" name="external_id" value="<?php echo esc_attr($donation->external_id); ?>" />
            
            <table class="form-table">
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
    <?php endif; ?>
</div>