jQuery(document).ready(function($) {
    $(document).on('click', '.edit-restaurant', function() {
        let row = $(this).closest('.restaurant-existing-row');
        row.find('input, textarea').prop('readonly', false);
        row.find('.edit-restaurant').hide();
        row.find('.save-restaurant').show();
    });

    $(document).on('click', '.save-restaurant', function() {
        let row = $(this).closest('.restaurant-existing-row');

        let data = {
            action: 'update_restaurant',
            security: pinakaEditRestaurantData.nonce,
            restaurant_id: row.find('.restaurant-id').val(),
            restaurant_slug: row.find('.restaurant-slug').val(),
            restaurant_name: row.find('.restaurant-name').val(),
            restaurant_address: row.find('.restaurant-address').val()
        };

        $.post(pinakaEditRestaurantData.ajax_url, data, function(response) {
            if (response.success) {
                alert('Restaurant updated successfully!');
                row.find('input, textarea').prop('readonly', true);
                row.find('.save-restaurant').hide();
                row.find('.edit-restaurant').show();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

});