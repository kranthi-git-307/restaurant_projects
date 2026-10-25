<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/includes
 * @author     Pinakageeks <info@pinaka.com>
 */
class Pinaka_Pos_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_customer_balance_tb();
		
	}

	/**
	 * To create customer balance table to log data.
	 */
	public static function create_customer_balance_tb() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'pp_customer_balance';

		// Check if the table already exists .
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {

			// Create the table.
			$sql = "CREATE TABLE $table_name (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                order_id INT,
                due_date DATE NOT NULL,
                payment_method VARCHAR(255) NOT NULL,
                remark TEXT,
                created_date DATETIME NOT NULL,
                created_date_gmt DATETIME NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) NOT NULL,
                PRIMARY KEY (id)
              );";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

}
