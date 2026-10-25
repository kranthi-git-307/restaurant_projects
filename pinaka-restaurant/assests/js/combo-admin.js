/**
 * combo-admin.js
 * Handles the Select2 AJAX search for products in the Combo Bundle product data tab.
 */
jQuery(function($) {
    // Ensure the DOM is ready and jQuery is available

    // Function to show a custom alert/modal
    function showCustomAlert(message) {
        // Create a simple modal element
        var $modal = $('<div class="wc-combo-bundle-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">' +
            '<div class="wc-combo-bundle-modal-content" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center; max-width: 400px; width: 90%;">' +
            '<p style="margin-bottom: 20px; font-size: 1.1em;">' + message + '</p>' +
            '<button class="wc-combo-bundle-modal-close button button-primary">OK</button>' +
            '</div>' +
            '</div>');

        $('body').append($modal);

        // Close modal on button click
        $modal.find('.wc-combo-bundle-modal-close').on('click', function() {
            $modal.remove();
        });

        // Close modal on outside click
        $modal.on('click', function(e) {
            if ($(e.target).hasClass('wc-combo-bundle-modal')) {
                $modal.remove();
            }
        });
    }

    // Function to initialize Select2 for a given element
    function initializeSelect2(element) {
        element.select2({
            // Allow clearing the selection
            allowClear: true,
            // Placeholder text from localized script
            placeholder: combo_ajax.placeholder,
            // Minimum input characters before search
            minimumInputLength: 1, // Changed to 1 as per your code
            // AJAX configuration
            ajax: {
                url: combo_ajax.ajax_url, // WordPress AJAX URL
                dataType: 'json',         // Expect JSON response
                delay: 250,               // Delay before sending request
                data: function(params) {
                    // Data to send with the AJAX request
                    return {
                        term: params.term, // Search term
                        action: 'combo_bundle_search_products', // AJAX action hook
                        nonce: combo_ajax.nonce // Security nonce
                    };
                },
                processResults: function(data) {
                    // Process the results from the AJAX response
                    // The PHP should return data in the format: { "results": [{ "id": 1, "text": "Product Name" }] }
                    if (data && data.results) {
                        return {
                            results: data.results // Return the 'results' array
                        };
                    }
                    return { results: [] }; // Return empty array if no results or malformed data
                },
                cache: true // Cache results
            },
            width: '300px', // Set width as per your code
        });
    }

    // Initialize Select2 for existing product select fields on page load
    $('.combo-product-select').each(function() {
        var $this = $(this);
        initializeSelect2($this);

        // If there's an initial value (for existing bundle items), set it
        var initialId = $this.data('initial-id');
        var initialText = $this.data('initial-text');
        if (initialId && initialText) {
            var option = new Option(initialText, initialId, true, true);
            $this.append(option).trigger('change');
        }
    });

    // Add new bundle item row
    $('#add-bundle-item').on('click', function() {
        var newRow = '<tr>' +
            '<td>' +
            '<select class="combo-product-select" name="combo_bundle_items[id][]" style="width:300px;" required></select>' +
            '</td>' +
            '<td><div style="margin-top:5px; font-size:0.9em; color:#555;"></div></td>' + // Placeholder for price
            '<td><input type="number" name="combo_bundle_items[qty][]" value="1" min="1" required style="width:80px;"></td>' +
            '<td><button type="button" class="remove-item button">Remove</button></td>' +
            '</tr>';
        var $newRow = $(newRow).appendTo('#bundle-items');

        // Initialize Select2 for the newly added select element
        initializeSelect2($newRow.find('.combo-product-select'));

        // Update required status after adding a row
        setTimeout(toggleRequired, 100);
    });

    // Remove bundle item row
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        // Update required status after removing a row
        setTimeout(toggleRequired, 100);
    });

    // Prevent duplicates
    $(document).on('change', '.combo-product-select', function() {
        let currentSelectedId = $(this).val();
        let $currentSelect = $(this);
        let duplicate = false;

        if (currentSelectedId) { // Only check if something is actually selected
            $('.combo-product-select').not($currentSelect).each(function() {
                if ($(this).val() === currentSelectedId) {
                    duplicate = true;
                    return false; // Break the loop
                }
            });
        }

        if (duplicate) {
            showCustomAlert('This product is already selected. Please choose a different one.');
            // Clear the selection of the current dropdown
            $currentSelect.val(null).trigger('change');
        }
    });

    // Toggle required attribute for custom price input
    // function toggleRequired() {
    //     var rows = $('#bundle-items tr').length;
    //     alert(rows);
    //     var $input = $('#combo_bundle_custom_price');
    //     if (rows > 0) {
    //         $input.prop('required', true);
    //     } else {
    //         $input.prop('required', false);
    //     }
    // }
    function toggleRequired() 
    {
        var rows = $('#bundle-items tr').length;
        var productType = $('#product-type').val();
        var $input = $('#combo_bundle_custom_price');

        if (rows > 0 && productType === 'combo_bundle') {
            $input.prop('required', true);
        } else {
            $input.prop('required', false);
        }
    }

    // Initial call on page load
    toggleRequired();

    // The event listeners for add/remove already call toggleRequired with a timeout.
    // No need for separate click handlers here.
    $(document).on('select2:select', '.combo-product-select', function(e) {
        var data = e.params.data;
        var priceText = data.price ? data.price : '';
        $(this).closest('tr').find('td div').text(priceText);
    });
});
