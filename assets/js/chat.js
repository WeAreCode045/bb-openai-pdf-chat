jQuery(document).ready(function($) {
    let selectedDocuments = new Set();
    let activePreviewId = null;
    let currentPDF = null;
    let currentPage = 1;
    let totalPages = 1;
    let chatHistory = [];
    
    // Initialize PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    // Toggle upload form
    $('#upload-toggle').on('click', function() {
        $('#upload-form').slideToggle();
    });

    // Select all documents
    $('#select-all-documents').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.document-select').prop('checked', isChecked);
        if (isChecked) {
            $('.document-select').each(function() {
                selectedDocuments.add($(this).val());
            });
        } else {
            selectedDocuments.clear();
        }
        updateChatStatus();
    });

    // File upload handling
    $('#document-file').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        if (file.type !== 'application/pdf') {
            alert('Only PDF files are allowed');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload_group_document');
        formData.append('nonce', bbOpenAIPDFChat.nonce);
        formData.append('document', file);
        formData.append('group_id', $('#group-id').val());

        $('#upload-status').text('Uploading...').show();

        $.ajax({
            url: bbOpenAIPDFChat.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#upload-status').text('Upload successful!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    $('#upload-status').text('Upload failed: ' + response.data);
                }
            },
            error: function() {
                $('#upload-status').text('Upload failed');
            }
        });
    });

    // Document selection handling
    $('.document-select').on('change', function() {
        const documentId = $(this).val();
        if (this.checked) {
            selectedDocuments.add(documentId);
        } else {
            selectedDocuments.delete(documentId);
        }
        updateChatStatus();
    });

    // View document
    $('.view-document').on('click', async function() {
        const documentId = $(this).data('document-id');
        const $documentItem = $(this).closest('.document-item');
        const title = $documentItem.find('.document-name').text();

        try {
            const response = await $.ajax({
                url: bbOpenAIPDFChat.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_document_preview',
                    nonce: bbOpenAIPDFChat.nonce,
                    document_id: documentId
                }
            });

            if (response.success) {
                activePreviewId = documentId;
                $('#preview-title').text(title);
                $('#documents-view').hide();
                $('#preview-view').show();
                
                // Reset page navigation
                currentPage = 1;
                $('#current-page').text(currentPage);
                
                // Load PDF
                const loadingTask = pdfjsLib.getDocument(response.data.file_url);
                loadingTask.promise.then(function(pdf) {
                    currentPDF = pdf;
                    totalPages = pdf.numPages;
                    $('#total-pages').text(totalPages);
                    
                    // Update navigation buttons
                    updatePageNavigation();
                    
                    // Render first page
                    renderPage(currentPage);
                });
                
                // Update chat status for single document context
                selectedDocuments.clear();
                selectedDocuments.add(documentId);
                updateChatStatus();
            }
        } catch (error) {
            alert('Error loading document preview');
        }
    });

    // Page navigation
    $('#prev-page').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            renderPage(currentPage);
            updatePageNavigation();
        }
    });

    $('#next-page').on('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            renderPage(currentPage);
            updatePageNavigation();
        }
    });

    // Chat functionality
    $('.send-message').on('click', sendMessage);
    $('.chat-message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    async function sendMessage() {
        const $input = $('.chat-message-input');
        const $sendButton = $('.send-message');
        const question = $input.val().trim();
        
        if (!question || selectedDocuments.size === 0) return;
        
        // Disable input while processing
        $input.prop('disabled', true);
        $sendButton.prop('disabled', true);
        
        // Add user message to chat
        addMessageToChat('user', question);
        
        try {
            const response = await $.ajax({
                url: bbOpenAIPDFChat.ajaxurl,
                type: 'POST',
                data: {
                    action: 'chat_with_document',
                    nonce: bbOpenAIPDFChat.nonce,
                    document_ids: Array.from(selectedDocuments),
                    question: question
                }
            });
            
            if (response.success) {
                // Add AI response to chat
                addMessageToChat('assistant', response.data);
                
                // Save to chat history
                chatHistory.push({
                    role: 'user',
                    content: question
                }, {
                    role: 'assistant',
                    content: response.data
                });
                
                // Save chat history
                saveChatHistory();
            } else {
                addMessageToChat('error', 'Error: ' + response.data);
            }
        } catch (error) {
            addMessageToChat('error', 'Error processing your request');
        }
        
        // Clear and re-enable input
        $input.val('').prop('disabled', false).focus();
        $sendButton.prop('disabled', false);
    }

    function addMessageToChat(role, content) {
        const $messages = $('#chat-messages');
        const messageClass = role === 'user' ? 'user-message' : 
                           role === 'assistant' ? 'assistant-message' : 
                           'error-message';
        
        const $message = $('<div>', {
            class: `chat-message ${messageClass}`,
            html: `<div class="message-content">${content}</div>`
        });
        
        $messages.append($message);
        $messages.scrollTop($messages[0].scrollHeight);
    }

    async function saveChatHistory() {
        try {
            await $.ajax({
                url: bbOpenAIPDFChat.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_chat_history',
                    nonce: bbOpenAIPDFChat.nonce,
                    group_id: $('#group-id').val(),
                    chat_data: JSON.stringify(chatHistory)
                }
            });
        } catch (error) {
            console.error('Error saving chat history:', error);
        }
    }

    function updatePageNavigation() {
        $('#current-page').text(currentPage);
        $('#prev-page').prop('disabled', currentPage === 1);
        $('#next-page').prop('disabled', currentPage === totalPages);
    }

    async function renderPage(pageNumber) {
        if (!currentPDF) return;

        try {
            const page = await currentPDF.getPage(pageNumber);
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            
            // Calculate scale to fit the container width while maintaining aspect ratio
            const containerWidth = $('#pdf-viewer').width();
            const viewport = page.getViewport({ scale: 1.0 });
            const scale = containerWidth / viewport.width;
            const scaledViewport = page.getViewport({ scale: scale });
            
            canvas.height = scaledViewport.height;
            canvas.width = scaledViewport.width;
            
            await page.render({
                canvasContext: context,
                viewport: scaledViewport
            }).promise;
            
            $('#pdf-viewer').empty().append(canvas);
        } catch (error) {
            console.error('Error rendering page:', error);
        }
    }

    // Close preview
    $('#close-preview').on('click', function() {
        $('#preview-view').hide();
        $('#documents-view').show();
        activePreviewId = null;
        currentPDF = null;
        selectedDocuments.clear();
        updateChatStatus();
    });

    // Handle window resize
    $(window).on('resize', function() {
        if (currentPDF && currentPage) {
            renderPage(currentPage);
        }
    });

    function updateChatStatus() {
        const $chatInput = $('.chat-message-input');
        const $sendButton = $('.send-message');
        const $selectedInfo = $('#selected-documents-info');
        
        if (selectedDocuments.size > 0) {
            $chatInput.prop('disabled', false);
            $sendButton.prop('disabled', false);
            if (activePreviewId) {
                $selectedInfo.text('Ask questions about the current document');
            } else {
                $selectedInfo.text(`${selectedDocuments.size} document(s) selected`);
            }
        } else {
            $chatInput.prop('disabled', true);
            $sendButton.prop('disabled', true);
            $selectedInfo.text('Select documents to start chatting');
        }
    }
});