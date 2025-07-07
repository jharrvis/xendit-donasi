<?php
/**
 * Kelas untuk menangani form donasi dan interaksi dengan Xendit
 */
class WP_Xendit_Donation_Form {

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
     * Mendaftarkan stylesheet untuk form donasi
     */
    public function enqueue_styles() {
        $css_file = WP_XENDIT_DONATION_PLUGIN_URL . 'assets/css/style.css';
        if (file_exists(WP_XENDIT_DONATION_PLUGIN_DIR . 'assets/css/style.css')) {
            wp_enqueue_style($this->plugin_name, $css_file, array(), $this->version, 'all');
        }
    }

    /**
     * Mendaftarkan script untuk form donasi
     */
    public function enqueue_scripts() {
        $js_file = WP_XENDIT_DONATION_PLUGIN_URL . 'assets/js/script.js';
        $currency_js = WP_XENDIT_DONATION_PLUGIN_URL . 'assets/js/currency-converter.js';
        
        if (file_exists(WP_XENDIT_DONATION_PLUGIN_DIR . 'assets/js/script.js')) {
            wp_enqueue_script($this->plugin_name, $js_file, array('jquery'), $this->version, false);
        }
        
        if (file_exists(WP_XENDIT_DONATION_PLUGIN_DIR . 'assets/js/currency-converter.js')) {
            wp_enqueue_script($this->plugin_name . '-currency', $currency_js, array('jquery'), $this->version, false);
        }

        // Localize script dengan data yang diperlukan
        wp_localize_script($this->plugin_name, 'wp_xendit_donation', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_xendit_donation_nonce')
        ));

        // Register AJAX handlers
        add_action('wp_ajax_submit_donation', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_donation', array($this, 'handle_form_submission'));
        add_action('wp_ajax_get_exchange_rate', array($this, 'get_current_exchange_rate'));
        add_action('wp_ajax_nopriv_get_exchange_rate', array($this, 'get_current_exchange_rate'));
    }

