<?php
/**
 * Kelas yang dijalankan saat plugin diaktifkan
 */
class WP_Xendit_Donation_Activator {
    
    /**
     * Dijalankan saat plugin diaktifkan
     */
    public static function activate() {
        self::create_database_tables();
    }
    
    /**
     * Membuat tabel database untuk plugin
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'xendit_donations';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            donor_name varchar(100) NOT NULL,
            donor_email varchar(100) NOT NULL,
            donor_phone varchar(20) DEFAULT '',
            amount decimal(15,2) NOT NULL,
            message text DEFAULT '',
            external_id varchar(50) NOT NULL,
            invoice_id varchar(50) NOT NULL,
            invoice_url text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY external_id (external_id),
            KEY invoice_id (invoice_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}