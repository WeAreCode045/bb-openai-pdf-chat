jQuery(document).ready(function($) {
    // Add new website row
    $('.add-website').on('click', function() {
        var newRow = $('<div class="website-row">' +
            '<input type="url" name="bb_openai_pdf_chat_websites[]" value="" class="regular-text website-url" placeholder="https://example.com">' +
            '<button type="button" class="button remove-website">Remove</button>' +
            '</div>');
        $('#website-repeater').append(newRow);
    });

    // Remove website row
    $(document).on('click', '.remove-website', function() {
        var $row = $(this).closest('.website-row');
        if ($('.website-row').length > 1) {
            $row.remove();
        } else {
            $row.find('.website-url').val('');
        }
    });
});