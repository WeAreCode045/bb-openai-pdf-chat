<?php
/**
 * The core plugin class
 */
class BP_OpenAI_PDF_Chat {
    private static $instance = null;
    private $loader;
    private $plugin_name;
    private $version;

    private function __construct() {
        $this->plugin_name = 'bp-openai-pdf-chat';
        $this->version = BP_OPENAI_PDF_CHAT_VERSION;
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-loader.php';
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-admin.php';
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-public.php';
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-document.php';
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-api.php';
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-chat.php';
        require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat-file-admin.php';

        $this->loader = new BP_OpenAI_PDF_Chat_Loader();
    }

    private function define_admin_hooks() {
        $admin = new BP_OpenAI_PDF_Chat_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
    }

    private function define_public_hooks() {
        $public = new BP_OpenAI_PDF_Chat_Public($this->get_plugin_name(), $this->get_version());
        $document = new BP_OpenAI_PDF_Chat_Document();
        $chat = new BP_OpenAI_PDF_Chat_Chat();
        $file_admin = new BP_OpenAI_PDF_Chat_File_Admin();
        
        $this->loader->add_action('bp_init', $public, 'setup_group_nav');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_process_document', $document, 'process_document');
        $this->loader->add_action('wp_ajax_get_document_preview', $document, 'get_preview');
        $this->loader->add_action('wp_ajax_upload_group_document', $file_admin, 'upload');
        $this->loader->add_action('wp_ajax_delete_group_document', $file_admin, 'delete');
        $this->loader->add_action('wp_ajax_reanalyze_document', $file_admin, 'reanalyze');
        
        $this->loader->add_action('wp_ajax_chat_with_document', $chat, 'chat');
        $this->loader->add_action('wp_ajax_save_chat_history', $chat, 'save_history');
        $this->loader->add_action('wp_ajax_get_chat_history', $chat, 'get_history');
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function run() {
        $this->loader->run();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}