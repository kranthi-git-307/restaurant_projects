jQuery(document).ready(function($) {
    $(document).on('click', '.delete-existing-slot', function () {
        const $row = $(this).closest('.slot-existing-row');
        const slotId = $row.data('slot-id');
        if (confirm('Are you sure you want to delete this slot?')) {
            $.post(PinakaSlotDelData.ajax_url, {
                action: 'delete_single_slot',
                slot_id: slotId,
                _ajax_nonce: PinakaSlotDelData.nonce
            }, function (response) {
                if (response.success) {
                    $row.remove();
                    $('#slot-save-message').html('<p style="color:green;">' + response.data.message + '</p>');
                    location.reload();
                } else {
                    $('#slot-save-message').html('<p style="color:red;">' + response.data.message + '</p>');
                    location.reload();
                }
            });
        }
    });
});