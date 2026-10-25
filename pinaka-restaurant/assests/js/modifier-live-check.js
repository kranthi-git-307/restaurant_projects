jQuery(function($) {
    let duplicateExists = false;
    let debounceTimer;

    function showWarning() {
        if ($('#duplicate-warning').length === 0) {
            $('#titlewrap').after('<div id="duplicate-warning" class="notice notice-error"><p>A Modifier / Add-on with this name already exists in this type. Cannot publish.</p></div>');
        }
    }

    function removeWarning() {
        $('#duplicate-warning').remove();
    }

    function checkDuplicateAsync() {
        let title = $('#title').val().trim();
        let type  = $('#modifier_type').val().trim();
        let postId = modifierCheckData.exclude_id || 0;

        if (!title || !type) {
            removeWarning();
            duplicateExists = false;
            return;
        }

        $.ajax({
            url: modifierCheckData.rest_url,
            method: 'GET',
            data: { name: title, type: type, post_id: postId },
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

    function checkDuplicateSync() {
        let title = $('#title').val().trim();
        let type  = $('#modifier_type').val().trim();
        let postId = modifierCheckData.exclude_id || 0;

        if (!title || !type) {
            duplicateExists = false;
            return;
        }

        let xhr = new XMLHttpRequest();
        xhr.open('GET', modifierCheckData.rest_url + '?name=' + encodeURIComponent(title) + '&type=' + encodeURIComponent(type) + '&post_id=' + postId, false);
        xhr.send(null);

        if (xhr.status === 200) {
            let response = JSON.parse(xhr.responseText);
            if (!response.same_as_existing && response.found_in_others && response.found_in_others.name) {
                showWarning();
                duplicateExists = true;
            } else {
                removeWarning();
                duplicateExists = false;
            }
        }
    }

    $('#title').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkDuplicateAsync, 300);
    });

    $('#modifier_type').on('change', checkDuplicateAsync);

    $('#post').on('submit', function(e) {
        checkDuplicateSync();
        if (duplicateExists) {
            e.preventDefault();
            alert("Cannot publish. A Modifier / Add-on with this name already exists in this type.");
            return false;
        }
    });

    // Initial check on load
    checkDuplicateAsync();
});
