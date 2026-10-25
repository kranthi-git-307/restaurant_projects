jQuery(function($) {

    function restaurantSelect(selected = '') {
        let options = `<option value="">-- Select Restaurant --</option>`;

        (PINAKA_ADDON.restaurants || []).forEach(r => {
            const sel = String(r.id) === String(selected) ? 'selected' : '';
            options += `<option value="${r.id}" ${sel}>${r.name}</option>`;
        });

        return `
            <select class="restaurant-id" required>
                ${options}
            </select>
        `;
    }

    function rowTemplate(row = {}) {
        const actionCell = row.addon_id
        ? `<td></td>`
        : `
        <td>
            <button type="button" class="button remove-row">X</button>
        </td>`;
        return `
        <tr data-addon-id="${row.addon_id || ''}">
            <td>
                ${row.count || ''}
            </td>
            <td>
                ${restaurantSelect(row.restaurant_id || PINAKA_ADDON.user_restaurant)}
            </td>
            <td>
                <input type="text" class="product-search" value="${row.product_name || ''}" placeholder="Search product" required>
                <input type="hidden" class="product-id" value="${row.product_id || ''}">
            </td>
            <td>
                ${addonTypeSelect(row.type)}
            </td>
            <td>
                <input type="text" class="addon-name" value="${row.addon_name || ''}" placeholder="Addon name" required>
            </td>
            <td>
                <input type="number" class="addon-price" step="0.01" value="${row.price || ''}" placeholder="Price" required>
            </td>
            ${actionCell}
        </tr>`;
    }
    function addonTypeSelect(selected = 'add-on') {
        const types = [
            { id: 'add-on', label: 'Add-on' },
            { id: 'modifier', label: 'Modifier' }
        ];

        let options = '';
        types.forEach(t => {
            const sel = String(t.id) === String(selected) ? 'selected' : '';
            options += `<option value="${t.id}" ${sel}>${t.label}</option>`;
        });

        return `
            <select class="addon-type" required>
                ${options}
            </select>
        `;
    }

    // Load existing
    function loadAddons() {
        $.post(PINAKA_ADDON.ajax, {
            action: 'pin_get_addons',
            nonce: PINAKA_ADDON.nonce
        }, function(resp) {
            if (!resp.success) return;

            resp.data.forEach(row => {
                $('#addon-table tbody').append(rowTemplate(row));
            });
        });
    }

    loadAddons();

    $('#add-row').on('click', function() {
        $('#addon-table tbody').append(rowTemplate());
    });

    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });
    // Handle addon type change → control price field
    $(document).on('change', '.addon-type', function () {
        const $row = $(this).closest('tr');
        const $price = $row.find('.addon-price');
        const type = $(this).val();

        if (type === 'modifier') {
            $price.val(0);
            $price.prop('readonly', true);
            $price.addClass('readonly-price');
        } else {
            $price.prop('readonly', false);
            $price.removeClass('readonly-price');
        }
    });

    // Autocomplete
    $(document).on('focus', '.product-search', function() {

        if ($(this).data('ui-autocomplete')) return;

        $(this).autocomplete({
            minLength: 2,
            source: function(req, res) {
                $.post(PINAKA_ADDON.ajax, {
                    action: 'pin_search_products',
                    nonce: PINAKA_ADDON.nonce,
                    term: req.term
                }, res);
            },
            select: function(e, ui) {
                $(this).val(ui.item.label);
                $(this).closest('td').find('.product-id').val(ui.item.id);
                return false;
            }
        });
    });

    // Save
    // $('#pin-addon-form').on('submit', function(e) {
    //     e.preventDefault();

    //     let rows = [];

    //     $('#addon-table tbody tr').each(function() {
    //         rows.push({
    //             addon_id: $(this).data('addon-id'),
    //             restaurant_id: $(this).find('.restaurant-id').val(),
    //             product_id: $(this).find('.product-id').val(),
    //             addon_name: $(this).find('.addon-name').val(),
    //             price: $(this).find('.addon-price').val(),
    //             type: $(this).find('.addon-type').val()
    //         });
    //     });

    //     $.post(PINAKA_ADDON.ajax, {
    //         action: 'pin_save_addons',
    //         nonce: PINAKA_ADDON.nonce,
    //         rows: rows
    //     }, function(resp) {
    //         alert(resp.data);
    //         location.reload();
    //     });
    // });
    $('#pin-addon-form').on('submit', function(e) {
        e.preventDefault();

        let rows = [];
        let seen = {};
        let hasError = false;
        let errorMsg = '';

        $('#addon-table tbody tr').each(function(index) {

            const product_id = $(this).find('.product-id').val();
            const type       = $(this).find('.addon-type').val();
            const addon_name = $(this).find('.addon-name').val().trim();
            const restaurant = $(this).find('.restaurant-id').val();
            const price      = $(this).find('.addon-price').val();
            const addon_id  = $(this).data('addon-id');

            if (!restaurant || !product_id || !type || !addon_name) {
                hasError = true;
                errorMsg = 'Please fill all required fields before saving.';
                return false;
            }

            const key = [
                restaurant,
                product_id,
                type.toLowerCase(),
                addon_name.toLowerCase()
            ].join('|');

            if (seen[key]) {
                hasError = true;
                errorMsg = `Duplicate Add-on detected:\n\nProduct ID: ${product_id}\nType: ${type}\nName: "${addon_name}"\n\nEach product cannot have the same add-on name for the same type.`;
                return false;
            }

            seen[key] = true;

            rows.push({
                addon_id: addon_id,
                restaurant_id: restaurant,
                product_id: product_id,
                addon_name: addon_name,
                price: price,
                type: type
            });
        });

        if (hasError) {
            alert(errorMsg);
            return;
        }
        $.post(PINAKA_ADDON.ajax, {
            action: 'pin_save_addons',
            nonce: PINAKA_ADDON.nonce,
            rows: rows
        }, function(resp) {
            alert(resp.data);
            location.reload();
        });
    });
});