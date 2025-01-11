<?php
class BB_OpenAI_PDF_Chat_File_Admin {
    private $table_name;
    private $base_upload_dir;
    private $max_file_size = 10485760; // 10MB limit
    private $api;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bb_group_documents';
        $this->api = new BB_OpenAI_PDF_Chat_API();
        
        $wp_upload_dir = wp_upload_dir();
        $this->base_upload_dir = $wp_upload_dir['basedir'] . '/group_documents';
    }

    public function upload() {
        check_ajax_referer('bb_openai_pdf_chat', 'nonce');

        if (!isset($_FILES['document']) || !isset($_POST['group_id'])) {
            wp_send_json_error('Missing required parameters');
        }

        $file = $_FILES['document'];
        $group_id = intval($_POST['group_id']);

        // Validate file type
        $file_type = wp_check_filetype($file['name'], array('pdf' => 'application/pdf'));
        if (!$file_type['type']) {
            wp_send_json_error('Invalid file type. Only PDF files are allowed.');
        }

        // Check file size
        if ($file['size'] > $this->max_file_size) {
            wp_send_json_error('File is too large. Maximum size is 10MB.');
        }

        // Create group folder structure
        $group_folder = $this->get_group_folder($group_id);
        $pdf_dir = $this->base_upload_dir . '/' . $group_folder . '/pdfs';
        $text_dir = $this->base_upload_dir . '/' . $group_folder . '/text';

        wp_mkdir_p($pdf_dir);
        wp_mkdir_p($text_dir);

        // Generate unique filenames
        $pdf_filename = wp_unique_filename($pdf_dir, $file['name']);
        $text_filename = pathinfo($pdf_filename, PATHINFO_FILENAME) . '.txt';
        
        $pdf_path = $pdf_dir . '/' . $pdf_filename;
        $text_path = $text_dir . '/' . $text_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $pdf_path)) {
            wp_send_json_error('Error uploading file');
        }

        // Insert into database
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'group_id' => $group_id,
                'title' => sanitize_text_field($file['name']),
                'file_name' => $pdf_filename,
                'file_path' => $pdf_path,
                'text_path' => $text_path,
                'uploaded_by' => get_current_user_id(),
                'uploaded_at' => current_time('mysql'),
                'analysis_status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if (!$result) {
            unlink($pdf_path);
            wp_send_json_error('Error saving document information');
        }

        // Process document immediately after upload
        $document_id = $wpdb->insert_id;
        try {
            $this->analyze_document($document_id);
            wp_send_json_success(array(
                'message' => 'Document uploaded and processed successfully',
                'document_id' => $document_id
            ));
        } catch (Exception $e) {
            wp_send_json_error('Document upload failed: ' . $e->getMessage());
        }
    }

    public function analyze_document($document_id) {
        $document = $this->get_document($document_id);
        if (!$document) {
            throw new Exception('Document not found');
        }

        try {
            // First try with pdftotext if available
            $text = $this->extract_text_with_pdftotext($document->file_path);
            
            // If pdftotext fails, fallback to PDF Parser
            if (empty($text)) {
                $text = $this->extract_text_with_parser($document->file_path);
            }

            if (empty($text)) {
                throw new Exception('No text content could be extracted from the PDF');
            }

            // Clean and normalize the text
            $text = $this->clean_text($text);

            // Store the complete text without analysis
            if (file_put_contents($document->text_path, $text) === false) {
                throw new Exception('Failed to save text to file');
            }

            // Get page count using PDF Parser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($document->file_path);
            $page_count = count($pdf->getPages());

            // Update database
            global $wpdb;
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'analyzed_at' => current_time('mysql'),
                    'analysis_status' => 'completed',
                    'page_count' => $page_count,
                    'file_size' => filesize($document->file_path),
                    'metadata' => json_encode(array(
                        'text_length' => strlen($text)
                    ))
                ),
                array('id' => $document_id),
                array('%s', '%s', '%d', '%d', '%s'),
                array('%d')
            );

            if ($result === false) {
                throw new Exception('Failed to update document status in database');
            }

            return true;
        } catch (Exception $e) {
            // Update status to error
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('analysis_status' => 'error'),
                array('id' => $document_id),
                array('%s'),
                array('%d')
            );
            throw new Exception('Error processing document: ' . $e->getMessage());
        }
    }

    private function extract_text_with_pdftotext($file_path) {
        if (!function_exists('shell_exec')) {
            return '';
        }

        $output = shell_exec("pdftotext -layout '{$file_path}' -");
        return $output ?: '';
    }

    private function extract_text_with_parser($file_path) {
        require_once BB_OPENAI_PDF_CHAT_PLUGIN_DIR . 'vendor/autoload.php';
        $parser = new \Smalot\PdfParser\Parser();
        
        try {
            $pdf = $parser->parseFile($file_path);
            return $pdf->getText();
        } catch (Exception $e) {
            error_log('PDF Parser error: ' . $e->getMessage());
            return '';
        }
    }

    private function clean_text($text) {
        // Remove multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove multiple newlines while preserving paragraph structure
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        
        // Remove form feeds
        $text = preg_replace('/\f/', "\n", $text);
        
        // Remove non-printable characters while preserving newlines
        $text = preg_replace('/[^\x20-\x7E\n]/', '', $text);
        
        // Trim whitespace
        $text = trim($text);
        
        return $text;
    }

    public function get_document_text($document_id) {
        $document = $this->get_document($document_id);
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

    public function get_group_folder($group_id) {
        return 'group_' . $group_id;
    }

    public function get_base_upload_dir() {
        return $this->base_upload_dir;
    }

    public function get_analysis_status($document_id) {
        global $wpdb;
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT analysis_status FROM {$this->table_name} WHERE id = %d",
            $document_id
        ));
        return $status ?: 'error';
    }
}