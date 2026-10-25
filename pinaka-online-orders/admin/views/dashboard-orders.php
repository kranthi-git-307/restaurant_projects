<div class="wrap">

    <h1 class="wp-heading-inline">OrderOut Orders</h1>

    <hr class="wp-header-end">

    <div class="card" style="max-width:none;padding:15px;margin-bottom:20px;">

        <table class="form-table">
            <tr>

                <td>
                    <label><strong>Status</strong></label><br>
                    <select id="order_status">
                        <option value="">All Status</option>
                        <option value="CONFIRMED">Confirmed</option>
                        <option value="ACCEPTED">Accepted</option>
                        <option value="READY">Ready</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                </td>

                <td>
                    <label><strong>From</strong></label><br>
                    <input type="date" id="from_date">
                </td>

                <td>
                    <label><strong>To</strong></label><br>
                    <input type="date" id="to_date">
                </td>

                <td>
                    <label><strong>Search</strong></label><br>
                    <input type="text" id="search" placeholder="Customer / Order Id">
                </td>

                <td style="vertical-align:bottom">

                    <button class="button button-primary" id="load_orders">
                        Refresh Orders
                    </button>

                </td>

            </tr>

        </table>

    </div>

    <div id="order_loader" style="display:none">
        <p><strong>Loading Orders...</strong></p>
    </div>

    <table class="widefat striped" id="orders_table">

        <thead>

        <tr>

            <th width="120">Order ID</th>
            <th>Customer</th>
            <th>Platform</th>
            <th>Type</th>
            <th>Status</th>
            <th>Items</th>
            <th>Total</th>
            <th>Ready By</th>
            <th>Created</th>
            <th width="90">Action</th>

        </tr>

        </thead>

        <tbody id="orders_body">

        </tbody>

    </table>

    <div id="orders_pagination" style="margin-top:20px;text-align:right;">

        <button class="button" id="prev_page">
            ← Previous
        </button>

        <span id="page_info" style="padding:0 15px;"></span>

        <button class="button" id="next_page">
            Next →
        </button>

    </div>

</div>