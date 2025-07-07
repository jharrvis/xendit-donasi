<?php
/**
 * Kelas yang dijalankan saat plugin dinonaktifkan
 */
class WP_Xendit_Donation_Deactivator {
    
    /**
     * Dijalankan saat plugin dinonaktifkan
     */
    public static function deactivate() {
        // Tidak perlu menghapus tabel database saat deaktivasi
        // Agar data donasi tetap tersimpan
    }
}