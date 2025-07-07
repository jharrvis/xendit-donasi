<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Kelas untuk menampilkan daftar donasi dalam bentuk tabel di admin
 */
class WP_Xendit_Donation_List_Table extends WP_List_Table {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'donation',
            'plural'   => 'donations',
            'ajax'     => false
        ));
    }
    
    /**
     * Mendapatkan kolom tabel
     */
    public function get_columns() {
        $columns = array(
            'cb'          => '<input type="checkbox" />',
            'donor_name'  => 'Nama Donatur',
            'donor_email' => 'Email',
            'amount'      => 'Jumlah',
            'status'      => 'Status',
            'message'     => 'Pesan',
            'created_at'  => 'Tanggal'
        );
        
        return $columns;
    }
    
    /**
     * Kolom yang dapat diurutkan
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'donor_name'  => array('donor_name', false),
            'donor_email' => array('donor_email', false),
            'amount'      => array('amount', false),
            'status'      => array('status', false),
            'created_at'  => array('created_at', true)
        );
        
        return $sortable_columns;
    }
    
    /**
     * Mendapatkan data donasi dari database
     */
    public function get_donations($per_page = 20, $page_number = 1, $orderby = 'created_at', $order = 'DESC') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xendit_donations';
        
        $sql = "SELECT * FROM $table_name";
        
        // Search filter
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $sql .= $wpdb->prepare(
                " WHERE donor_name LIKE %s OR donor_email LIKE %s OR external_id LIKE %s",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        // Status filter
        if (!empty($_REQUEST['status'])) {
            $status = sanitize_text_field($_REQUEST['status']);
            $sql .= !empty($_REQUEST['s']) ? " AND" : " WHERE";
            $sql .= $wpdb->prepare(" status = %s", $status);
        }
        
        // Orderby and pagination
        $sql .= " ORDER BY $orderby $order LIMIT %d OFFSET %d";
        
        $sql = $wpdb->prepare($sql, $per_page, ($page_number - 1) * $per_page);
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results;
    }
    
    /**
     * Mendapatkan jumlah total donasi
     */
    public function record_count() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xendit_donations';
        
        $sql = "SELECT COUNT(*) FROM $table_name";
        
        // Search filter
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $sql .= $wpdb->prepare(
                " WHERE donor_name LIKE %s OR donor_email LIKE %s OR external_id LIKE %s",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        // Status filter
        if (!empty($_REQUEST['status'])) {
            $status = sanitize_text_field($_REQUEST['status']);
            $sql .= !empty($_REQUEST['s']) ? " AND" : " WHERE";
            $sql .= $wpdb->prepare(" status = %s", $status);
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Tampilan jika tidak ada donasi
     */
    public function no_items() {
        echo 'Belum ada donasi.';
    }
    
    /**
     * Render kolom untuk checkbox
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="donation_id[]" value="%s" />',
            $item['id']
        );
    }
    
    /**
     * Render kolom nama donatur
     */
    public function column_donor_name($item) {
        $actions = array(
            'view' => sprintf(
                '<a href="%s">Detail</a>',
                admin_url('admin.php?page=wp-xendit-donation-donations&action=view&id=' . $item['id'])
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'Apakah Anda yakin ingin menghapus donasi ini?\')">Hapus</a>',
                admin_url('admin.php?page=wp-xendit-donation-donations&action=delete&id=' . $item['id'] . '&_wpnonce=' . wp_create_nonce('delete_donation'))
            )
        );
        
        return sprintf(
            '%1$s %2$s',
            $item['donor_name'],
            $this->row_actions($actions)
        );
    }
    
    /**
     * Render kolom jumlah donasi
     */
    public function column_amount($item) {
        return 'Rp ' . number_format($item['amount'], 0, ',', '.');
    }
    
    /**
     * Render kolom status
     */
    public function column_status($item) {
        $status_labels = array(
            'pending' => 'Pending',
            'PAID' => 'Sukses',
            'EXPIRED' => 'Expired',
            'FAILED' => 'Gagal'
        );
        
        $status_class = strtolower($item['status']);
        
        return sprintf(
            '<span class="status-%s">%s</span>',
            $status_class,
            isset($status_labels[$item['status']]) ? $status_labels[$item['status']] : $item['status']
        );
    }
    
    /**
     * Render kolom tanggal
     */
    public function column_created_at($item) {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['created_at']));
    }
    
    /**
     * Render kolom default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'donor_email':
            case 'message':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }
    
    /**
     * Bulk actions
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => 'Hapus'
        );
        
        return $actions;
    }
    
    /**
     * Menangani bulk actions
     */
    public function process_bulk_action() {
        // Proses bulk delete
        if ('bulk-delete' === $this->current_action()) {
            $nonce = esc_attr($_REQUEST['_wpnonce']);
            
            if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
                die('Nonce verification failed');
            }
            
            $donation_ids = isset($_REQUEST['donation_id']) ? $_REQUEST['donation_id'] : array();
            
            if (!empty($donation_ids) && is_array($donation_ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'xendit_donations';
                
                foreach ($donation_ids as $id) {
                    $wpdb->delete(
                        $table_name,
                        array('id' => $id),
                        array('%d')
                    );
                }
                
                wp_redirect(admin_url('admin.php?page=wp-xendit-donation-donations&message=deleted'));
                exit;
            }
        }
        
        // Proses single delete
        if ('delete' === $this->current_action()) {
            $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
            $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;
            
            if (!wp_verify_nonce($nonce, 'delete_donation')) {
                die('Nonce verification failed');
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'xendit_donations';
            
            $wpdb->delete(
                $table_name,
                array('id' => $id),
                array('%d')
            );
            
            wp_redirect(admin_url('admin.php?page=wp-xendit-donation-donations&message=deleted'));
            exit;
        }
    }
    
    /**
     * Filter status
     */
    public function extra_tablenav($which) {
        if ('top' !== $which) {
            return;
        }
        
        $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        ?>
        <div class="alignleft actions">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                <option value="PAID" <?php selected($status, 'PAID'); ?>>Sukses</option>
                <option value="EXPIRED" <?php selected($status, 'EXPIRED'); ?>>Expired</option>
                <option value="FAILED" <?php selected($status, 'FAILED'); ?>>Gagal</option>
            </select>
            <?php submit_button('Filter', '', 'filter_action', false); ?>
        </div>
        <?php
    }
    
    /**
     * Prepare items for display
     */
    public function prepare_items() {
        $this->process_bulk_action();
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $orderby = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'created_at';
        $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'DESC';
        
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );
        
        $this->items = $this->get_donations($per_page, $current_page, $orderby, $order);
        
        $total_items = $this->record_count();
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}