    /**
     * Mendaftarkan REST routes
     */
    public function register_rest_routes() {
        register_rest_route('wp-xendit-donation/v1', '/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_xendit_callback'),
            'permission_callback' => '__return_true',
            'args' => array()
        ));
    }

    /**
     * Menampilkan form donasi (shortcode handler)
     */
    public function display_donation_form($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Donasi',
            'description' => 'Dukung kami dengan donasi Anda',
            'button_text' => 'Donasi Sekarang',
            'minimum_amount' => 10000,
            'suggested_amounts' => '50000,100000,250000,500000'
        ), $atts);

        ob_start();
        
        // Cek apakah template file ada
        $template_file = WP_XENDIT_DONATION_PLUGIN_DIR . 'templates/donation-form.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>Template form donasi tidak ditemukan.</p>';
        }
        
        return ob_get_clean();
    }

    /**
     * Get current exchange rate via AJAX
     */
    public function get_current_exchange_rate() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_xendit_donation_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        $rate = WP_Xendit_Currency_Converter::get_exchange_rate('USD', 'IDR');
        
        wp_send_json_success(array(
            'rate' => $rate,
            'formatted' => WP_Xendit_Currency_Converter::format_currency($rate, 'IDR')
        ));
    }

    /**
     * Menangani submission form donasi
     */
    public function handle_form_submission() {
        // Verifikasi nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_xendit_donation_nonce')) {
            wp_send_json_error(array('message' => 'Verifikasi keamanan gagal'));
            return;
        }

        // Validasi data form
        $donation_data = array();
        $required_fields = array('donor_name', 'donor_email', 'amount');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Semua field wajib diisi'));
                return;
            }
            $donation_data[$field] = sanitize_text_field($_POST[$field]);
        }

        // Handle currency
        $currency = isset($_POST['selected_currency']) ? sanitize_text_field($_POST['selected_currency']) : 'IDR';
        if (!WP_Xendit_Currency_Converter::is_supported_currency($currency)) {
            wp_send_json_error(array('message' => 'Mata uang tidak didukung'));
            return;
        }

        $donation_data['currency'] = $currency;
        $donation_data['original_amount'] = floatval($donation_data['amount']);

        // Validasi jumlah donasi berdasarkan mata uang
        $minimum_amount = WP_Xendit_Currency_Converter::get_minimum_amount($currency);
        if (floatval($donation_data['amount']) < $minimum_amount) {
            $min_formatted = WP_Xendit_Currency_Converter::format_currency($minimum_amount, $currency);
            wp_send_json_error(array('message' => 'Jumlah donasi minimal ' . $min_formatted));
            return;
        }

        // Konversi ke IDR jika diperlukan
        if ($currency === 'USD') {
            $exchange_rate = WP_Xendit_Currency_Converter::get_exchange_rate('USD', 'IDR');
            $donation_data['exchange_rate'] = $exchange_rate;
            $donation_data['amount'] = WP_Xendit_Currency_Converter::convert($donation_data['original_amount'], 'USD', 'IDR');
            
            // Pastikan amount dalam IDR (yang dikirim ke Xendit) adalah integer
            $donation_data['amount'] = round($donation_data['amount']);
        } else {
            $donation_data['exchange_rate'] = 1;
        }

        // Tambahkan field opsional
        if (!empty($_POST['donor_phone'])) {
            $donation_data['donor_phone'] = sanitize_text_field($_POST['donor_phone']);
        }
        
        if (!empty($_POST['message'])) {
            $donation_data['message'] = sanitize_textarea_field($_POST['message']);
        }

        // Buat invoice di Xendit
        if (class_exists('WP_Xendit_Donation_API')) {
            $xendit_api = new WP_Xendit_Donation_API();
            $response = $xendit_api->create_invoice($donation_data);

            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => $response->get_error_message()));
                return;
            }

            // Success message dengan informasi konversi jika perlu
            $success_message = 'Donasi berhasil dibuat, Anda akan dialihkan ke halaman pembayaran';
            if ($currency === 'USD') {
                $original_formatted = WP_Xendit_Currency_Converter::format_currency($donation_data['original_amount'], 'USD');
                $converted_formatted = WP_Xendit_Currency_Converter::format_currency($donation_data['amount'], 'IDR');
                $success_message .= '. Donasi ' . $original_formatted . ' telah dikonversi menjadi ' . $converted_formatted;
            }

            // Kirim URL invoice untuk redirect
            wp_send_json_success(array(
                'invoice_url' => $response['invoice_url'],
                'message' => $success_message
            ));
        } else {
            wp_send_json_error(array('message' => 'Xendit API class tidak tersedia'));
        }
    }

    /**
     * Menangani callback dari Xendit
     */
    public function handle_xendit_callback($request) {
        try {
            $params = $request->get_params();
            
            // Validasi callback dari Xendit
            $callback_token = get_option('wp_xendit_donation_callback_token', '');
            $received_token = $request->get_header('X-CALLBACK-TOKEN');
            
            if (!empty($callback_token) && $callback_token !== $received_token) {
                return new WP_Error('invalid_token', 'Token tidak valid', array('status' => 403));
            }

            // Validasi data callback
            if (empty($params['external_id']) || empty($params['status'])) {
                return new WP_Error('invalid_data', 'Data tidak valid', array('status' => 400));
            }

            // Update status donasi
            if (class_exists('WP_Xendit_Donation_API')) {
                $xendit_api = new WP_Xendit_Donation_API();
                $xendit_api->update_donation_status($params['external_id'], $params['status']);

                // Kirim email notifikasi jika donasi berhasil
                if ($params['status'] === 'PAID') {
                    $this->send_donation_notification($params['external_id']);
                }
            }

            return array('status' => 'success');
            
        } catch (Exception $e) {
            error_log('WP Xendit Donation Callback Error: ' . $e->getMessage());
            return new WP_Error('callback_error', 'Internal error', array('status' => 500));
        }
    }

    /**
     * Mengirim notifikasi email donasi dengan informasi mata uang
     */
    private function send_donation_notification($external_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xendit_donations';
        
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE external_id = %s",
            $external_id
        ));
        
        if (!$donation) {
            return;
        }
        
        // Kirim email ke admin
        $admin_email = get_option('admin_email');
        $subject = 'Donasi Baru Diterima - ' . get_bloginfo('name');
        
        $message = "Donasi baru telah diterima:\n\n";
        $message .= "Nama: " . $donation->donor_name . "\n";
        $message .= "Email: " . $donation->donor_email . "\n";
        
        // Format amount berdasarkan currency
        if ($donation->currency === 'USD' && $donation->original_amount) {
            $message .= "Jumlah: " . WP_Xendit_Currency_Converter::format_currency($donation->original_amount, 'USD');
            $message .= " (Rp " . number_format($donation->amount, 0, ',', '.') . ")\n";
            $message .= "Kurs: 1 USD = Rp " . number_format($donation->exchange_rate, 0, ',', '.') . "\n";
        } else {
            $message .= "Jumlah: Rp " . number_format($donation->amount, 0, ',', '.') . "\n";
        }
        
        if (!empty($donation->message)) {
            $message .= "Pesan: " . $donation->message . "\n";
        }
        
        $message .= "\nWaktu: " . $donation->created_at . "\n";
        
        wp_mail($admin_email, $subject, $message);
        
        // Kirim email terima kasih ke donatur
        $donor_subject = 'Terima Kasih Atas Donasi Anda - ' . get_bloginfo('name');
        
        $donor_message = "Halo " . $donation->donor_name . ",\n\n";
        $donor_message .= "Terima kasih atas donasi Anda ";
        
        if ($donation->currency === 'USD' && $donation->original_amount) {
            $donor_message .= "sebesar " . WP_Xendit_Currency_Converter::format_currency($donation->original_amount, 'USD');
            $donor_message .= " (setara Rp " . number_format($donation->amount, 0, ',', '.') . ")";
        } else {
            $donor_message .= "sebesar Rp " . number_format($donation->amount, 0, ',', '.');
        }
        
        $donor_message .= ".\nDonasi Anda sangat berarti bagi kami.\n\n";
        $donor_message .= "Salam,\n";
        $donor_message .= get_bloginfo('name');
        
        wp_mail($donation->donor_email, $donor_subject, $donor_message);
    }
}