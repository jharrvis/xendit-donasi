<?php
/**
 * Kelas untuk aktivasi plugin
 */
class WP_Xendit_Donation_Activator {

    /**
     * Menjalankan proses aktivasi plugin
     */
    public static function activate() {
        // Buat tabel donations jika belum ada
        self::create_donations_table();
        
        // Buat tabel exchange rates
        self::create_exchange_rates_table();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron job untuk update kurs
        self::schedule_exchange_rate_update();
    }

    /**
     * Membuat tabel donasi
     */
    private static function create_donations_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'xendit_donations';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            external_id varchar(100) NOT NULL,
            donor_name varchar(100) NOT NULL,
            donor_email varchar(100) NOT NULL,
            donor_phone varchar(20),
            amount decimal(15,2) NOT NULL,
            currency varchar(3) DEFAULT 'IDR',
            original_amount decimal(15,2),
            exchange_rate decimal(10,4),
            message text,
            status varchar(20) DEFAULT 'pending',
            invoice_id varchar(100),
            invoice_url text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY external_id (external_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Membuat tabel kurs mata uang
     */
    private static function create_exchange_rates_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'xendit_exchange_rates';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            from_currency varchar(3) NOT NULL,
            to_currency varchar(3) NOT NULL,
            rate decimal(10,4) NOT NULL,
            source varchar(50) DEFAULT 'api',
            is_manual tinyint(1) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY currency_pair (from_currency, to_currency),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert default USD to IDR rate
        $wpdb->insert(
            $table_name,
            array(
                'from_currency' => 'USD',
                'to_currency' => 'IDR',
                'rate' => 15000.00,
                'source' => 'default',
                'is_manual' => 1
            )
        );
    }

    /**
     * Set opsi default
     */
    private static function set_default_options() {
        add_option('wp_xendit_donation_api_key', '');
        add_option('wp_xendit_donation_mode', 'sandbox');
        add_option('wp_xendit_donation_callback_token', wp_generate_password(32, false));
        add_option('wp_xendit_donation_minimum_amount', 10000);
        add_option('wp_xendit_donation_suggested_amounts', '50000,100000,250000,500000');
        add_option('wp_xendit_donation_success_page', '');
        add_option('wp_xendit_donation_failed_page', '');
        
        // New currency options
        add_option('wp_xendit_donation_enable_usd', 1);
        add_option('wp_xendit_donation_usd_minimum', 1);
        add_option('wp_xendit_donation_usd_suggested', '5,10,25,50');
        add_option('wp_xendit_donation_exchange_api', 'exchangerate-api');
        add_option('wp_xendit_donation_auto_update_rate', 1);
        add_option('wp_xendit_donation_update_interval', 6); // hours
    }

    /**
     * Schedule cron job untuk update kurs
     */
    private static function schedule_exchange_rate_update() {
        if (!wp_next_scheduled('wp_xendit_update_exchange_rates')) {
            wp_schedule_event(time(), 'sixhourly', 'wp_xendit_update_exchange_rates');
        }
    }
}