<?php
/**
 * @package Military Discount
 * @version 2.1
 */
/*
Plugin Name: Military Discount
Plugin URI: https://wordpress.org/plugins/military-discount/
Description: This WooCommerce plugin provides a military discount option for your checkout.
Author: ID Services
Version: 2.1
Author URI: https://id.services
*/

class idsgr{

        /*************************************
        Construct:
        -Declare actions/filter
        -Queue JS
        *************************************/
        public function __construct(){
          add_filter('plugin_action_links', array($this, 'add_settings_link'), 10, 2 );
       		add_action('admin_menu', array($this,'add_admin_page'));
          add_action('woocommerce_before_cart', array($this,'hook_before_cart'));
          add_action('woocommerce_before_checkout_form', array($this,'hook_before_checkout'));
          add_action('woocommerce_cart_coupon', array($this,'hook_after_coupon'));
          add_action('wp_ajax_nopriv_gr_get_code', array($this,'gr_get_code'));
          add_action('wp_footer', array($this,'hook_footer'));
          wp_enqueue_script('idsm_run', 'https://cdn.id.services/m/run.js');
        }

        /*************************************
        Hook: plugin_action_links
        -Create settings link in plugin page
        *************************************/
        public function add_settings_link($links, $file){
      		static $this_plugin;
      		if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

      		if ($file == $this_plugin){
      			$settings_link = '<a href="admin.php?page=idsgr.php">'.__("Settings", "idsgr").'</a>';
      			array_unshift($links, $settings_link);
      		}
      		return $links;
        }

        /*************************************
        Admin load
        -Load page into site
        *************************************/
        public function add_admin_page($action = NULL){
		      add_submenu_page( $parent_slug = NULL,
				  "Military Discounts",
				  "menu title",
				  "manage_options",
				  "idsgr",
				  array($this,'show_admin_page'));
        }

        /*************************************
        Hook: woocommerce_before_cart
        *************************************/
        public function hook_before_cart(){
            if (get_option("gr_show_cart")){
                $gr_banner_url = get_option("gr_show_cart");
                include 'gr_display.php';
            }
        }

        /*************************************
        Hook: woocommerce_before_checkout_form
        *************************************/
        public function hook_before_checkout(){
            if (get_option("gr_show_checkout")){
                $gr_banner_url = get_option("gr_show_checkout");
                include 'gr_display.php';
            }
        }

        /*************************************
        Hook: woocommerce_before_checkout_form
        *************************************/
        public function hook_after_coupon(){
          if (get_option("gr_show_cart_button")){
            echo '<input type="button" class="button btn btn-default gr_verify" value="Military Discount">';
          }
        }

        /*************************************
        Hook: wp_ajax_gr_get_code
        -Create coupon
        *************************************/
        public function gr_get_code(){

          //SQL Injection Proofing
          $private_key_received = preg_replace('~[^a-zA-Z0-9]+~', '', $_SERVER['HTTP_X_IDS_KEY']);

          if (isset($_SERVER['HTTP_X_IDS_KEY']) && get_option('gr_private_key') == $private_key_received){

            //Create coupon name (invisible to customer)
            $coupon_code = 'mil-' . substr(md5(rand(100000, 99999999)), 0, 6);

            //Create coupon
            $amount = get_option("gr_amount"); // Amount
            $discount_type = get_option("gr_type"); // Type: fixed_cart, percent, fixed_product, percent_product
            $coupon = array(
              'post_title' 	=> $coupon_code,
              'post_content' 	=> '',
              'post_status' 	=> 'publish',
              'post_author' 	=> 1,
              'post_type'	=> 'shop_coupon'
            );
            $new_coupon_id = wp_insert_post( $coupon );

            //Update coupon settings
            update_post_meta($new_coupon_id, 'discount_type', $discount_type);
            update_post_meta($new_coupon_id, 'coupon_amount', $amount);
            update_post_meta($new_coupon_id, 'individual_use', 'no');
            update_post_meta($new_coupon_id, 'product_ids', '');
            update_post_meta($new_coupon_id, 'exclude_product_ids', '');
            update_post_meta($new_coupon_id, 'usage_limit', '1');
            update_post_meta($new_coupon_id, 'expiry_date', date('Y-m-d', strtotime("+7 day")));
            update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
            update_post_meta($new_coupon_id, 'free_shipping', 'no');

            $r = array(
             'discount_code' => $coupon_code
            );
            die(json_encode($r));
          }

        }

        /*************************************
        Admin page load
        -Set default options if missing
        -Show admin page
        *************************************/
        public function show_admin_page(){
          //Save default local settings
          if (!get_option("gr_private_key")){
            update_option("gr_type", "percent");
            update_option("gr_amount", "15");
            update_option("gr_show_cart_button", "0");
            update_option("gr_show_cart", "0");
            update_option("gr_show_checkout", "0");
          }
          include 'gr_admin.php';
        }

}

/*************************************
Loads the plugin
*************************************/
$GLOBALS['idsgr'] = new idsgr();

?>
