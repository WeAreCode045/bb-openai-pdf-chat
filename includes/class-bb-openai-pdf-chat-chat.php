<?php
class BB_OpenAI_PDF_Chat_Chat {
    private $api;
    private $file_admin;

    public function __construct() {
        $this->api = new BB_OpenAI_PDF_Chat_API();
        $this->file_admin = new BB_OpenAI_PDF_Chat_File_Admin();
    }

    public function chat() {
        check_ajax_referer('bb_openai_pdf_chat', 'nonce');

        $document_ids = isset($_POST['document_ids']) ? array_map('intval', $_POST['document_ids']) : array();
        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';

        if (empty($document_ids) || !$question) {
            wp_send_json_error('Missing required parameters');
        }

        try {
            $context = $this->get_combined_context($document_ids);
            $response = $this->api->query($question, $context);
            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function get_combined_context($document_ids) {
        $combined_text = '';
        foreach ($document_ids as $document_id) {
            $text = $this->file_admin->get_document_text($document_id);
            if ($text) {
                $document = $this->file_admin->get_document($document_id);
                $combined_text .= "Content from document '{$document->title}':\n\n";
                $combined_text .= $text . "\n\n---\n\n";
            }
        }

        if (empty($combined_text)) {
            throw new Exception('No analyzed documents found');
        }

        return $combined_text;
    }

    public function save_history() {
        check_ajax_referer('bb_openai_pdf_chat', 'nonce');

        if (!isset($_POST['group_id']) || !isset($_POST['chat_data'])) {
            wp_send_json_error('Missing required parameters');
        }

        $group_id = intval($_POST['group_id']);
        $chat_data = json_decode(stripslashes($_POST['chat_data']), true);

        if (!is_array($chat_data)) {
            wp_send_json_error('Invalid chat data format');
        }

        // Get group folder path
        $group_folder = $this->file_admin->get_group_folder($group_id);
        $history_dir = $this->file_admin->get_base_upload_dir() . '/' . $group_folder . '/history';

        // Create history directory if it doesn't exist
        if (!file_exists($history_dir)) {
            wp_mkdir_p($history_dir);
            file_put_contents($history_dir . '/.htaccess', 'deny from all');
        }

        // Generate unique filename for chat history
        $filename = date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
        $file_path = $history_dir . '/' . $filename;

        // Save chat history
        if (file_put_contents($file_path, json_encode($chat_data, JSON_PRETTY_PRINT))) {
            wp_send_json_success('Chat history saved');
        } else {
            wp_send_json_error('Failed to save chat history');
        }
    }

    public function get_history() {
        check_ajax_referer('bb_openai_pdf_chat', 'nonce');

        if (!isset($_POST['group_id'])) {
            wp_send_json_error('Missing group ID');
        }

        $group_id = intval($_POST['group_id']);
        $group_folder = $this->file_admin->get_group_folder($group_id);
        $history_dir = $this->file_admin->get_base_upload_dir() . '/' . $group_folder . '/history';

        if (!file_exists($history_dir)) {
            wp_send_json_success(array());
            return;
        }

        $files = glob($history_dir . '/*.json');
        $history = array();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content) {
                $history[] = array(
                    'id' => basename($file, '.json'),
                    'date' => date('Y-m-d H:i:s', filectime($file)),
                    'data' => json_decode($content, true)
                );
            }
        }

        // Sort by date descending
        usort($history, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        wp_send_json_success($history);
    }
}