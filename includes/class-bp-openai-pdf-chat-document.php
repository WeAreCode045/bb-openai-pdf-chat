<?php
class BP_OpenAI_PDF_Chat_Document {
    private $table_name;
    private $base_upload_dir;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bp_group_documents';
        
        $wp_upload_dir = wp_upload_dir();
        $this->base_upload_dir = $wp_upload_dir['basedir'] . '/group_documents';
    }

    public function get_document_text($document_id) {
        global $wpdb;
        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT text_path FROM {$this->table_name} WHERE id = %d",
            $document_id
        ));
        
        if (!$document || !$document->text_path || !file_exists($document->text_path)) {
            return '';
        }
        
        return file_get_contents($document->text_path);
    }

    public function get_document($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    public function get_preview() {
        check_ajax_referer('bp_openai_pdf_chat', 'nonce');

        if (!isset($_POST['document_id'])) {
            wp_send_json_error('Missing document ID');
        }

        $document_id = intval($_POST['document_id']);
        $document = $this->get_document($document_id);

        if (!$document) {
            wp_send_json_error('Document not found');
        }

        // Get file URL
        $upload_dir = wp_upload_dir();
        $file_url = str_replace(
            $upload_dir['basedir'],
            $upload_dir['baseurl'],
            $document->file_path
        );

        wp_send_json_success(array(
            'file_url' => $file_url,
            'title' => $document->title,
            'status' => $document->analysis_status,
            'analyzed_at' => $document->analyzed_at
        ));
    }

    public function get_group_folder($group_id) {
        return 'group_' . $group_id;
    }

    public function get_base_upload_dir() {
        return $this->base_upload_dir;
    }
}