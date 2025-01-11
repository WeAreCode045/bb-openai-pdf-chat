<?php
$group_id = bp_get_current_group_id();
?>
<div class="chat-container">
    <!-- Left Section -->
    <div class="documents-section">
        <!-- Main Documents View -->
        <div id="documents-view">
            <div class="documents-header">
                <h3>Documents</h3>
                <button type="button" id="upload-toggle" class="icon-button">
                    <span class="dashicons dashicons-upload"></span>
                </button>
            </div>

            <div id="upload-form" class="upload-form" style="display: none;">
                <input type="file" id="document-file" name="document" accept=".pdf">
                <div id="upload-status" class="upload-status"></div>
            </div>

            <div class="documents-controls">
                <label>
                    <input type="checkbox" id="select-all-documents"> Select All
                </label>
            </div>

            <input type="hidden" id="group-id" value="<?php echo esc_attr($group_id); ?>">

            <?php if ($documents): ?>
                <ul class="documents-list">
                    <?php foreach ($documents as $document): 
                        $status = (new BP_OpenAI_PDF_Chat_File_Admin())->get_analysis_status($document->id);
                    ?>
                        <li class="document-item">
                            <label class="document-label">
                                <input type="checkbox" class="document-select" value="<?php echo esc_attr($document->id); ?>">
                                <span class="document-status status-<?php echo esc_attr($status); ?>"></span>
                                <span class="document-name"><?php echo esc_html($document->title); ?></span>
                            </label>
                            <div class="document-actions">
                                <button type="button" class="view-document icon-button" data-document-id="<?php echo esc_attr($document->id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="delete-document icon-button" data-document-id="<?php echo esc_attr($document->id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-documents">No documents uploaded yet.</div>
            <?php endif; ?>
        </div>

        <!-- PDF Preview View -->
        <div id="preview-view" style="display: none;">
            <div class="preview-header">
                <button type="button" id="close-preview" class="back-button">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Back to Documents
                </button>
                <h4 id="preview-title"></h4>
            </div>
            <div class="preview-controls">
                <div class="page-nav">
                    <button type="button" id="prev-page" class="page-button" disabled>
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        Previous
                    </button>
                    <span class="page-info">Page <span id="current-page">1</span> of <span id="total-pages">1</span></span>
                    <button type="button" id="next-page" class="page-button" disabled>
                        Next
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
            <div id="pdf-viewer"></div>
        </div>
    </div>

    <!-- Right Section -->
    <div class="chat-section">
        <div class="chat-header">
            <button type="button" id="load-history" class="secondary-button">
                <span class="dashicons dashicons-backup"></span> History
            </button>
            <button type="button" id="load-questions" class="secondary-button">
                <span class="dashicons dashicons-editor-help"></span> Questions
            </button>
        </div>

        <div id="chat-messages" class="chat-messages"></div>

        <div class="chat-input-container">
            <textarea class="chat-message-input" placeholder="Ask a question about the selected documents..." disabled></textarea>
            <button class="send-message" disabled>Send</button>
        </div>
    </div>
</div>