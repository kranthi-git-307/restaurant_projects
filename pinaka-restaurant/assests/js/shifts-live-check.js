jQuery(function($) {
    let duplicateFound = { name: false, start: false, end: false };
    let sameAsExisting = false;

    function checkDuplicate(sync = false) {
        let title = $('#title').val();
        let start = $('#shift_start_time').val();
        let end = $('#shift_end_time').val();
        let date = $('#shift_date').val();

        if (!title && !start && !end && !date) {
            removeWarning();
            duplicateFound = { name: false, start: false, end: false };
            sameAsExisting = false;
            return;
        }

        $.ajax({
            url: shiftCheckData.rest_url,
            method: 'GET',
            data: {
                name: title,
                start: start,
                end: end,
                date: date,
                post_id: shiftCheckData.post_id
            },
            async: !sync,
            success: function(response) {
                duplicateFound = response.found_in_others || { name: false, start: false, end: false };
                sameAsExisting = response.same_as_existing || false;

                if (sameAsExisting) {
                    showInfo("Same values as existing shift. No actual change.");
                } else if (duplicateFound.name || duplicateFound.start || duplicateFound.end) {
                    showWarning(duplicateFound);
                } else {
                    removeWarning();
                }
            }
        });
    }

    function showWarning(found) {
        let msg = '<strong>Duplicate detected in other shifts:</strong> ';
        let parts = [];
        if (found.name) parts.push('shift name');
        if (found.start) parts.push('start time');
        if (found.end) parts.push('end time');
        msg += parts.join(', ');
        updateNotice(msg, 'notice-error');
    }

    function showInfo(msg) {
        updateNotice('<strong>Info:</strong> ' + msg, 'notice-info');
    }

    function updateNotice(msg, cls) {
        if ($('#duplicate-warning').length === 0) {
            $('#titlewrap').after('<div id="duplicate-warning" class="notice '+cls+'"><p>'+msg+'</p></div>');
        } else {
            $('#duplicate-warning').removeClass('notice-error notice-info').addClass(cls);
            $('#duplicate-warning p').html(msg);
        }
    }

    function removeWarning() {
        $('#duplicate-warning').remove();
    }

    // Listen on inputs including shift_date
    $('#title, #shift_start_time, #shift_end_time, #shift_date').on('input change', function() {
        setTimeout(() => checkDuplicate(), 300);
    });

    // Block form submit if duplicate found
    $('#post').on('submit', function(e) {
        setTimeout(() => checkDuplicate(true), 300);
        if (!sameAsExisting && (duplicateFound.name || duplicateFound.start || duplicateFound.end)) {
            e.preventDefault();
            window.onbeforeunload = null;
            alert("Cannot publish. Duplicate shift found for this date in other shifts.");
            showWarning(duplicateFound);
            return false;
        }
    });

    // Run once on page load
    setTimeout(() => checkDuplicate(), 300);
});
