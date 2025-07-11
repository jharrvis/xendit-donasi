<?php
/**
 * Kelas untuk mendaftarkan semua hooks dengan WordPress
 */
class WP_Xendit_Donation_Loader {
    
    /**
     * Array dari actions
     */
    protected $actions;
    
    /**
     * Array dari filters
     */
    protected $filters;
    
    /**
     * Array dari shortcodes
     */
    protected $shortcodes;
    
    /**
     * Inisialisasi kelas
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }
    
    /**
     * Menambahkan action hook
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Menambahkan filter hook
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Menambahkan shortcode
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes = $this->add($this->shortcodes, $tag, $component, $callback);
    }
    
    /**
     * Utility function untuk menambahkan hooks
     */
    private function add($hooks, $hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    /**
     * Mendaftarkan hooks dengan WordPress
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
        
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
        
        foreach ($this->shortcodes as $hook) {
            add_shortcode($hook['hook'], array($hook['component'], $hook['callback']));
        }
    }
}