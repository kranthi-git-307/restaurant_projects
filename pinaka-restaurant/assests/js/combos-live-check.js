jQuery(function($) {
    let duplicateExists = false;

    function checkDuplicate(sync = false) {
        let title = $('#title').val();
        let restaurant_id = $('#restaurant_id').val();

        if (!title || !restaurant_id) {
            removeWarning();
            duplicateExists = false;
            return;
        }

        $.ajax({
            url: comboCheckData.rest_url,
            method: 'GET',
            data: { 
                name: title, 
                restaurant_id: restaurant_id, 
                post_id: comboCheckData.exclude_id 
            },
            async: !sync,
            success: function(response) {
                if (response.found_in_others && response.found_in_others.name && !response.same_as_existing) {
                    showWarning();
                    duplicateExists = true;
                } else {
                    removeWarning();
                    duplicateExists = false;
                }
            }
        });
    }

    function showWarning() {
        if ($('#duplicate-warning').length === 0) {
            $('#titlewrap').after('<div id="duplicate-warning" class="notice notice-error"><p>A Combo with this name already exists for this restaurant. Cannot publish.</p></div>');
        }
    }

    function removeWarning() {
        $('#duplicate-warning').remove();
    }

    // Check when user types or changes restaurant
    $('#title').on('input', function() {
        setTimeout(() => checkDuplicate(), 300);
    });
    $('#restaurant_id').on('change', function() {
        setTimeout(() => checkDuplicate(), 300);
    });

    // Block form submit if duplicate found
    $('#post').on('submit', function(e) {
        if (duplicateExists) {
            e.preventDefault();
            showWarning();
            alert("Cannot publish. A Combo with this name already exists for this restaurant.");
            return false;
        }
    });

    // Initial check on page load
    setTimeout(() => checkDuplicate(), 300);
});
