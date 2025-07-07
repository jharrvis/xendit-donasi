<?php
/**
 * Kelas untuk menangani internationalization (i18n)
 */
class WP_Xendit_Donation_i18n {
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-xendit-donation',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}