jQuery(function ($) {

    $(document).on('click', '.pinaka-view-shift', function (e) {

        e.preventDefault();

        var shift_id = $(this).data('id');

        tb_show(
            'Shift Details',
            '#TB_inline?inlineId=pinaka-shift-modal&width=900&height=600'
        );

        $('#TB_ajaxContent').html('<p>Loading...</p>');

        $.post(pinakaShift.ajax_url, {
            action: 'pinaka_shift_view',
            id: shift_id,
            nonce: pinakaShift.nonce
        }, function (response) {

            $('#TB_ajaxContent').html(response);

        });

    });

});