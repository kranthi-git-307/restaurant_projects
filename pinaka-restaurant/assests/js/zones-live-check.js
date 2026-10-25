jQuery(function($) {
    let duplicateExists = false;
    let debounceTimer;

    function showWarning() {
        if ($('#duplicate-warning').length === 0) {
            $('#titlewrap').after('<div id="duplicate-warning" class="notice notice-error"><p>A Zone with this name already exists for this restaurant. Cannot publish.</p></div>');
        }
    }

    function removeWarning() {
        $('#duplicate-warning').remove();
    }

    function checkDuplicate(async = true) {
        let title = $('#title').val().trim();
        let restaurant_id = $('#restaurant_id').val();
        let postId = zoneCheckData.exclude_id || 0;

        if (!title || !restaurant_id) {
            removeWarning();
            duplicateExists = false;
            return;
        }

        $.ajax({
            url: zoneCheckData.rest_url,
            method: 'GET',
            data: { 
                name: title,
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

    $('#title').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => checkDuplicate(true), 300);
    });

    $('#restaurant_id').on('change', function() {
        checkDuplicate(true);
    });

    $('#post').on('submit', function(e) {
        checkDuplicate(false); // force sync to block immediately
        if (duplicateExists) {
            e.preventDefault();
            alert("Cannot publish. A Zone with this name already exists for this restaurant.");
            return false;
        }
    });

    checkDuplicate(true);
});
