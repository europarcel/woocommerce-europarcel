<?php
namespace EawbShipping;
defined('ABSPATH') || exit;

class Eawb_Shipping_Custom_Fields {
    
     public static function fixed_price_group($shipping_method) {
        ob_start();
        ?>
        <tr valign="top" class="eawb-price-type-dependent eawb-fixed-price">
            <th scope="row"><?php _e('Preț fix transport', 'woocommerce-shipping-plugin'); ?></th>
            <td>
                <input type="text" 
                       name="<?php echo esc_attr($shipping_method->get_field_key('fixed_price')); ?>" 
                       value="<?php echo esc_attr($shipping_method->get_option('fixed_price', '15')); ?>"
                       style="width: 80px; margin-right: 10px;" />
                <span class="description"><?php _e('RON', 'woocommerce-shipping-plugin'); ?></span>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Generează HTML pentru grupul de preț calculat
     */
    public static function calculated_price_group($shipping_method) {
        ob_start();
        ?>
        <tr valign="top" class="eawb-price-type-dependent eawb-calculated-price">
            <th scope="row"><?php _e('Parametri calcul', 'woocommerce-shipping-plugin'); ?></th>
            <td>
                <span class="description" style="margin-right: 15px;"><?php _e('Greutate (kg)', 'woocommerce-shipping-plugin'); ?></span>
                <input type="text" 
                       name="<?php echo esc_attr($shipping_method->get_field_key('default_weight')); ?>" 
                       value="<?php echo esc_attr($shipping_method->get_option('default_weight', '1')); ?>"
                       style="width: 60px;" />
                <span class="description" style="margin-right: 15px;"><?php _e('Lungime (cm)', 'woocommerce-shipping-plugin'); ?></span>
                <input type="text" 
                       name="<?php echo esc_attr($shipping_method->get_field_key('default_length')); ?>" 
                       value="<?php echo esc_attr($shipping_method->get_option('default_length', '15')); ?>"
                       style="width: 60px;" />
                <span class="description" style="margin-right: 15px;"><?php _e('Latime (cm)', 'woocommerce-shipping-plugin'); ?></span>
                <input type="text" 
                       name="<?php echo esc_attr($shipping_method->get_field_key('default_width')); ?>" 
                       value="<?php echo esc_attr($shipping_method->get_option('default_width', '15')); ?>"
                       style="width: 60px;" />
                <span class="description" style="margin-right: 15px;"><?php _e('Inaltime (cm)', 'woocommerce-shipping-plugin'); ?></span>
                <input type="text" 
                       name="<?php echo esc_attr($shipping_method->get_field_key('default_height')); ?>" 
                       value="<?php echo esc_attr($shipping_method->get_option('default_height', '15')); ?>"
                       style="width: 60px;" />
                
                <span class="description"><?php _e('Multiplicator preț', 'woocommerce-shipping-plugin'); ?></span>
                <input type="text" 
                       name="<?php echo esc_attr($shipping_method->get_field_key('price_multiplier')); ?>" 
                       value="<?php echo esc_attr($shipping_method->get_option('price_multiplier', '1.2')); ?>"
                       style="width: 60px;" />
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
}