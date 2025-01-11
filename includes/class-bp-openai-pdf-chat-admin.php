<?php
/**
 * The admin-specific functionality of the plugin
 */
class BB_OpenAI_PDF_Chat_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(dirname(__FILE__)) . 'css/admin.css',
            array(),
            $this->version
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(dirname(__FILE__)) . 'js/admin.js',
            array('jquery'),
            $this->version,
            true
        );
    }

    public function add_admin_menu() {
        add_options_page(
            'BuddyBoss OpenAI PDF Chat Settings',
            'BB OpenAI PDF Chat',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_settings_page')
        );
    }

    public function register_settings() {
        register_setting($this->plugin_name, 'bb_openai_pdf_chat_api_key');
        register_setting($this->plugin_name, 'bb_openai_pdf_chat_model', array(
            'default' => 'gpt-4o-mini'
        ));
        register_setting($this->plugin_name, 'bb_openai_pdf_chat_max_tokens', array(
            'default' => 500
        ));
    }

    public function display_settings_page() {
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings-page.php';
    }
}