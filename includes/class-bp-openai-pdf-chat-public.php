<?php
/**
 * The public-facing functionality of the plugin
 */
class BB_OpenAI_PDF_Chat_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        if (!bp_is_group()) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(dirname(__FILE__)) . 'css/style.css',
            array(),
            $this->version
        );
    }

    public function enqueue_scripts() {
        if (!bp_is_group()) {
            return;
        }

        wp_enqueue_script(
            'pdf-js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js',
            array(),
            '3.4.120',
            true
        );

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(dirname(__FILE__)) . 'js/chat.js',
            array('jquery', 'pdf-js'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name, 'bbOpenAIPDFChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bb_openai_pdf_chat')
        ));
    }

    public function setup_group_nav() {
        if (!bp_is_group()) {
            return;
        }

        bp_core_new_subnav_item(array(
            'name' => 'Document Chat',
            'slug' => 'document-chat',
            'parent_url' => bp_get_group_permalink(groups_get_current_group()),
            'parent_slug' => bp_get_current_group_slug(),
            'screen_function' => array($this, 'display_chat_page'),
            'position' => 35,
            'user_has_access' => true
        ));
    }

    public function display_chat_page() {
        add_action('bb_template_content', array($this, 'get_chat_template'));
        bp_core_load_template('buddypress/members/single/plugins');
    }

    public function get_chat_template() {
        global $wpdb;
        $group_id = bp_get_current_group_id();
        
        // Get documents for current group
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bb_group_documents 
            WHERE group_id = %d 
            ORDER BY uploaded_at DESC",
            $group_id
        ));

        include_once plugin_dir_path(dirname(__FILE__)) . 'public/chat-template.php';
    }
}