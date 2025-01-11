<?php
class BB_OpenAI_PDF_Chat_API {
    private $api_key;
    private $timeout = 60; // Increased timeout for longer documents

    public function __construct() {
        $this->api_key = get_option('bb_openai_pdf_chat_api_key');
    }

    public function analyze($text) {
        // Simply return the raw text without OpenAI analysis
        return $text;
    }

    public function query($question, $context) {
        if (!$this->api_key) {
            throw new Exception('OpenAI API key not configured');
        }

        $system_prompt = "You are a helpful assistant that answers questions based PDF Documents";
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => get_option('bb_openai_pdf_chat_model', 'gpt-4o-mini'),
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_prompt
                    ),
                    array(
                        'role' => 'user',
                        'content' => "Context:\n\n{$context}\n\nQuestion: {$question}"
                    )
                ),
                'max_tokens' => intval(get_option('bb_openai_pdf_chat_max_tokens', 1000)),
                'temperature' => 0.7
            ))
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            throw new Exception($body['error']['message']);
        }

        return $body['choices'][0]['message']['content'];
    }
}