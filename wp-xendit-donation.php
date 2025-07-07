<?php
/**
 * Plugin Name: WP Xendit Donation
 * Plugin URI: https://yourwebsite.com/wp-xendit-donation
 * Description: Plugin WordPress untuk integrasi pembayaran donasi menggunakan Xendit payment gateway
 * Version: 1.0.0
 * Author: MarisaListi
 * Author URI: https://yourwebsite.com
 * Text Domain: wp-xendit-donation
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.2
 * PHP Requires: 7.4
 */

// Jika file ini dipanggil langsung, abort.
if (!defined('WPINC')) {
    die;
}

// Definisi konstanta plugin
define('WP_XENDIT_DONATION_VERSION', '1.0.0');
define('WP_XENDIT_DONATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_XENDIT_DONATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_XENDIT_DONATION_PLUGIN_FILE', __FILE__);

/**
 * Kode yang dijalankan saat plugin diaktifkan.
 */
function activate_wp_xendit_donation() {
    // Load file activator
    require_once WP_XENDIT_DONATION_PLUGIN_DIR . 'includes/class-activator.php';
    WP_Xendit_Donation_Activator::activate();
}

/**
 * Kode yang dijalankan saat plugin dinonaktifkan.
 */
function deactivate_wp_xendit_donation() {
    // Load file deactivator
    require_once WP_XENDIT_DONATION_PLUGIN_DIR . 'includes/class-deactivator.php';
    WP_Xendit_Donation_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_xendit_donation');
register_deactivation_hook(__FILE__, 'deactivate_wp_xendit_donation');

/**
 * Memulai plugin setelah semua plugin dimuat
 */
function init_wp_xendit_donation() {
    // Pastikan WordPress sudah dimuat sepenuhnya
    if (!function_exists('get_option')) {
        return;
    }
    
    // Load kelas utama plugin
    require_once WP_XENDIT_DONATION_PLUGIN_DIR . 'includes/class-wp-xendit-donation.php';
    
    // Jalankan plugin
    $plugin = new WP_Xendit_Donation();
    $plugin->run();
}

// Hook ke plugins_loaded untuk memastikan WordPress sudah siap
add_action('plugins_loaded', 'init_wp_xendit_donation');

/**
 * Hook untuk menampilkan error jika PHP version tidak memadai
 */
function wp_xendit_donation_check_requirements() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<p>Plugin <strong>WP Xendit Donation</strong> membutuhkan PHP versi 7.4 atau lebih tinggi. Anda menggunakan PHP versi ' . PHP_VERSION . '.</p>',
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'wp_xendit_donation_check_requirements');