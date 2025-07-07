<?php
/**
 * Kelas untuk menangani area admin/dashboard WordPress
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
     * Mendaftarkan stylesheet untuk area admin
     */
    public function enqueue_styles() {
        $css_file = WP_XENDIT_DONATION_PLUGIN_URL . 'assets/css/admin.css';
        if (file_exists(WP_XENDIT_DONATION_PLUGIN_DIR . 'assets/css/admin.css')) {
            wp_enqueue_style($this->plugin_name, $css_file, array(), $this->version, 'all');
        }
    }

    /**
     * Mendaftarkan script untuk area admin
     */
    public function enqueue_scripts() {
        $js_file = WP_XENDIT_DONATION_PLUGIN_URL . 'assets/js/admin.js';
        if (file_exists(WP_XENDIT_DONATION_PLUGIN_DIR . 'assets/js/admin.js')) {
            wp_enqueue_script($this->plugin_name, $js_file, array('jquery'), $this->version, false);
        }

        // Localize script untuk AJAX
        wp_localize_script($this->plugin_name, 'wp_xendit_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_xendit_admin_nonce')
        ));

        // Register AJAX handlers
        add_action('wp_ajax_update_exchange_rate', array($this, 'ajax_update_exchange_rate'));
        add_action('wp_ajax_manual_rate_update', array($this, 'ajax_manual_rate_update'));
    }

    /**
     * Menambahkan halaman opsi plugin ke menu admin
     */
    public function add_options_page() {
        add_menu_page(
            'Xendit Donation Settings',
            'Xendit Donation',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_options_page'),
            'dashicons-heart',
            30
        );

        add_submenu_page(
            $this->plugin_name,
            'Donation List',
            'Donations',
            'manage_options',
            $this->plugin_name . '-donations',
            array($this, 'display_donations_page')
        );

        add_submenu_page(
            $this->plugin_name,
            'Exchange Rates',
            'Exchange Rates',
            'manage_options',
            $this->plugin_name . '-exchange-rates',
            array($this, 'display_exchange_rates_page')
        );
    }

    /**
     * Menampilkan halaman pengaturan plugin
     */
    public function display_options_page() {
        include_once WP_XENDIT_DONATION_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Menampilkan halaman daftar donasi
     */
    public function display_donations_page() {
        if (!class_exists('WP_Xendit_Donations_List_Table')) {
            require_once WP_XENDIT_DONATION_PLUGIN_DIR . 'includes/class-donations-list-table.php';
        }
        
        $donations_list_table = new WP_Xendit_Donations_List_Table();
        $donations_list_table->prepare_items();

        include_once WP_XENDIT_DONATION_PLUGIN_DIR . 'templates/admin-donations.php';
    }

    /**
     * Menampilkan halaman kurs mata uang
     */
    public function display_exchange_rates_page() {
        include_once WP_XENDIT_DONATION_PLUGIN_DIR . 'templates/admin-exchange-rates.php';
    }

    /**
     * Mendaftarkan pengaturan plugin
     */
    public function register_settings() {
        // Basic settings
        register_setting($this->plugin_name, 'wp_xendit_donation_api_key');
        register_setting($this->plugin_name, 'wp_xendit_donation_mode');
        register_setting($this->plugin_name, 'wp_xendit_donation_callback_token');
        register_setting($this->plugin_name, 'wp_xendit_donation_minimum_amount');
        register_setting($this->plugin_name, 'wp_xendit_donation_suggested_amounts');
        register_setting($this->plugin_name, 'wp_xendit_donation_success_page');
        register_setting($this->plugin_name, 'wp_xendit_donation_failed_page');
        
        // Currency settings
        register_setting($this->plugin_name, 'wp_xendit_donation_enable_usd');
        register_setting($this->plugin_name, 'wp_xendit_donation_usd_minimum');
        register_setting($this->plugin_name, 'wp_xendit_donation_usd_suggested');
        register_setting($this->plugin_name, 'wp_xendit_donation_exchange_api');
        register_setting($this->plugin_name, 'wp_xendit_donation_auto_update_rate');
        register_setting($this->plugin_name, 'wp_xendit_donation_update_interval');
        register_setting($this->plugin_name, 'wp_xendit_fixer_api_key');
        
        add_settings_section(
            'wp_xendit_donation_general_section',
            'General Settings',
            array($this, 'general_section_callback'),
            $this->plugin_name
        );
        
        add_settings_section(
            'wp_xendit_donation_currency_section',
            'Currency Settings',
            array($this, 'currency_section_callback'),
            $this->plugin_name
        );
        
        // Basic fields
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
            'Minimum Donation Amount (IDR)',
            array($this, 'minimum_amount_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_general_section'
        );
        
        add_settings_field(
            'wp_xendit_donation_suggested_amounts',
            'Suggested Donation Amounts (IDR)',
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

        // Currency fields
        add_settings_field(
            'wp_xendit_donation_enable_usd',
            'Enable USD Currency',
            array($this, 'enable_usd_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_currency_section'
        );

        add_settings_field(
            'wp_xendit_donation_usd_settings',
            'USD Settings',
            array($this, 'usd_settings_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_currency_section'
        );

        add_settings_field(
            'wp_xendit_donation_exchange_settings',
            'Exchange Rate Settings',
            array($this, 'exchange_settings_field_callback'),
            $this->plugin_name,
            'wp_xendit_donation_currency_section'
        );
    }

    /**
     * Callback untuk section pengaturan umum
     */
    public function general_section_callback() {
        echo '<p>Konfigurasi pengaturan umum untuk integrasi donasi dengan Xendit.</p>';
    }

    /**
     * Callback untuk section pengaturan mata uang
     */
    public function currency_section_callback() {
        echo '<p>Konfigurasi pengaturan mata uang dan kurs konversi.</p>';
    }

    /**
     * Callback untuk field API Key
     */
    public function api_key_field_callback() {
        $api_key = get_option('wp_xendit_donation_api_key', '');
        echo '<input type="password" class="regular-text" name="wp_xendit_donation_api_key" value="' . esc_attr($api_key) . '" />';
        echo ' <button type="button" class="button toggle-api-key">Tampilkan</button>';
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
        echo '<p class="description">Pilih mode sandbox untuk testing atau production untuk live.</p>';
    }

    /**
     * Callback untuk field Callback Token
     */
    public function callback_token_field_callback() {
        $token = get_option('wp_xendit_donation_callback_token', '');
        echo '<input type="text" class="regular-text" name="wp_xendit_donation_callback_token" value="' . esc_attr($token) . '" readonly />';
        echo '<p class="description">Token ini digunakan untuk mengamankan callback dari Xendit. Salin token ini ke pengaturan webhook di dashboard Xendit.</p>';
    }

    /**
     * Callback untuk field Minimum Amount
     */
    public function minimum_amount_field_callback() {
        $amount = get_option('wp_xendit_donation_minimum_amount', 10000);
        echo '<input type="number" class="regular-text" name="wp_xendit_donation_minimum_amount" value="' . esc_attr($amount) . '" min="1000" step="1000" />';
        echo '<p class="description">Jumlah minimum donasi dalam Rupiah.</p>';
    }

    /**
     * Callback untuk field Suggested Amounts
     */
    public function suggested_amounts_field_callback() {
        $amounts = get_option('wp_xendit_donation_suggested_amounts', '50000,100000,250000,500000');
        echo '<input type="text" class="regular-text" name="wp_xendit_donation_suggested_amounts" value="' . esc_attr($amounts) . '" />';
        echo '<p class="description">Nominal donasi yang disarankan (dalam Rupiah), pisahkan dengan koma. Contoh: 50000,100000,250000,500000</p>';
    }

    /**
     * Callback untuk field Redirect Pages
     */
    public function redirect_pages_field_callback() {
        $success_page = get_option('wp_xendit_donation_success_page', '');
        $failed_page = get_option('wp_xendit_donation_failed_page', '');
        
        echo '<p><label for="success_page">Success Page:</label><br>';
        wp_dropdown_pages(array(
            'name' => 'wp_xendit_donation_success_page',
            'id' => 'success_page',
            'selected' => $success_page,
            'show_option_none' => 'Select a page...'
        ));
        echo '</p>';
        
        echo '<p><label for="failed_page">Failed Page:</label><br>';
        wp_dropdown_pages(array(
            'name' => 'wp_xendit_donation_failed_page',
            'id' => 'failed_page',
            'selected' => $failed_page,
            'show_option_none' => 'Select a page...'
        ));
        echo '</p>';
        
        echo '<p class="description">Halaman tujuan redirect setelah pembayaran berhasil atau gagal.</p>';
    }

    /**
     * Callback untuk enable USD field
     */
    public function enable_usd_field_callback() {
        $enabled = get_option('wp_xendit_donation_enable_usd', 1);
        echo '<label>';
        echo '<input type="checkbox" name="wp_xendit_donation_enable_usd" value="1" ' . checked($enabled, 1, false) . ' />';
        echo ' Aktifkan donasi dalam mata uang USD';
        echo '</label>';
        echo '<p class="description">Centang untuk mengaktifkan opsi donasi dalam USD yang akan otomatis dikonversi ke IDR.</p>';
    }

    /**
     * Callback untuk USD settings
     */
    public function usd_settings_field_callback() {
        $usd_minimum = get_option('wp_xendit_donation_usd_minimum', 1);
        $usd_suggested = get_option('wp_xendit_donation_usd_suggested', '5,10,25,50');
        
        echo '<p><label for="usd_minimum">Minimum USD Amount:</label><br>';
        echo '<input type="number" id="usd_minimum" name="wp_xendit_donation_usd_minimum" value="' . esc_attr($usd_minimum) . '" min="1" step="1" />';
        echo '<small> USD</small></p>';
        
        echo '<p><label for="usd_suggested">Suggested USD Amounts:</label><br>';
        echo '<input type="text" id="usd_suggested" class="regular-text" name="wp_xendit_donation_usd_suggested" value="' . esc_attr($usd_suggested) . '" />';
        echo '<br><small>Nominal dalam USD, pisahkan dengan koma. Contoh: 5,10,25,50</small></p>';
    }

    /**
     * Callback untuk exchange rate settings
     */
    public function exchange_settings_field_callback() {
        $exchange_api = get_option('wp_xendit_donation_exchange_api', 'exchangerate-api');
        $auto_update = get_option('wp_xendit_donation_auto_update_rate', 1);
        $update_interval = get_option('wp_xendit_donation_update_interval', 6);
        $fixer_key = get_option('wp_xendit_fixer_api_key', '');
        
        echo '<p><label for="exchange_api">Exchange Rate API:</label><br>';
        echo '<select id="exchange_api" name="wp_xendit_donation_exchange_api">';
        echo '<option value="exchangerate-api" ' . selected($exchange_api, 'exchangerate-api', false) . '>ExchangeRate-API (Free)</option>';
        echo '<option value="fixer" ' . selected($exchange_api, 'fixer', false) . '>Fixer.io (Requires API Key)</option>';
        echo '<option value="bank-indonesia" ' . selected($exchange_api, 'bank-indonesia', false) . '>Bank Indonesia (Experimental)</option>';
        echo '</select></p>';
        
        echo '<p><label>';
        echo '<input type="checkbox" name="wp_xendit_donation_auto_update_rate" value="1" ' . checked($auto_update, 1, false) . ' />';
        echo ' Auto-update exchange rates</label></p>';
        
        echo '<p><label for="update_interval">Update Interval:</label><br>';
        echo '<select id="update_interval" name="wp_xendit_donation_update_interval">';
        echo '<option value="1" ' . selected($update_interval, 1, false) . '>Every hour</option>';
        echo '<option value="6" ' . selected($update_interval, 6, false) . '>Every 6 hours</option>';
        echo '<option value="12" ' . selected($update_interval, 12, false) . '>Every 12 hours</option>';
        echo '<option value="24" ' . selected($update_interval, 24, false) . '>Daily</option>';
        echo '</select></p>';
        
        echo '<p><label for="fixer_key">Fixer.io API Key (optional):</label><br>';
        echo '<input type="text" id="fixer_key" class="regular-text" name="wp_xendit_fixer_api_key" value="' . esc_attr($fixer_key) . '" />';
        echo '<br><small>Required only if using Fixer.io API</small></p>';
        
        // Current rate display
        $current_rate = WP_Xendit_Currency_Converter::get_exchange_rate();
        $last_update = get_option('wp_xendit_last_rate_update', 'Never');
        
        echo '<div class="current-rate-display">';
        echo '<h4>Current Exchange Rate</h4>';
        echo '<p><strong>1 USD = ' . WP_Xendit_Currency_Converter::format_currency($current_rate, 'IDR') . '</strong></p>';
        echo '<p><small>Last updated: ' . esc_html($last_update) . '</small></p>';
        echo '<p>';
        echo '<button type="button" class="button button-secondary" id="update-rate-now">Update Rate Now</button> ';
        echo '<button type="button" class="button button-secondary" id="manual-rate-update">Manual Update</button>';
        echo '</p>';
        echo '</div>';
        
        // Manual rate input (hidden by default)
        echo '<div id="manual-rate-input" style="display: none; margin-top: 10px;">';
        echo '<label for="manual_rate">Manual Rate (1 USD = ? IDR):</label><br>';
        echo '<input type="number" id="manual_rate" step="0.01" min="1" placeholder="15000" />';
        echo ' <button type="button" class="button button-primary" id="save-manual-rate">Save Rate</button>';
        echo ' <button type="button" class="button button-secondary" id="cancel-manual-rate">Cancel</button>';
        echo '</div>';
    }

    /**
     * AJAX handler untuk update exchange rate
     */
    public function ajax_update_exchange_rate() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_xendit_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $rate = WP_Xendit_Exchange_Rate_API::update_exchange_rates();
        
        if ($rate) {
            wp_send_json_success(array(
                'rate' => $rate,
                'formatted' => WP_Xendit_Currency_Converter::format_currency($rate, 'IDR'),
                'message' => 'Exchange rate updated successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update exchange rate'));
        }
    }

    /**
     * AJAX handler untuk manual rate update
     */
    public function ajax_manual_rate_update() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_xendit_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $rate = floatval($_POST['rate']);
        
        if ($rate <= 0) {
            wp_send_json_error(array('message' => 'Invalid rate value'));
            return;
        }

        $success = WP_Xendit_Exchange_Rate_API::manual_update_rate($rate);
        
        if ($success) {
            update_option('wp_xendit_last_rate_update', current_time('mysql') . ' (Manual)');
            wp_send_json_success(array(
                'rate' => $rate,
                'formatted' => WP_Xendit_Currency_Converter::format_currency($rate, 'IDR'),
                'message' => 'Manual rate saved successfully'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save manual rate'));
        }
    }
}