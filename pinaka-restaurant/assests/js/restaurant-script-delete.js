jQuery(document).ready(function($) {
    $(document).on('click', '.delete-existing-restaurant', function() {
        const $row = $(this).closest('.restaurant-existing-row');
        const restaurantId = $row.data('restaurant-id');
        if (confirm('Are you sure you want to delete this restaurant?')) {
            $.post(pinakaDelRestaurantData.ajax_url, {
                action: 'delete_single_restaurant',
                restaurant_id: restaurantId,
                _ajax_nonce: pinakaDelRestaurantData.nonce
            }, function(response) {
                if (response.success) {
                    $row.remove();
                    $('#restaurant-save-message').html('<p style="color:green;">' + response.data.message + '</p>');
                    location.reload();
                } else {
                    $('#restaurant-save-message').html('<p style="color:red;">' + response.data.message + '</p>');
                    location.reload();
                }
            });
        }
    });

});