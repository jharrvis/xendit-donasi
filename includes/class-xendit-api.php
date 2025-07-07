<?php
/**
 * Kelas untuk menangani integrasi dengan Xendit API
 */
class WP_Xendit_Donation_API {

    /**
     * API Key Xendit
     */
    private $api_key;

    /**
     * Mode API (sandbox atau production)
     */
    private $mode;

    /**
     * Base URL API Xendit
     */
    private $api_url;

    /**
     * Inisialisasi kelas
     */
    public function __construct() {
        $this->api_key = get_option('wp_xendit_donation_api_key', '');
        $this->mode = get_option('wp_xendit_donation_mode', 'sandbox');
        $this->api_url = ($this->mode === 'production') 
            ? 'https://api.xendit.co' 
            : 'https://api.xendit.co';  // Xendit menggunakan URL yang sama untuk sandbox & production
    }

    /**
     * Membuat invoice untuk pembayaran donasi
     * 
     * @param array $donation_data Data donasi
     * @return array|WP_Error Response dari Xendit API atau error
     */
    public function create_invoice($donation_data) {
        // Validasi data donasi
        if (empty($donation_data['amount']) || empty($donation_data['donor_name']) || empty($donation_data['donor_email'])) {
            return new WP_Error('invalid_data', 'Data donasi tidak lengkap');
        }

        $success_redirect_url = home_url('/donation-success');
        $failure_redirect_url = home_url('/donation-failed');

        $callback_url = home_url('/wp-json/wp-xendit-donation/v1/callback');

        $params = array(
            'external_id' => 'donation-' . time() . '-' . wp_rand(100, 999),
            'amount' => $donation_data['amount'],
            'payer_email' => $donation_data['donor_email'],
            'description' => isset($donation_data['message']) ? $donation_data['message'] : 'Donasi',
            'success_redirect_url' => $success_redirect_url,
            'failure_redirect_url' => $failure_redirect_url,
            'callback_url' => $callback_url,
            'should_send_email' => true,
            'customer' => array(
                'given_names' => $donation_data['donor_name'],
                'email' => $donation_data['donor_email'],
                'mobile_number' => isset($donation_data['donor_phone']) ? $donation_data['donor_phone'] : ''
            ),
            'items' => array(
                array(
                    'name' => 'Donasi',
                    'quantity' => 1,
                    'price' => $donation_data['amount'],
                    'category' => 'donation'
                )
            )
        );

        $response = $this->request('POST', '/v2/invoices', $params);
        
        if (is_wp_error($response)) {
            return $response;
        }

        // Simpan data donasi di database
        $this->save_donation($donation_data, $params['external_id'], $response);

        return $response;
    }

    /**
     * Menyimpan data donasi ke database
     * 
     * @param array $donation_data Data donasi dari form
     * @param string $external_id ID eksternal yang digunakan untuk Xendit
     * @param array $invoice_data Data invoice dari Xendit
     */
    private function save_donation($donation_data, $external_id, $invoice_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xendit_donations';

        $wpdb->insert(
            $table_name,
            array(
                'donor_name' => $donation_data['donor_name'],
                'donor_email' => $donation_data['donor_email'],
                'donor_phone' => isset($donation_data['donor_phone']) ? $donation_data['donor_phone'] : '',
                'amount' => $donation_data['amount'],
                'message' => isset($donation_data['message']) ? $donation_data['message'] : '',
                'external_id' => $external_id,
                'invoice_id' => $invoice_data['id'],
                'invoice_url' => $invoice_data['invoice_url'],
                'status' => 'pending',
                'created_at' => current_time('mysql')
            )
        );
    }

    /**
     * Memperbarui status donasi
     * 
     * @param string $external_id ID eksternal donasi
     * @param string $status Status baru
     * @return bool Sukses atau gagal
     */
    public function update_donation_status($external_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xendit_donations';

        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('external_id' => $external_id)
        );

        return ($result !== false);
    }

    /**
     * Melakukan request ke Xendit API
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $params Parameter request
     * @return array|WP_Error Response dari API atau error
     */
    private function request($method, $endpoint, $params = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API Key Xendit belum dikonfigurasi');
        }

        $url = $this->api_url . $endpoint;

        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($this->api_key . ':'),
            'Content-Type' => 'application/json'
        );

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        );

        if (!empty($params) && $method !== 'GET') {
            $args['body'] = json_encode($params);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) >= 400) {
            return new WP_Error(
                'xendit_api_error', 
                isset($data['message']) ? $data['message'] : 'Terjadi kesalahan saat menghubungi Xendit API'
            );
        }

        return $data;
    }
}