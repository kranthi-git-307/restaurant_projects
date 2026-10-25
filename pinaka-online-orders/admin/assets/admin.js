jQuery(function ($) {

    let currentPage = 1;
    const itemsPerPage = 20;

    $(document).ready(function(){
        loadOrders(1);
    });
    $("#connect-orderout").on("click", function (e) {

        e.preventDefault();

        const $button = $(this);
        const $message = $("#orderout-message");

        $button.prop("disabled", true).text("Connecting...");

        $.ajax({

            url: orderout_admin.connect_url,
            method: "POST",
            dataType: "json",

            data: {
                email: orderout_admin.email
            },

            success: function (response) {

                if (!response.success) {

                    $message
                        .removeClass("notice-success")
                        .addClass("notice-error")
                        .html(response.message);

                    $button.prop("disabled", false).text("Connect OrderOut");

                    return;
                }

                // Save response into current website

                $.ajax({

                    url: orderout_admin.save_url,
                    method: "POST",

                    beforeSend: function (xhr) {

                        xhr.setRequestHeader(
                            "X-WP-Nonce",
                            orderout_admin.nonce
                        );

                    },

                    contentType: "application/json",

                    data: JSON.stringify({

                        connected: true,
                        connected_at: new Date().toISOString(),
                        api_response: response.data

                    }),

                    success: function () {

                        $message
                            .removeClass("notice-error")
                            .addClass("notice-success")
                            .html("Connected Successfully.");

                        location.reload();

                    },

                    error: function () {

                        $message
                            .removeClass("notice-success")
                            .addClass("notice-error")
                            .html("Unable to save settings.");

                    },

                    complete: function () {

                        $button.prop("disabled", false)
                            .text("Connect OrderOut");

                    }

                });

            },

            error: function (xhr) {

                console.log(xhr.responseText);

                $button.prop("disabled", false)
                    .text("Connect OrderOut");

                $message
                    .removeClass("notice-success")
                    .addClass("notice-error")
                    .html("Unable to connect.");

            }

        });

    });

    $("#load_orders").click(function () {

        loadOrders(1);

    });

    function loadOrders(page = 1){
        currentPage = page;
        $("#order_loader").show();
        console.log("Loading orders...");
        $.ajax({

            url:orderout_admin.orders_url,

            method:"GET",

            beforeSend:function(xhr){

                xhr.setRequestHeader(
                    "X-WP-Nonce",
                    orderout_admin.nonce
                );

            },

            data:{
                page:currentPage,
                items_per_page:20,
                status:$("#order_status").val(),
                from_date:$("#from_date").val(),
                to_date:$("#to_date").val(),
                search:$("#search").val()
            },

            success:function(response){

                $("#order_loader").hide();

                let html='';

                if(response.data.length==0){

                    html='<tr><td colspan="10">No Orders Found.</td></tr>';

                    $("#orders_body").html(html);

                    return;

                }

                $.each(response.data,function(i,order){

                    let badge='';

                    switch(order.status){

                        case 'CONFIRMED':
                            badge='<span class="button button-primary">'+order.status+'</span>';
                            break;

                        case 'ACCEPTED':
                            badge='<span class="button button-secondary">'+order.status+'</span>';
                            break;

                        case 'READY':
                            badge='<span class="button" style="background:#46b450;color:#fff;">READY</span>';
                            break;

                        case 'COMPLETED':
                            badge='<span class="button" style="background:#00a32a;color:#fff;">COMPLETED</span>';
                            break;

                        default:
                            badge='<span class="button">'+order.status+'</span>';

                    }

                    html+=`

                    <tr>

                        <td>${order.id}</td>

                        <td>

                            <strong>${order.customer_name}</strong><br>

                            ${order.customer_phone}<br>

                            ${order.customer_email}

                        </td>

                        <td>${order.delivery_service.type}</td>

                        <td>${order.type}</td>

                        <td>${badge}</td>

                        <td>${order.order_items.length}</td>

                        <td>$${order.charge_total}</td>

                        <td>${order.ready_by}</td>

                        <td>${order.api_created_at}</td>

                        <td>

                            <button class="button view-order"

                            data-index="${i}">

                            View

                            </button>

                            ${!['CANCELLED', 'COMPLETED'].includes(order.status) ? `
                            <button class="button button-link-delete cancel-order"
                                data-order-id="${order.id}">
                                Cancel
                            </button>` : ''}

                        </td>

                    </tr>

                    `;

                });

                $("#orders_body").html(html);

                $("#page_info").text("Page " + currentPage);

                if(currentPage == 1){
                    $("#prev_page").prop("disabled",true);
                }else{
                    $("#prev_page").prop("disabled",false);
                }

                if(response.data.length < itemsPerPage){
                    $("#next_page").prop("disabled",true);
                }else{
                    $("#next_page").prop("disabled",false);
                }

            }

        });

    }

    $(document).on("click", ".cancel-order", function () {
        const $button = $(this);
        const orderId = String($button.data("order-id"));

        if (!window.confirm("Cancel order " + orderId + " because an item is unavailable?")) {
            return;
        }

        $button.prop("disabled", true).text("Cancelling...");

        $.ajax({
            url: orderout_admin.cancel_order_url + encodeURIComponent(orderId) + "/cancel",
            method: "POST",
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("X-WP-Nonce", orderout_admin.nonce);
            },
            data: JSON.stringify({ reason: "ITEM_UNAVAILABLE" }),
            success: function () {
                window.alert("Order cancelled successfully.");
                loadOrders(currentPage);
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : "Unable to cancel the order.";
                window.alert(message);
                $button.prop("disabled", false).text("Cancel");
            }
        });
    });

    $("#next_page").click(function(){

        loadOrders(currentPage + 1);

    });

    $("#prev_page").click(function(){

        if(currentPage > 1){

            loadOrders(currentPage - 1);

        }
    });

    $(document).on("click", "#connect_webhook", function () {

        $("#webhook_progress").show();

        $("#progress_bar").val(20);

        $("#progress_text").text("Connecting...");

        $.ajax({

            url: orderout_webhook.connect_webhook,

            type: "POST",

            beforeSend: function (xhr) {

                xhr.setRequestHeader(
                    "X-WP-Nonce",
                    orderout_webhook.nonce
                );

            },

            success: function (response) {

                console.log(response);

                $("#progress_bar").val(100);

                $("#progress_text").text("Completed");

                if(response.success){

                    $("#webhook_message").html(
                        '<div class="notice notice-success"><p>'+response.message+'</p></div>'
                    );

                }else{

                    $("#webhook_message").html(
                        '<div class="notice notice-error"><p>'+response.message+'</p></div>'
                    );

                }

            },

            error:function(xhr){

                console.log(xhr);

                $("#progress_bar").val(100);

                $("#progress_text").text("Failed");

                $("#webhook_message").html(
                    '<div class="notice notice-error"><p>'+xhr.responseText+'</p></div>'
                );

            }

        });

    });

    document.getElementById('orderout-menu-form').addEventListener('submit', function(e){
            
        const menuName = document.getElementById('menu_name').value.trim();
        const menuDescription = document.getElementById('menu_description').value.trim();
    
        if(menuName === ''){
            alert('Please enter Menu Name.');
            document.getElementById('menu_name').focus();
            e.preventDefault();
            return;
        }
    
        if(menuDescription === ''){
            alert('Please enter Menu Description.');
            document.getElementById('menu_description').focus();
            e.preventDefault();
            return;
        }
    
        const checkedProducts = document.querySelectorAll('.product-checkbox:checked');
    
        if(checkedProducts.length === 0){
            alert('Please select at least one product.');
            e.preventDefault();
            return;
        }
    
    });
    document.querySelectorAll('.category-checkbox').forEach(function(category){

        category.addEventListener('change', function(){
    
            const card = this.closest('.category-card');
    
            card.querySelectorAll('.product-checkbox').forEach(function(product){
                product.checked = category.checked;
            });
    
        });
    
    });
    document.querySelectorAll('.category-card').forEach(function(card) {

        const category = card.querySelector('.category-checkbox');
        const total = card.querySelectorAll('.product-checkbox').length;
        const checked = card.querySelectorAll('.product-checkbox:checked').length;
    
        category.checked = (total > 0 && checked === total);
        category.indeterminate = (checked > 0 && checked < total);
    
    });
    document.querySelectorAll('.product-checkbox').forEach(function(product){
    
        product.addEventListener('change', function(){
    
            const card = this.closest('.category-card');
            const category = card.querySelector('.category-checkbox');
    
            const total = card.querySelectorAll('.product-checkbox').length;
            const checked = card.querySelectorAll('.product-checkbox:checked').length;
    
            category.checked = checked === total;
            category.indeterminate = checked > 0 && checked < total;
    
        });
    
    });

});
