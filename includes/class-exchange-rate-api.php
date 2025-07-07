<?php
/**
 * Kelas untuk mengambil kurs mata uang dari API eksternal
 */
class WP_Xendit_Exchange_Rate_API {

    /**
     * Update kurs mata uang dari API
     */
    public static function update_exchange_rates() {
        $api_provider = get_option('wp_xendit_donation_exchange_api', 'exchangerate-api');
        
        try {
            switch ($api_provider) {
                case 'exchangerate-api':
                    $rate = self::fetch_from_exchangerate_api();
                    break;
                case 'fixer':
                    $rate = self::fetch_from_fixer_api();
                    break;
                case 'bank-indonesia':
                    $rate = self::fetch_from_bank_indonesia();
                    break;
                default:
                    $rate = self::fetch_from_exchangerate_api();
            }
            
            if ($rate && $rate > 0) {
                self::save_exchange_rate('USD', 'IDR', $rate, $api_provider);
                update_option('wp_xendit_last_rate_update', current_time('mysql'));
                return $rate;
            }
            
        } catch (Exception $e) {
            error_log('WP Xendit Exchange Rate API Error: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Mengambil kurs dari ExchangeRate-API
     */
    private static function fetch_from_exchangerate_api() {
        $url = 'https://api.exchangerate-api.com/v4/latest/USD';
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'WP-Xendit-Donation-Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API Request Failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['rates']['IDR'])) {
            return floatval($data['rates']['IDR']);
        }
        
        throw new Exception('IDR rate not found in API response');
    }

    /**
     * Mengambil kurs dari Fixer.io
     */
    private static function fetch_from_fixer_api() {
        $api_key = get_option('wp_xendit_fixer_api_key', '');
        
        if (empty($api_key)) {
            throw new Exception('Fixer.io API key not configured');
        }
        
        $url = "http://data.fixer.io/api/latest?access_key={$api_key}&base=USD&symbols=IDR";
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            throw new Exception('Fixer API Request Failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success'] && isset($data['rates']['IDR'])) {
            return floatval($data['rates']['IDR']);
        }
        
        throw new Exception('Invalid response from Fixer.io');
    }

    /**
     * Mengambil kurs dari Bank Indonesia (simulasi)
     */
    private static function fetch_from_bank_indonesia() {
        // Note: Bank Indonesia tidak memiliki public API yang mudah diakses
        // Ini adalah simulasi atau bisa diganti dengan web scraping
        $url = 'https://www.bi.go.id/id/statistik/informasi-kurs/transaksi-bi/Default.aspx';
        
        // Untuk sekarang, return false agar fallback ke API lain
        // Implementasi web scraping bisa ditambahkan di sini
        
        return false;
    }

    /**
     * Menyimpan kurs ke database
     */
    private static function save_exchange_rate($from_currency, $to_currency, $rate, $source) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xendit_exchange_rates';
        
        return $wpdb->insert(
            $table_name,
            array(
                'from_currency' => $from_currency,
                'to_currency' => $to_currency,
                'rate' => $rate,
                'source' => $source,
                'is_manual' => 0
            ),
            array('%s', '%s', '%f', '%s', '%d')
        );
    }

    /**
     * Mendapatkan history kurs
     */
    public static function get_rate_history($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xendit_exchange_rates';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE from_currency = 'USD' AND to_currency = 'IDR'
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Manual update kurs oleh admin
     */
    public static function manual_update_rate($rate) {
        if (!is_numeric($rate) || $rate <= 0) {
            return false;
        }
        
        return self::save_exchange_rate('USD', 'IDR', floatval($rate), 'manual');
    }
}