jQuery(document).ready(function($) {
    let restaurantCount = $('#existing-restaurants .restaurant-existing-row').length;

    function addRestaurantRow(slug = '', name = '', address = '') {
        restaurantCount++;
        const row = `
        <div class="restaurant-row" data-index="${restaurantCount}">
            <input type="number" name="restaurant_id[]" value="${restaurantCount}" readonly>
            <input type="text" name="restaurant_slug[]" placeholder="Slug" value="${slug}" autocomplete="off" required>
            <input type="text" name="restaurant_name[]" placeholder="Restaurant Name" value="${name}" autocomplete="off" required>
            <textarea name="restaurant_address[]" placeholder="Address" rows="2" autocomplete="off" required>${address}</textarea>
            <button type="button" class="remove-restaurant button">Remove</button>
        </div>`;
        $('#restaurant-list').append(row);
    }

    addRestaurantRow(); // Add one blank row initially

    $('#add-restaurant-row').click(function() {
        addRestaurantRow();
    });

    $(document).on('click', '.remove-restaurant', function() {
        const allRows = $('#restaurant-list .restaurant-row');
        if (allRows.length > 1) {
            const index = $(this).closest('.restaurant-row').index();
            if (index > 0) {
                $(this).closest('.restaurant-row').remove();
                updateRestaurantNumbers();
            }
        }
    });

    function updateRestaurantNumbers() {
        let index = $('#existing-restaurants .restaurant-existing-row').length;
        $('#restaurant-list .restaurant-row').each(function() {
            index++;
            $(this).attr('data-index', index);
            $(this).find('input[name="restaurant_id[]"]').val(index);
        });
        restaurantCount = index;
    }

    $('#restaurant-form').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        formData += '&action=save_multiple_restaurants';
        formData += '&security=' + pinakaRestaurantData.nonce;

        $.post(pinakaRestaurantData.ajax_url, formData, function(response) {
            if (response.success) {
                $('#restaurant-save-message').html('<p style="color:green;">' + response.data.message + '</p>');
                setTimeout(() => location.reload(), 500);
            } else {
                $('#restaurant-save-message').html('<p style="color:red;">' + response.data.message + '</p>');
            }
        });
    });
});