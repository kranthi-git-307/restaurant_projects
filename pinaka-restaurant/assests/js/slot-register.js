jQuery(document).ready(function($) {
    let slotIndex = $('#existing-slots .slot-existing-row').length;

    function generateTimeOptions(start, end) {
        const times = [];
        const startTime = new Date(`1970-01-01T${start}`);
        const endTime = new Date(`1970-01-01T${end}`);

        while (startTime <= endTime) {
            const hours = startTime.getHours();
            const minutes = startTime.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHour = ((hours + 11) % 12 + 1).toString().padStart(2, '0');
            const displayMinute = minutes.toString().padStart(2, '0');
            const displayTime = `${displayHour}:${displayMinute} ${ampm}`;
            const valueTime = `${hours.toString().padStart(2, '0')}:${displayMinute}`;
            times.push({ value: valueTime, label: displayTime });
            startTime.setMinutes(startTime.getMinutes() + 15);
        }
        return times;
    }

    function getTimeOptions(type) {
        switch (type) {
            case 'breakfast':
                return generateTimeOptions("09:00", "11:45");
            case 'lunch':
                return generateTimeOptions("12:00", "17:45");
            case 'dinner':
                return generateTimeOptions("18:00", "23:45");
            default:
                return [];
        }
    }
    function generateRestaurantOptions() {
        let options = `<option value="">Select Restaurant</option>`;

        if (Array.isArray(PinakaSlotData.restaurants)) {
            PinakaSlotData.restaurants.forEach(res => {
                options += `<option value="${res.id}">${res.name} (ID: ${res.id})</option>`;
            });
        }

        return options;
    }


    function addSlotRow(type = '', time = '') {
        slotIndex++;
        const restaurantOptions = generateRestaurantOptions();
        const row = $(`
            <div class="slot-row" data-index="${slotIndex}">
                <select name="restaurant_id[]" class="restaurant-id">  
                    ${restaurantOptions}  
                </select> 
                <select name="slot_type[]" class="slot-type">
                    <option value="">Select Type</option>
                    <option value="breakfast"${type === 'breakfast' ? ' selected' : ''}>Breakfast</option>
                    <option value="lunch"${type === 'lunch' ? ' selected' : ''}>Lunch</option>
                    <option value="dinner"${type === 'dinner' ? ' selected' : ''}>Dinner</option>
                </select>
                <select name="slot_time[]" class="slot-time">
                    <option value="">Select Time</option>
                </select>
                <button type="button" class="remove-slot button">Remove</button>
            </div>
        `);

        $('#slot-list').append(row);

        // Pre-populate time dropdown if type is given
        if (type) {
            const options = getTimeOptions(type);
            const $timeSelect = row.find('.slot-time');
            $timeSelect.empty().append('<option value="">Select Time</option>');
            options.forEach(opt => {
                const selected = opt.value === time ? 'selected' : '';
                $timeSelect.append(`<option value="${opt.value}" ${selected}>${opt.label}</option>`);
            });
        }
    }

    $('#add-slot-row').on('click', function () {
        addSlotRow();
    });

    $(document).on('change', '.slot-type', function () {
        const $row = $(this).closest('.slot-row');
        const selectedType = $(this).val();
        const options = getTimeOptions(selectedType);
        const $timeSelect = $row.find('.slot-time');
        $timeSelect.empty().append('<option value="">Select Time</option>');
        options.forEach(opt => {
            $timeSelect.append(`<option value="${opt.value}">${opt.label}</option>`);
        });
    });

    $(document).on('click', '.remove-slot', function () {
        $(this).closest('.slot-row').remove();
    });

    $('#slot-form').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post(PinakaSlotData.ajax_url, formData + '&action=save_multiple_slots', function (response) {
            if (response.success) {
                $('#slot-save-message').html('<p style="color:green;">' + response.data.message + '</p>');
                setTimeout(() => location.reload(), 500);
            } else {
                $('#slot-save-message').html('<p style="color:red;">' + response.data.message + '</p>');
            }
        });
    });
    // Add one blank row initially
    addSlotRow();
});