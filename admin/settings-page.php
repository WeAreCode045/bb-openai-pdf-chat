<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bp_openai_pdf_chat_api_key">OpenAI API Key</label>
                </th>
                <td>
                    <input type="password" 
                           id="bp_openai_pdf_chat_api_key" 
                           name="bp_openai_pdf_chat_api_key" 
                           value="<?php echo esc_attr(get_option('bp_openai_pdf_chat_api_key')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bp_openai_pdf_chat_model">OpenAI Model</label>
                </th>
                <td>
                    <select id="bp_openai_pdf_chat_model" 
                            name="bp_openai_pdf_chat_model">
                        <option value="gpt-3.5-turbo" <?php selected(get_option('bp_openai_pdf_chat_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>
                            GPT-3.5 Turbo
                        </option>
                        <option value="gpt-4" <?php selected(get_option('bp_openai_pdf_chat_model'), 'gpt-4'); ?>>
                            GPT-4
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bp_openai_pdf_chat_max_tokens">Max Response Tokens</label>
                </th>
                <td>
                    <input type="number" 
                           id="bp_openai_pdf_chat_max_tokens" 
                           name="bp_openai_pdf_chat_max_tokens" 
                           value="<?php echo esc_attr(get_option('bp_openai_pdf_chat_max_tokens', '500')); ?>" 
                           min="1" 
                           max="4000" 
                           class="small-text">
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>