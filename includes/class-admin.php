<?php
/**
 * Kelas untuk menangani halaman admin plugin
 */
class WP_Xendit_Donation_Admin {

    /**
     * ID plugin
     */
    private $plugin_name;

    /**
     * Versi plugin
     */
    private $version;

    /**
     * Inisialisasi kelas
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Mendaftarkan stylesheet untuk halaman admin
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name . '-admin', WP_XENDIT_DONATION_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all');
    }

    /**
     * Mendaftarkan script untuk halaman admin
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name . '-admin', WP_XENDIT_DONATION_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), $this->version, false);
    }

    /**
     * Menambahkan halaman pengaturan di menu admin
     */
    public function add_options_page() {
        add_menu_page(
            'Xendit Donation Settings', 
            'Xendit Donasi', 
            'manage_options', 
            $this->plugin_name, 
            array($this, 'display_options_page'),
            'dashicons-money-alt',
            65
        );
        
        add_submenu_page(
            $this->plugin_name,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_options_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            'Donations',
            'Donations',
            'manage_options',
            $this->plugin_name . '-donations',
            array($this, 'display_donations_page')
        );
    }

    /**
     * Mendaftarkan pengaturan plugin
     */
    public function register_settings() {
        register_setting($this->plugin_name, 'wp_xendit_donation_api_key');
        register_setting($this->plugin_name, 'wp_xendit_donation_mode');
        register_setting($this->plugin_name, 'wp_xendit_donation_callback_token');
        register_setting($this->plugin_name, 'wp_xendit_donation_minimum_amount');
        register_setting($this->plugin_name, 'wp_xendit_donation_suggested_amounts');
        register_setting($this->plugin_name, 'wp_xendit_donation_success_page');
        register_setting($this->plugin_name, 'wp_xendit_donation_failed_page');
        
        add_settings_section(
            'wp_xendit_donation_general_section',
            'General Settings',
            array($this, 'general_section_callback'),
            $this->plugin_name
        );
        
        add_settings_field(
            'wp_xendit_donation_api_key',
            'Xendit API Key',
            array($this, 'api_key_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
        
        add_settings_field(
            'wp_xendit_donation_mode',
            'Mode',
            array($this, 'mode_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
        
        add_settings_field(
            'wp_xendit_donation_callback_token',
            'Callback Token',
            array($this, 'callback_token_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
        
        add_settings_field(
            'wp_xendit_donation_minimum_amount',
            'Minimum Donation Amount',
            array($this, 'minimum_amount_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
        
        add_settings_field(
            'wp_xendit_donation_suggested_amounts',
            'Suggested Donation Amounts',
            array($this, 'suggested_amounts_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
        
        add_settings_field(
            'wp_xendit_donation_pages',
            'Redirect Pages',
            array($this, 'redirect_pages_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
    }

    /**
     * Callback untuk section pengaturan umum
     */
    public function general_section_callback() {
        echo '<p>Konfigurasi pengaturan umum untuk integrasi donasi dengan Xendit.</p>';
    }

    /**
     * Callback untuk field API Key
     */
    public function api_key_field_callback() {
        $api_key = get_option('wp_xendit_donation_api_key', '');
        echo '<input type="text" class="regular-text" name="wp_xendit_donation_api_key" value="' . esc_attr($api_key) . '" />';
        echo '<p class="description">Masukkan Xendit API Key. Anda dapat menemukannya di dashboard Xendit.</p>';
    }

    /**
     * Callback untuk field Mode
     */
    public function mode_field_callback() {
        $mode = get_option('wp_xendit_donation_mode', 'sandbox');
        echo '<select name="wp_xendit_donation_mode">';
        echo '<option value="sandbox" ' . selected($mode, 'sandbox', false) . '>Sandbox (Test)</option>';
        echo '<option value="production" ' . selected($mode, 'production', false) . '>Production (Live)</option>';
        echo '</select>';
        echo '<p class="description">Pilih mode Sandbox untuk testing atau Production untuk transaksi live.</p>';
    }

    /**
     * Callback untuk field Callback Token
     */
    public function callback_token_field_callback() {
        $callback_token = get_option('wp_xendit_donation_callback_token', '');
        if (empty($callback_token)) {
            $callback_token = wp_generate_password(24, false);
            update_option('wp_xendit_donation_callback_token', $callback_token);
        }
        echo '<input type="text" class="regular-text" name="wp_xendit_donation_callback_token" value="' . esc_attr($callback_token) . '" />';
        echo '<p class="description">Token ini digunakan untuk memverifikasi callback dari Xendit. Masukkan token ini di pengaturan callback Xendit.</p>';
    }

    /**
     * Callback untuk field Minimum Amount
     */
    public function minimum_amount_field_callback() {
        $minimum_amount = get_option('wp_xendit_donation_minimum_amount', 10000);
        echo '<input type="number" name="wp_xendit_donation_minimum_amount" value="' . esc_attr($minimum_amount) . '" min="1000" step="1000" />';
        echo '<p class="description">Jumlah minimum donasi dalam Rupiah (tanpa tanda pemisah).</p>';
    }

    /**
     * Callback untuk field Suggested Amounts
     */
    public function suggested_amounts_field_callback() {
        $suggested_amounts = get_option('wp_xendit_donation_suggested_amounts', '50000,100000,250000,500000');
        echo '<input type="text" class="regular-text" name="wp_xendit_donation_suggested_amounts" value="' . esc_attr($suggested_amounts) . '" />';
        echo '<p class="description">Jumlah donasi yang disarankan, dipisahkan dengan koma (contoh: 50000,100000,250000,500000).</p>';
    }

    /**
     * Callback untuk field Redirect Pages
     */
    public function redirect_pages_field_callback() {
        $success_page = get_option('wp_xendit_donation_success_page', 0);
        $failed_page = get_option('wp_xendit_donation_failed_page', 0);
        
        wp_dropdown_pages(array(
            'name' => 'wp_xendit_donation_success_page',
            'selected' => $success_page,
            'show_option_none' => 'Pilih halaman sukses',
            'option_none_value' => '0'
        ));
        echo '<p class="description">Halaman yang akan ditampilkan setelah donasi berhasil.</p>';
        
        wp_dropdown_pages(array(
            'name' => 'wp_xendit_donation_failed_page',
            'selected' => $failed_page,
            'show_option_none' => 'Pilih halaman gagal',
            'option_none_value' => '0'
        ));
        echo '<p class="description">Halaman yang akan ditampilkan jika donasi gagal.</p>';
    }

    /**
     * Menampilkan halaman pengaturan
     */
    public function display_options_page() {
        include WP_XENDIT_DONATION_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Menampilkan halaman daftar donasi
     */
    public function display_donations_page() {
        // Buat instance dari class yang mengelola daftar donasi
        require_once WP_XENDIT_DONATION_PLUGIN_DIR . 'includes/class-donations-list-table.php';
        $donations_list_table = new WP_Xendit_Donation_List_Table();
        $donations_list_table->prepare_items();
        
        include WP_XENDIT_DONATION_PLUGIN_DIR . 'templates/admin-donations.php';
    }
}