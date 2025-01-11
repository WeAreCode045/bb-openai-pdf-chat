<?php
/**
 * Plugin Name: BuddyPress OpenAI PDF Chat
 * Description: A plugin that allows users to chat with PDF documents using OpenAI in BuddyPress groups
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BP_OPENAI_PDF_CHAT_VERSION', '1.0.0');
define('BP_OPENAI_PDF_CHAT_DB_VERSION', '1.0.0');
define('BP_OPENAI_PDF_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load custom autoloader
require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/autoload.php';

// Load the main plugin class
require_once BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'includes/class-bp-openai-pdf-chat.php';

function bp_openai_pdf_chat_activate() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bp_group_documents';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        group_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL,
        file_name varchar(255) NOT NULL,
        file_path varchar(255) NOT NULL,
        text_path varchar(255) DEFAULT NULL,
        uploaded_by bigint(20) NOT NULL,
        uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
        analyzed_at datetime DEFAULT NULL,
        analysis_status varchar(20) DEFAULT 'pending',
        page_count int DEFAULT NULL,
        file_size bigint DEFAULT NULL,
        metadata json DEFAULT NULL,
        PRIMARY KEY (id),
        KEY group_id (group_id),
        KEY uploaded_by (uploaded_by),
        KEY analysis_status (analysis_status),
        KEY uploaded_at (uploaded_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Create upload directory
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/group_documents';
    wp_mkdir_p($base_dir);

    // Add .htaccess to protect the uploads directory
    $htaccess = $base_dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        $rules = "Options -Indexes\n";
        $rules .= "<FilesMatch '\.(php|php\.|php3|php4|php5|php7|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$'>\n";
        $rules .= "Order Deny,Allow\n";
        $rules .= "Deny from all\n";
        $rules .= "</FilesMatch>\n";
        file_put_contents($htaccess, $rules);
    }

    // Store database version
    add_option('bp_openai_pdf_chat_db_version', BP_OPENAI_PDF_CHAT_DB_VERSION);
}

register_activation_hook(__FILE__, 'bp_openai_pdf_chat_activate');

function run_bp_openai_pdf_chat() {
    $plugin = BP_OpenAI_PDF_Chat::get_instance();
    $plugin->run();
}

run_bp_openai_pdf_chat();