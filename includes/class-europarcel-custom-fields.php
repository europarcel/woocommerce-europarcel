<?php

/**
 * EuroParcel Custom Fields Handler
 *
 * Handles custom form fields for the EuroParcel shipping method
 * including price type selection, fixed price settings, and
 * calculation parameters for package dimensions.
 *
 * @link       https://eawb.ro
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

namespace EuroparcelShipping;

defined('ABSPATH') || exit;

/**
 * EuroParcel Shipping Custom Fields Class
 *
 * Provides static methods for generating custom form fields
 * used in the shipping method admin configuration. Handles
 * price type selection and calculation parameter inputs.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class Europarcel_Shipping_Custom_Fields {

	/**
	 * Generate fixed price form field group
	 *
	 * Creates HTML for the fixed price input field used when
	 * the shipping method is set to fixed price mode.
	 *
	 * @since    1.0.0
	 * @param    object    $shipping_method    The shipping method instance
	 * @return   string    HTML for the fixed price field group
	 */
	public static function fixed_price_group($shipping_method) {
		ob_start();
		?>
		<tr valign="top" class="europarcel-price-type-dependent europarcel-fixed-price">
			<th scope="row"><?php _e('Fixed price', 'europarcel'); ?></th>
			<td>
				<input type="text" 
				       name="<?php echo esc_attr($shipping_method->get_field_key('fixed_price')); ?>" 
				       value="<?php echo esc_attr($shipping_method->get_option('fixed_price', '15')); ?>"
				       style="width: 80px; margin-right: 10px;" />
				<span class="description"><?php _e('RON', 'europarcel'); ?></span>
			</td>
		</tr>
		<?php
		return ob_get_clean();
    }

	/**
	 * Generate calculated price form field group
	 *
	 * Creates HTML for the calculation parameters including package
	 * dimensions (weight, length, width, height) and price multiplier.
	 *
	 * @since    1.0.0
	 * @param    object    $shipping_method    The shipping method instance
	 * @return   string    HTML for the calculation parameters field group
	 */
	public static function calculated_price_group($shipping_method) {
		ob_start();
		?>
		<tr valign="top" class="europarcel-price-type-dependent europarcel-calculated-price">
			<th scope="row"><?php _e('Calculation parameters', 'europarcel'); ?></th>
			<td>
				<span class="description" style="margin-right: 15px;"><?php _e('Weight (kg)', 'europarcel'); ?></span>
				<input type="text" 
				       name="<?php echo esc_attr($shipping_method->get_field_key('default_weight')); ?>" 
				       value="<?php echo esc_attr($shipping_method->get_option('default_weight', '1')); ?>"
				       style="width: 60px;" />
				<span class="description" style="margin-right: 15px;"><?php _e('Length (cm)', 'europarcel'); ?></span>
				<input type="text" 
				       name="<?php echo esc_attr($shipping_method->get_field_key('default_length')); ?>" 
				       value="<?php echo esc_attr($shipping_method->get_option('default_length', '15')); ?>"
				       style="width: 60px;" />
				<span class="description" style="margin-right: 15px;"><?php _e('Width (cm)', 'europarcel'); ?></span>
				<input type="text" 
				       name="<?php echo esc_attr($shipping_method->get_field_key('default_width')); ?>" 
				       value="<?php echo esc_attr($shipping_method->get_option('default_width', '15')); ?>"
				       style="width: 60px;" />
				<span class="description" style="margin-right: 15px;"><?php _e('Height (cm)', 'europarcel'); ?></span>
				<input type="text" 
				       name="<?php echo esc_attr($shipping_method->get_field_key('default_height')); ?>" 
				       value="<?php echo esc_attr($shipping_method->get_option('default_height', '15')); ?>"
				       style="width: 60px;" />

				<span class="description"><?php _e('Price multiplier', 'europarcel'); ?></span>
				<input type="text" 
				       name="<?php echo esc_attr($shipping_method->get_field_key('price_multiplier')); ?>" 
				       value="<?php echo esc_attr($shipping_method->get_option('price_multiplier', '1.2')); ?>"
				       style="width: 60px;" />
			</td>
		</tr>
		<?php
		return ob_get_clean();
    }

	/**
	 * Generate price type selection field
	 *
	 * Creates HTML for the price type selector allowing users to choose
	 * between fixed price and calculated price shipping methods.
	 *
	 * @since    1.0.0
	 * @param    object    $shipping_method    The shipping method instance
	 * @return   string    HTML for the price type selector field
	 */
	public static function price_type($shipping_method) {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row">
				<label for="<?php echo esc_attr($shipping_method->get_field_id('price_type')); ?>">
					<?php _e('Tip preț transport', 'europarcel'); ?>
				</label>
			</th>
			<td>
				<select 
					id="<?php echo esc_attr($shipping_method->get_field_id('price_type')); ?>" 
					name="<?php echo esc_attr($shipping_method->get_field_name('price_type')); ?>" 
					class="europarcel-price-type-selector" 
					data-instance="<?php echo esc_attr($shipping_method->instance_id); ?>"
					>
					<option value="fixed" <?php selected($shipping_method->get_option('price_type'), 'fixed'); ?>>
						<?php _e('Preț fix', 'europarcel'); ?>
					</option>
					<option value="calculated" <?php selected($shipping_method->get_option('price_type'), 'calculated'); ?>>
						<?php _e('Preț calculat', 'europarcel'); ?>
					</option>
				</select>
			</td>
		</tr>
		<?php
		return ob_get_clean();
    }
}
