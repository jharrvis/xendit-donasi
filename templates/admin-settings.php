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
        <h2>Cara Menggunakan</h2>
        <ol>
            <li>Daftar dan buat akun di <a href="https://dashboard.xendit.co/register" target="_blank">Xendit</a>.</li>
            <li>Dapatkan API Key dari dashboard Xendit.</li>
            <li>Masukkan API Key pada pengaturan di atas.</li>
            <li>Gunakan shortcode <code>[xendit_donation_form]</code> di halaman atau post untuk menampilkan form donasi.</li>
            <li>Setelah pengaturan disimpan, konfigurasi webhook di dashboard Xendit dengan URL berikut:<br>
                <code><?php echo home_url('/wp-json/wp-xendit-donation/v1/callback'); ?></code>
            </li>
            <li>Masukkan Callback Token pada pengaturan webhook di Xendit dashboard:<br>
                <code><?php echo esc_html(get_option('wp_xendit_donation_callback_token', '')); ?></code>
            </li>
        </ol>
    </div>
</div>