jQuery(function($) {
    let duplicateExists = false;
    let debounceTimer;

    function showWarning() {
        if ($('#duplicate-warning').length === 0) {
            $('#titlewrap').after('<div id="duplicate-warning" class="notice notice-error"><p>A Table with this name already exists in this Zone for this Restaurant. Cannot publish.</p></div>');
        }
    }

    function removeWarning() {
        $('#duplicate-warning').remove();
    }

    function checkDuplicate(async = true) {
        let title = $('#title').val().trim();
        let zone_id = $('#zone_id').val();
        let restaurant_id = $('#restaurant_id').val();
        let postId = tableCheckData.exclude_id || 0;

        if (!title || !zone_id || !restaurant_id) {
            removeWarning();
            duplicateExists = false;
            return;
        }

        $.ajax({
            url: tableCheckData.rest_url,
            method: 'GET',
            data: {
                name: title,
                zone_id: zone_id,
                restaurant_id: restaurant_id,
                post_id: postId
            },
            async: async,
            success: function(response) {
                if (!response.same_as_existing && response.found_in_others && response.found_in_others.name) {
                    showWarning();
                    duplicateExists = true;
                } else {
                    removeWarning();
                    duplicateExists = false;
                }
            }
        });
    }

    // Debounce typing & changes
    $('#title, #zone_id, #restaurant_id').on('input change', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => checkDuplicate(true), 300);
    });

    $('#post').on('submit', function(e) {
        checkDuplicate(false); // synchronous check
        if (duplicateExists) {
            e.preventDefault();
            alert("Cannot publish. A Table with this name already exists in this Zone for this Restaurant.");
            showWarning();
            return false;
        }
    });

    // Run once on load
    checkDuplicate(true);
});
