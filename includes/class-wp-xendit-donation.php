<?php
/**
 * Kelas utama plugin yang mendefinisikan kode internasionalisasi, hook admin, dan hook publik.
 */
class WP_Xendit_Donation {

    /**
     * Loader yang menjalankan semua hooks dengan plugin.
     */
    protected $loader;

    /**
     * Nama unik identifier plugin.
     */
    protected $plugin_name;

    /**
     * Versi saat ini dari plugin.
     */
    protected $version;

    /**
     * Mendefinisikan fungsi inti plugin dan memuat dependensi.
     */
    public function __construct() {
        if (defined('WP_XENDIT_DONATION_VERSION')) {
            $this->version = WP_XENDIT_DONATION_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        
        $this->plugin_name = 'wp-xendit-donation';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load dependensi yang diperlukan untuk plugin ini.
     */
    private function load_dependencies() {
        // Cek apakah file ada sebelum memuatnya
        $required_files = array(
            'includes/class-loader.php',
            'includes/class-i18n.php',
            'includes/class-admin.php',
            'includes/class-xendit-api.php',
            'includes/class-donation-form.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = WP_XENDIT_DONATION_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                // Log error jika file tidak ditemukan
                error_log("WP Xendit Donation: File tidak ditemukan - " . $file_path);
            }
        }
        
        // Buat loader instance
        if (class_exists('WP_Xendit_Donation_Loader')) {
            $this->loader = new WP_Xendit_Donation_Loader();
        }
    }

    /**
     * Mendefinisikan locale untuk internasionalisasi.
     */
    private function set_locale() {
        if (class_exists('WP_Xendit_Donation_i18n') && $this->loader) {
            $plugin_i18n = new WP_Xendit_Donation_i18n();
            $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        }
    }

    /**
     * Mendaftarkan semua hooks terkait dengan area admin/dashboard WordPress.
     */
    private function define_admin_hooks() {
        if (class_exists('WP_Xendit_Donation_Admin') && $this->loader) {
            $plugin_admin = new WP_Xendit_Donation_Admin($this->get_plugin_name(), $this->get_version());
            
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
            $this->loader->add_action('admin_menu', $plugin_admin, 'add_options_page');
            $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        }
    }

    /**
     * Mendaftarkan semua hooks terkait dengan public-facing area.
     */
    private function define_public_hooks() {
        if (class_exists('WP_Xendit_Donation_Form') && $this->loader) {
            $donation_form = new WP_Xendit_Donation_Form($this->get_plugin_name(), $this->get_version());
            
            $this->loader->add_action('wp_enqueue_scripts', $donation_form, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $donation_form, 'enqueue_scripts');
            $this->loader->add_shortcode('xendit_donation_form', $donation_form, 'display_donation_form');
            
            // Endpoint untuk callback Xendit - gunakan init hook yang lebih aman
            $this->loader->add_action('init', $donation_form, 'register_endpoints');
        }
    }

    /**
     * Menjalankan loader untuk mengeksekusi semua hooks dengan WordPress.
     */
    public function run() {
        if ($this->loader) {
            $this->loader->run();
        }
    }

    /**
     * Nama dari plugin digunakan untuk identifikasi plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Referensi ke class loader yang mengelola hooks plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Mendapatkan versi plugin.
     */
    public function get_version() {
        return $this->version;
    }
}