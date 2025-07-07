<?php
/**
 * Kelas untuk menangani konversi mata uang
 */
class WP_Xendit_Currency_Converter {

    /**
     * Mendapatkan kurs terbaru dari database
     */
    public static function get_exchange_rate($from_currency = 'USD', $to_currency = 'IDR') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xendit_exchange_rates';
        
        $rate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE from_currency = %s AND to_currency = %s 
             ORDER BY created_at DESC LIMIT 1",
            $from_currency,
            $to_currency
        ));
        
        return $rate ? floatval($rate->rate) : 15000; // Default fallback
    }

    /**
     * Konversi amount dari satu mata uang ke mata uang lain
     */
    public static function convert($amount, $from_currency = 'USD', $to_currency = 'IDR') {
        if ($from_currency === $to_currency) {
            return $amount;
        }
        
        $rate = self::get_exchange_rate($from_currency, $to_currency);
        return $amount * $rate;
    }

    /**
     * Format currency sesuai dengan jenis mata uang
     */
    public static function format_currency($amount, $currency = 'IDR') {
        switch ($currency) {
            case 'USD':
                return '$' . number_format($amount, 2, '.', ',');
            case 'IDR':
            default:
                return 'Rp ' . number_format($amount, 0, ',', '.');
        }
    }

    /**
     * Mendapatkan simbol mata uang
     */
    public static function get_currency_symbol($currency) {
        $symbols = array(
            'USD' => '$',
            'IDR' => 'Rp'
        );
        
        return isset($symbols[$currency]) ? $symbols[$currency] : '';
    }

    /**
     * Mendapatkan informasi detail konversi
     */
    public static function get_conversion_details($amount, $from_currency = 'USD', $to_currency = 'IDR') {
        $rate = self::get_exchange_rate($from_currency, $to_currency);
        $converted_amount = self::convert($amount, $from_currency, $to_currency);
        
        return array(
            'original_amount' => $amount,
            'original_currency' => $from_currency,
            'converted_amount' => $converted_amount,
            'converted_currency' => $to_currency,
            'exchange_rate' => $rate,
            'formatted_original' => self::format_currency($amount, $from_currency),
            'formatted_converted' => self::format_currency($converted_amount, $to_currency)
        );
    }

    /**
     * Validasi mata uang yang didukung
     */
    public static function is_supported_currency($currency) {
        $supported = array('IDR', 'USD');
        return in_array(strtoupper($currency), $supported);
    }

    /**
     * Mendapatkan minimum amount berdasarkan mata uang
     */
    public static function get_minimum_amount($currency = 'IDR') {
        switch ($currency) {
            case 'USD':
                return intval(get_option('wp_xendit_donation_usd_minimum', 1));
            case 'IDR':
            default:
                return intval(get_option('wp_xendit_donation_minimum_amount', 10000));
        }
    }

    /**
     * Mendapatkan suggested amounts berdasarkan mata uang
     */
    public static function get_suggested_amounts($currency = 'IDR') {
        switch ($currency) {
            case 'USD':
                return get_option('wp_xendit_donation_usd_suggested', '5,10,25,50');
            case 'IDR':
            default:
                return get_option('wp_xendit_donation_suggested_amounts', '50000,100000,250000,500000');
        }
    }
}