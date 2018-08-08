<?php
/**
 * Plugin Name: WooCommerce Max Membership Rules
 * Plugin URI: https://craftpeak.com/todo
 * Description: Extends the WooCommerce Min/Max Quantities and Memberships plugins to allow defining specific item maximums per membership level on a per-product basis.
 * Version: 1.0.1
 * Author: Craftpeak
 * Author URI: https://craftpeak.com
 * Requires at least: 4.0
 * Tested up to: 4.9.8
 * Text Domain: woocommerce-max-membership-rules
 */

class WC_Max_Membership_Rules {
	public function __construct() {
		// Write the Admin Panel
		add_action( 'woocommerce_product_options_general_product_data', [ &$this, 'write_panel' ] );
		// Process the Admin Panel Saving
		add_action( 'woocommerce_process_product_meta', [ &$this, 'write_panel_save' ] );

		// Add Filter for taking over the min/max quantity calculation at the membership level
		add_filter( 'wc_min_max_quantity_maximum_allowed_quantity', [ &$this, 'calculate_membership_quantity_override' ], 11, 4 );
	}

	/**
	 * Return an array of all membership levels
	 *
	 * @return array
	 */
	public function get_membership_levels() {
		$membership_levels = get_posts( [
			'post_type'      => 'wc_membership_plan',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		] );

		if ( ! empty( $membership_levels ) ) {
			return $membership_levels;
		}

		return false;
	}

	/**
	 * Function to sanitize membership keys
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function sanitize_membership_key( $post_id ) {
		$post_name = get_post_field( 'post_name', $post_id );

		if ( $post_name ) {
			return str_replace( '-', '_', $post_name );
		}

		return false;
	}

	/**
	 * Simple function to combine the meta_value prefix with a membership key
	 *
	 * @param $membership_key
	 *
	 * @return string
	 */
	public function combine_membership_key( $membership_key ) {
		return 'maximum_allowed_quantity_membership_' . $membership_key;
	}

	/**
	 * Function to write the HTML/form fields for the product panel
	 */
	public function write_panel() {
		// Grab our membership levels
		$membership_levels = $this->get_membership_levels();

		if ( $membership_levels ) {
			// Open Options Group
			echo '<div class="options_group">';

			// Loop through the membership levels and add min/max quantity inputs for each
			foreach ( $membership_levels as $membership_level ) {
				// Get membership info
				$membership_title = $membership_level->post_title;
				$membership_key = $this->sanitize_membership_key( $membership_level->ID );

				if ( $membership_key ) {
					woocommerce_wp_text_input( [
						'id'          => 'maximum_allowed_quantity_membership_' . $membership_key,
						'label'       => sprintf( __( '%s max quantity', 'woocommerce-max-membership-rules' ), $membership_title ),
						'description' => __( 'Enter a quantity to prevent a member from buying this product if they have more than the allowed quantity in their cart. Don\'t enter 0, it won\'t work.', 'woocommerce-max-membership-rules' ),
						'desc_tip'    => true,
					] );
				}
			}

			// Close Options Group
			echo '</div>';
		}
	}

	/**
	 * Function to save our custom write panel values
	 *
	 * @param $post_id
	 */
	public function write_panel_save( $post_id ) {
		// Grab our membership levels
		$membership_levels = $this->get_membership_levels();

		// Loop through our membership levels and save the values, if we did anything with them
		if ( $membership_levels ) {
			foreach( $membership_levels as $membership_level ) {
				$membership_key = $this->sanitize_membership_key( $membership_level->ID );
				$combined_key = $this->combine_membership_key( $membership_key );

				if ( isset( $_POST[ $combined_key ] ) ) {
					update_post_meta( $post_id, $combined_key, esc_attr( $_POST[ $combined_key ] ) );
				}
			}
		}
	}

	/**
	 * Calculate the new quantity allowed based on the user's membership, if there is one
	 * Hooks into the wc_min_max_quantity_maximum_allowed_quantity filter
	 * Provides a similar wc_max_membership_rules_quantity_override filter
	 *
	 * @param $initial_quantity
	 * @param $checking_id
	 * @param $cart_item_key
	 * @param $values
	 *
	 * @return int
	 */
	public function calculate_membership_quantity_override( $initial_quantity, $checking_id, $cart_item_key, $values ) {
		// See if this user has any memberships...
		$user_memberships = wc_memberships_get_user_memberships();

		// Bail is the user has no memberships...
		if ( empty( $user_memberships ) ) {
			return $initial_quantity;
		}

		// Grab the last membership level to use
		$user_membership = array_pop( $user_memberships );

		// Get the membership key
		if ( $user_membership->plan_id ) {
			$membership_key = $this->sanitize_membership_key( $user_membership->plan_id );
			$combined_key = $this->combine_membership_key( $membership_key );

			// Get/set a new maximum order quantity, passing all above values in a new filter for future use
			$maximum_quantity  = absint( apply_filters( 'wc_max_membership_rules_quantity_override', get_post_meta( $checking_id, $combined_key, true ), $checking_id, $cart_item_key, $values ) );

			// Return the new max quantity if we have it
			if ( $maximum_quantity ) {
				return $maximum_quantity;
			}
		}

		// By default, return the initial amount
		return $initial_quantity;
	}
}

// TODO: Make this work only if both plugins are active and have been instantiated
add_action( 'plugins_loaded', function() {
	$WC_Max_Membership_Rules = new WC_Max_Membership_Rules();
} );

