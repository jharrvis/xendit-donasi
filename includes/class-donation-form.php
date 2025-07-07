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
        if (file_exists(WP_XENDIT_DONATION_PLUGIN_DIR . 'assets/js/script.js')) {
            wp_enqueue_script($this->plugin_name, $js_file, array('jquery'), $this->version, false);
            
            wp_localize_script($this->plugin_name, 'wp_xendit_donation', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_xendit_donation_nonce')
            ));
        }
    }

    /**
     * Mendaftarkan endpoint REST API untuk callback Xendit
     */
    public function register_endpoints() {
        // Daftarkan REST route dengan cara yang lebih aman
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Tambahkan endpoint AJAX untuk form submission
        add_action('wp_ajax_submit_donation', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_donation', array($this, 'handle_form_submission'));
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

        // Validasi jumlah donasi
        $minimum_amount = intval(get_option('wp_xendit_donation_minimum_amount', 10000));
        if (intval($donation_data['amount']) < $minimum_amount) {
            wp_send_json_error(array('message' => 'Jumlah donasi minimal Rp ' . number_format($minimum_amount, 0, ',', '.')));
            return;
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

            // Kirim URL invoice untuk redirect
            wp_send_json_success(array(
                'invoice_url' => $response['invoice_url'],
                'message' => 'Donasi berhasil dibuat, Anda akan dialihkan ke halaman pembayaran'
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
            return new WP_Error('callback_error', 'Terjadi kesalahan dalam memproses callback', array('status' => 500));
        }
    }

    /**
     * Kirim email notifikasi donasi berhasil
     */
    private function send_donation_notification($external_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xendit_donations';
        
        $donation = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE external_id = %s", $external_id)
        );
        
        if (!$donation) {
            return;
        }
        
        // Kirim email ke admin
        $admin_email = get_option('admin_email');
        $subject = '[' . get_bloginfo('name') . '] Donasi Baru Diterima';
        
        $message = "Donasi baru telah diterima:\n\n";
        $message .= "Nama: " . $donation->donor_name . "\n";
        $message .= "Email: " . $donation->donor_email . "\n";
        $message .= "Jumlah: Rp " . number_format($donation->amount, 0, ',', '.') . "\n";
        
        if (!empty($donation->message)) {
            $message .= "Pesan: " . $donation->message . "\n";
        }
        
        $message .= "\nWaktu: " . $donation->created_at . "\n";
        
        wp_mail($admin_email, $subject, $message);
        
        // Kirim email terima kasih ke donatur
        $donor_subject = 'Terima Kasih Atas Donasi Anda - ' . get_bloginfo('name');
        
        $donor_message = "Halo " . $donation->donor_name . ",\n\n";
        $donor_message .= "Terima kasih atas donasi Anda sebesar Rp " . number_format($donation->amount, 0, ',', '.') . ".\n";
        $donor_message .= "Donasi Anda sangat berarti bagi kami.\n\n";
        $donor_message .= "Salam,\n";
        $donor_message .= get_bloginfo('name');
        
        wp_mail($donation->donor_email, $donor_subject, $donor_message);
    }
}