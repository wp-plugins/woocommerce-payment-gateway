<?php
/**
 * Plugin Name: WooCommerce Payment Gateway - Inspire 
 * Plugin URI: http://www.inspirecommerce.com/woocommerce/
 * Description: Accept all major credit cards directly on your WooCommerce site in a seamless and secure checkout environment with Inspire Commerce.
 * Version: 1.6.0
 * Author: innerfire
 * Author URI: http://www.inspirecommerce.com/
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package WordPress
 * @author innerfire
 * @since 1.0.0
*/
 
add_action( 'plugins_loaded', 'woocommerce_inspire_commerce_init', 0 );

function woocommerce_inspire_commerce_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  };
  
  DEFINE ('PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );

	/*
	 * Inspire Commerce Gateway Class
	 */
		class WC_Inspire extends WC_Payment_Gateway {
	
			function __construct() {
	
	      $this->id			    = 'inspire';
	      $this->has_fields = true;
	      $this->supports   = array( 'products', 'subscriptions', 'subscription_cancellation' );
      
				// Load the form fields
				$this->init_form_fields();
			
				// Load the settings.
				$this->init_settings();
		
				// Get setting values
				foreach ( $this->settings as $key => $val ) $this->$key = $val;
			
	      $this->icon = PLUGIN_DIR . 'images/cards.png';

				// Hooks
	      		add_action( 'woocommerce_receipt_inspire',                  array( $this, 'receipt_page' ) );
				add_action( 'admin_notices',                                array( $this, 'inspire_commerce_ssl_check' ) );
				add_action( 'woocommerce_update_options_payment_gateways',  array( $this, 'process_admin_options' ) );
				add_action( 'scheduled_subscription_payment_inspire',       array( $this, 'process_scheduled_subscription_payment'), 0, 3 );
			
		  }

	/*
     * Check if SSL is enabled and notify the user.
	 */
		function inspire_commerce_ssl_check() {
			if ( get_option( 'woocommerce_force_ssl_checkout' ) == 'no' && $this->enabled == 'yes' ) {
	        echo '<div class="error"><p>' . sprintf( __('Inspire Commerce is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes' ), admin_url( 'admin.php?page=woocommerce' ) ) . '</p></div>';
	      	}
		}
		
	/*
     * Initialize Gateway Settings Form Fields.
     */
	    function init_form_fields() {
  
	      $this->form_fields = array(
	      'enabled'     => array(
	        'title'       => __( 'Enable/Disable', 'woothemes' ), 
	        'label'       => __( 'Enable Inspire Commerce', 'woothemes' ), 
	        'type'        => 'checkbox', 
	        'description' => '', 
	        'default'     => 'no'
	        ), 
	      'title'       => array(
	        'title'       => __( 'Title', 'woothemes' ), 
	        'type'        => 'text', 
	        'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
	        'default'     => __( 'Credit Card (Inspire Commerce)', 'woothemes' )
	        ), 
	      'description' => array(
	        'title'       => __( 'Description', 'woothemes' ), 
	        'type'        => 'textarea', 
	        'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
	        'default'     => 'Pay with your credit card via Inspire Commerce.'
	        ),  
	      'username'    => array(
	        'title'       => __( 'Username', 'woothemes' ), 
	        'type'        => 'text', 
	        'description' => __( 'This is the API username generated within the Inspire Commerce gateway.', 'woothemes' ), 
	        'default'     => ''
	        ), 
	      'password'    => array(
	        'title'       => __( 'Password', 'woothemes' ), 
	        'type'        => 'text', 
	        'description' => __( 'This is the API user password generated within the Inspire Commerce gateway.', 'woothemes' ), 
	        'default'     => ''
	        ),
	      'salemethod'  => array(
	        'title'       => __( 'Sale Method', 'woothemes' ), 
	        'type'        => 'select', 
	        'description' => __( 'Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'woothemes' ), 
	        'options'     => array(
	          'sale' => 'Authorize &amp; Capture',
	          'auth' => 'Authorize Only'
	          ),
	        'default'     => 'Authorize &amp; Capture'
	        ),
	      'cardtypes'   => array(
	        'title'       => __( 'Accepted Cards', 'woothemes' ), 
	        'type'        => 'multiselect', 
	        'description' => __( 'Select which card types to accept.', 'woothemes' ), 
	        'default'     => '',
	        'options'     => array(
	          'MasterCard'	      => 'MasterCard', 
	          'Visa'			        => 'Visa',
	          'Discover'		      => 'Discover',
	          'American Express'  => 'American Express'
	          ),
	        ),		
	      'cvv'         => array(
	        'title'       => __( 'CVV', 'woothemes' ), 
	        'type'        => 'checkbox', 
	        'label'       => __( 'Require customer to enter credit card CVV code', 'woothemes' ), 
	        'description' => __( '', 'woothemes' ), 
	        'default'     => 'yes'
	        ),
	      'saveinfo'    => array(
	        'title'       => 'Billing Information Storage',
	        'type'        => 'checkbox',
	        'label'       => 'Allow customers to save billing information for future use (requires Inspire Commerce Customer Vault)',
	        'default'     => 'no'
	        ),
	      'gatewayurl'  => array(
	        'title'       => __( 'Gateway URL', 'woothemes' ), 
	        'type'        => 'hidden', 
	        'description' => __( 'URL for Inspire Commerce gateway processor.', 'woothemes' ), 
	        'default'     => 'https://secure.inspiregateway.net/api/transact.php'
	        ),
	      'queryurl'    => array(
	        'title'       => __( 'Query URL', 'woothemes' ), 
	        'type'        => 'hidden', 
	        'description' => __( 'URL for Inspire Commerce data queries.', 'woothemes' ), 
	        'default'     => 'https://secure.inspiregateway.net/api/query.php'
	        )
			);
		  }
		
		
		/*
		 * UI - Admin Panel Options
		 */
			function admin_options() { ?>
				<h3><?php _e( 'Inspire Commerce','woothemes' ); ?></h3>	    	
			    <p><?php _e( 'Woo has been using Inspire Commerce on WooThemes.com for all credit card processing, and are so happy with the gateway, that they are recommending it to all US based Woo uses.  <a href="http://www.inspirecommerce.com/woocommerce/">Click here to get paid like the pros</a>.<br /><br />Inspire Commerce works by adding credit card fields on the checkout page, and then sending the details to Inspire Commerce for verification.', 'woothemes' ); ?></p>
			    <table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table>
				<?php
		    	}
	    
	    
    	/*
		 * UI - Payment page fields for Inspire Commerce.
		 */
    		function payment_fields() {      
      			// Description of payment method from settings
      			if ( $this->description ) { ?>
        			<p><?php echo $this->description; ?></p>
				<?php } ?>
				

				<fieldset  style="padding-left: 40px;">
					<?php
						if ( $this->customer_exists( $this->get_user_login() ) ) { 
					?>
   					<fieldset>
						<input type="radio" name="inspire_use_stored_payment_info" id="inspire_use_stored_payment_info_yes" value="yes" checked="checked" onclick="document.getElementById('inspire_new_info').style.display='none'; document.getElementById('inspire_stored_info').style.display='block'"; />

		           		<label for="inspire_use_stored_payment_info_yes"><?php _e( 'Use a stored credit card', 'woocommerce' ) ?></label>
						<br />           
						<div id="inspire_stored_info" style="padding: 10px 0 0 40px;">
							<?php
								global $customer_details;
								$i = 0;
								foreach ( $customer_details->billing as $key => $val ) { 
							?>
							<!-- Begin stored card loop for each stored card... need guidance best practices here for design -->
			                <p>
								<input type="radio" name="inspire_payment_method" id="<?php echo $i; ?>" value="<?php echo $i; ?>" <?php if ( $customer_details->billing->$i->priority == 1 ) { ?> checked="checked" <?php } ?> /> &nbsp;<?php echo $customer_details->billing->$i->cc_number; ?> (<?php
			                      				$exp = $customer_details->billing->$i->cc_exp;
			                      				echo substr( $exp, 0, 2 ) . '/' . substr( $exp, -2 ); 
								?>)<br />
			                </p>

						<?php
		                $i ++;
		              	} ?>
					</fieldset>
					
					     
					<fieldset>
            			<p>
			              <input type="radio" name="inspire_use_stored_payment_info" id="inspire_use_stored_payment_info_no" value="no"
			              onclick="document.getElementById('inspire_stored_info').style.display='none'; document.getElementById('inspire_new_info').style.display='block'"; />
			              <label for="inspire_use_stored_payment_info_no"><?php _e( 'Use a new payment method', 'woocommerce' ) ?></label>
		            	</p>
            			<div id="inspire_new_info" style="display:none">
							<?php } else { ?>
								<fieldset>
								<!-- Show input boxes for new data -->          
								<div id="inspire_new_info">
								<?php } ?>
           
					            <!-- Credit card number -->
					            <p class="form-row form-row-first">
						              <label for="ccnum"><?php echo __( 'Credit Card number', 'woocommerce' ) ?> <span class="required">*</span></label>
						              <input type="text" class="input-text" id="ccnum" name="ccnum" />
					            </p>

					            <!-- Credit card type -->
					            <p class="form-row form-row-last">
						            <label for="cardtype"><?php echo __( 'Card type', 'woocommerce' ) ?> <span class="required">*</span></label>
						            <select name="cardtype" id="cardtype" class="woocommerce-select">
										<?php  foreach( $this->cardtypes as $type ) { ?>
						                  <option value="<?php echo $type ?>"><?php _e( $type, 'woocommerce' ); ?></option>
										<?php } ?>
						             </select>
					            </p>
         
					            <div class="clear"></div>

					            <!-- Credit card expiration -->
					            <p class="form-row form-row-first">
					              <label for="cc-expire-month"><?php echo __( 'Expiration date', 'woocommerce') ?> <span class="required">*</span></label>
					              <select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
					                <option value=""><?php _e( 'Month', 'woocommerce' ) ?></option><?php
					                $months = array();
					                for ( $i = 1; $i <= 12; $i ++ ) {
					                  $timestamp = mktime( 0, 0, 0, $i, 1 );
					                  $months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
					                }
					                foreach ( $months as $num => $name ) {
					                  printf( '<option value="%u">%s</option>', $num, $name );
					                } ?>
					              </select>
					              <select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
					                <option value=""><?php _e( 'Year', 'woocommerce' ) ?></option><?php
					                $years = array();
					                for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
					                  printf( '<option value="20%u">20%u</option>', $i, $i );
					                } ?>
					              </select>
					            </p><?php
     
					            // Credit card security code
					            if ( $this->cvv == 'yes' ) { ?>
					              <p class="form-row form-row-last">
					                <label for="cvv"><?php _e( 'Card security code', 'woocommerce' ) ?> <span class="required">*</span></label>
					                <input type="text" class="input-text" id="cvv" name="cvv" maxlength="4" style="width:45px" />
					                <span class="help"><?php _e( '3 or 4 digits usually found on the signature strip.', 'woocommerce' ) ?></span>
					              </p><?php
					            }
           
					            // Option to store credit card data
					            if ( $this->saveinfo == 'yes' && ! ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) ) { ?>
					              <p class="form-row form-row-last">
					                <label for="saveinfo"><?php _e( 'Save this billing method?', 'woocommerce' ) ?></label>
					                <input type="checkbox" class="input-checkbox" id="saveinfo" name="saveinfo" />
					                <span class="help"><?php _e( 'Select to store your billing information for future use.', 'woocommerce' ) ?></span>
					              </p><?php
					            } ?>
							</fieldset>         
        				</div>
					</fieldset>
<?php    
    }
		
		/*
		 * Process the payment and return the result.
		 */
		function process_payment( $order_id ) {
			global $woocommerce;

			$order = &new WC_Order( $order_id );
			
      $user = new WP_User( $order->user_id );
			
			// Convert CC expiration date from (M)M-YYYY to MMYY
			$expmonth = $this->get_post( 'expmonth' );
			if ( $expmonth < 10 ) {
        $expmonth = '0' . $expmonth;
      }
			if ( $this->get_post( 'expyear' ) != null ) {
        $expyear = substr( $this->get_post( 'expyear' ), -2 );
      }
      
      // Create server request using stored or new payment details
			if ( $this->get_post( 'inspire_use_stored_payment_info' ) == 'yes' ) {
        
        // Get customer data from the Customer Vault
				$this->get_customer_details( $user->user_login );
				global $customer_details;
				
				// Workaround for getting billing id numeric key from SimpleXML response
				$billing_id = substr( substr( $customer_details->billing[ (int) $this->get_post( 'inspire_payment_method' ) ]['id']->asXML(), 5 ), 0, strlen( $billing_id ) - 1 );
        
        // Short request, use stored billing details
        $base_request = array (
          'username' 	        => $this->username,
          'password' 	        => $this->password,
          'amount' 		        => $order->order_total,
          'customer_vault_id' => $user->user_login,
          'billing_id'        => $billing_id,
          );
				
      } else {
        
        // Full request, new customer or new information
        $base_request = array (
          'username' 	=> $this->username,
          'password' 	=> $this->password,
          'ccnumber' 	=> $this->get_post( 'ccnum' ),
          'cvv' 		  => $this->get_post( 'cvv' ),
          'ccexp' 		=> $expmonth . $expyear,
          'firstname' => $order->billing_first_name,
          'lastname' 	=> $order->billing_last_name,
          'address1' 	=> $order->billing_address_1,
          'city' 			=> $order->billing_city,
          'state' 		=> $order->billing_state,
          'zip' 			=> $order->billing_postcode,
          'country' 	=> $order->billing_country,
          'phone' 		=> $order->billing_phone,
          );
        
      }
      
      // Add transaction-specific details to the request
      $transaction_details = array (
        'amount' 		=> $order->order_total,
        'type' 			=> $this->salemethod,
        'payment' 	=> 'creditcard',
        'orderid' 	=> $order->id,
        'ipaddress' => $_SERVER['REMOTE_ADDR'],
        );
			
      // Send request and get response from server
      $response = $this->post_and_get_response( array_merge( $base_request, $transaction_details ) );

      if ( $response['response'] == 1 ) {
        // Success
        $order->add_order_note( __( 'Inspire Commerce payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
        $order->payment_complete();
        
        // Subscriptions don't appear in the cart, so empty only if we bought regular items
        if ( ! $this->is_subscription( $order ) ) {
          $woocommerce->cart->empty_cart();
          }
          
        // If "save billing data" box is checked or order is a subscription, also request storage of customer payment information.
        // Note that WooCommerce currently allows only one subscription per order.
        
        if ( $this->get_post( 'inspire_use_stored_payment_info' ) == null || $this->get_post( 'inspire_use_stored_payment_info' ) == 'no' ) {
        
          if ( $this->get_post( 'saveinfo' ) || $this->is_subscription( $order ) ) {
          
            if( $this->get_post( 'createaccount' ) ) $customer_id = $this->get_post( 'account_username' );
            else $customer_id = $this->get_user_login();
            
            // Check if customer is already in the system
            if( $this->customer_exists( $customer_id ) ) {
              // Add a new billing method to an existing customer
              $vault_details = array (
                'customer_vault'    => 'add_billing',
                'customer_vault_id' => $customer_id,
                'priority'          => 1,
                );
            } else {
              // Add a new customer
              $vault_details = array (
                'customer_vault'    => 'add_customer',
                'customer_vault_id' => $customer_id,
                );
            }
            
            // Set 'recurring' flag for subscriptions
            if( $this->is_subscription( $order ) ) {
              $vault_details['billing_method'] = 'recurring';
            }
            // Store data
            $response = $this->post_and_get_response( array_merge( $base_request, $vault_details ) );
            
            // Notify of any errors
            if( $response['response'] !=  1 ) {
              $order->add_order_note( __( 'Inspire Commerce customer data storage failed. Error: ', 'woocommerce' ) . $response['responsetext'] );
              $woocommerce->add_error( __( 'Your order is complete. However, your payment data could not be stored. If this order is a subscription, your default payment method will be used to pay for future subscription periods.', 'woocommerce' ) );
              die;
            }
            
            // Get billing ID for subscriptions
            if( $this->is_subscription( $order ) ) {
              $this->get_customer_details( $user->user_login );
              global $customer_details;
              $billing_id = substr( substr( $customer_details->billing[ 0 ]['id']->asXML(), 5 ), 0, strlen( $billing_id ) - 1 );
            }
          
          }
          
        }
        
        // Store billing ID for future subscription payments
        if( $this->is_subscription( $order ) ) {
          add_post_meta( $order->id, 'billing_id', $billing_id );
          $order->add_order_note( __( 'Inspire Commerce billing method ID: ', 'woocommerce' ) . $billing_id );
        }
        
        // Return thank you redirect
        return array (
          'result'   => 'success',
          'redirect' => add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, stripslashes( get_permalink( get_option( 'woocommerce_thanks_page_id') ) ) ) ),
        );

      } else if ( $response['response'] == 2 ) {
        // Decline
        $order->add_order_note( __( 'Inspire Commerce payment failed. Payment declined.', 'woocommerce' ) );
        $woocommerce->add_error( __( 'Sorry, the transaction was declined.', 'woocommerce' ) );
      
      } else if ( $response['response'] == 3 ) {
        // Other transaction error
        $order->add_order_note( __( 'Inspire Commerce payment failed. Error: ', 'woocommerce' ) . $response['responsetext'] );
        $woocommerce->add_error( __( 'Sorry, there was an error: ', 'woocommerce' ) . $response['responsetext'] );
       
      } else {
        // No response or unexpected response
        $order->add_order_note( __( "Inspire Commerce payment failed. Couldn't connect to gateway server.", 'woocommerce' ) );
        $woocommerce->add_error( __( 'No response from payment gateway server. Try again later or contact the site administrator.', 'woocommerce' ) );
      
      }
	
		}
		
		/*
		 * Process a payment for an ongoing subscription.
		 */
    function process_scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
      
      $user = new WP_User( $order->user_id );
      
      $inspire_request = array (
				'username' 		      => $this->username,
				'password' 	      	=> $this->password, 
				'amount' 		      	=> $amount_to_charge,
        'type' 			        => $this->salemethod,
				'billing_method'    => 'recurring',
				'customer_vault_id' => $user->user_login,
        'billing_id'        => get_post_meta( $order->id, 'billing_id', true ),
        );
      
      $response = $this->post_and_get_response( $inspire_request );
      
//    $order->add_order_note( get_post_meta( $order->id, 'billing_id', true ) );
      
      if ( $response['response'] == 1 ) {
        // Success
        $order->add_order_note( __( 'Inspire Commerce scheduled subscription payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
        WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
        
			} else if ( $response['response'] == 2 ) {
        // Decline
        $order->add_order_note( __( 'Inspire Commerce scheduled subscription payment failed. Payment declined.', 'woocommerce') );
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
      
      } else if ( $response['response'] == 3 ) {
        // Other transaction error
        $order->add_order_note( __( 'Inspire Commerce scheduled subscription payment failed. Error: ', 'woocommerce') . $response['responsetext'] );
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
       
      } else {
        // No response or unexpected response
        $order->add_order_note( __('Inspire Commerce scheduled subscription payment failed. Couldn\'t connect to gateway server.', 'woocommerce') );
      
      }
     
    }
    
    /*
     * Access the Customer Vault and return stored records
     */
    function get_customer_details( $customer_id ) {
      
      if( $customer_id == null ) return null;
      
      global $woocommerce;
    
      $query = array (
        'username' 		      => $this->username,
        'password' 	      	=> $this->password,
        'report_type'       => 'customer_vault',
        'customer_vault_id' => $customer_id,
        'ver'               => '2',
        );
      
      $content = wp_remote_post( $this->queryurl, array(
          'body'  => $query,
         )
      );
      
      // Check for empty response, which means customer does not exist
      if ( trim( strip_tags( $content['body'] ) ) == '' ) return null;
      
      global $customer_details;
      
      $customer_details = simplexml_load_string( $content['body'] )->customer_vault->customer;
      
      return $customer_details;
      
    }
    
    function customer_exists( $customer_id ) {
      return $this->get_customer_details( $customer_id ) != null;
      }
    
    /*
     * Check payment details for valid format
     */
		function validate_fields() {
		
      if ( $this->get_post( 'inspire_use_stored_payment_info' ) == 'yes' ) return true;
      
			global $woocommerce;
			
			// Check for saving payment info without having or creating an account
			if( $this->get_post( 'saveinfo' )  && ! is_user_logged_in() && ! $this->get_post( 'createaccount' ) ) {
        $woocommerce->add_error( 'Sorry, you need to create an account in order for us to save your payment information.' );
        return false;
      }

			$cardType            = $this->get_post( 'cardtype' );
			$cardNumber          = $this->get_post( 'ccnum' );
			$cardCSC             = $this->get_post( 'cvv' );
			$cardExpirationMonth = $this->get_post( 'expmonth' );
			$cardExpirationYear  = $this->get_post( 'expyear' );
	
			// Check card number
			if ( empty( $cardNumber ) || ! ctype_digit( $cardNumber ) ) {
				$woocommerce->add_error( __( 'Card number is invalid', 'woocommerce' ) );
				return false;
			}
		
			if ( $this->cvv == 'yes' ){
				// Check security code
				if ( ! ctype_digit( $cardCSC ) ) {
					$woocommerce->add_error( __( 'Card security code is invalid (only digits are allowed).', 'woocommerce' ) );
					return false;
				}
				if ( ( strlen( $cardCSC ) != 3 && in_array( $cardType, array( 'Visa', 'MasterCard', 'Discover' ) ) ) || ( strlen( $cardCSC ) != 4 && $cardType == 'American Express' ) ) {
					$woocommerce->add_error( __( 'Card security code is invalid (wrong length).', 'woocommerce' ) );
					return false;
				}
			}
	
			// Check expiration data
			$currentYear = date( 'Y' );
			
			if ( ! ctype_digit( $cardExpirationMonth ) || ! ctype_digit( $cardExpirationYear ) ||
				 $cardExpirationMonth > 12 ||
				 $cardExpirationMonth < 1 ||
				 $cardExpirationYear < $currentYear ||
				 $cardExpirationYear > $currentYear + 20
			) {
				$woocommerce->add_error( __( 'Card expiration date is invalid', 'woocommerce' ) );
				return false;
			}
			
			// Strip spaces and dashes
			$cardNumber = str_replace( array( ' ', '-' ), '', $cardNumber );
	
			return true;
			
		}

		function receipt_page( $order ) {
			echo '<p>' . __( 'Thank you for your order.', 'woocommerce' ) . '</p>';
		}
		
		/*
     * Send the payment data to the gateway server and return the response.
     */
    private function post_and_get_response( $request ) {
      global $woocommerce;
      
      // Encode request
      $post = http_build_query( $request, '', '&' );
      
			// Send request
      $content = wp_remote_post( $this->gatewayurl, array(
          'body'  => $post,
         )
      );
      
      // Quit if it didn't work
      if ( is_wp_error( $content ) ) {
        $woocommerce->add_error( __( 'Problem connecting to server at ', 'woocommerce' ) . $this->gatewayurl . ' ( ' . $content->get_error_message() . ' )' );
        return null;
      }
      
      // Convert response string to array
      $vars = explode( '&', $content['body'] );
      foreach ( $vars as $key => $val ) {
        $var = explode( '=', $val );
        $data[ $var[0] ] = $var[1];
      }
      
      // Return response array
      return $data;
      
    }
    
    /*
     * Get the current user's login name
     */
    private function get_user_login() {
      global $user_login;
      get_currentuserinfo();
      return $user_login;
		}
		
		/*
		 * Get post data if set
		 */
		private function get_post( $name ) {
			if ( isset( $_POST[ $name ] ) ) {
				return $_POST[ $name ];
			}
			return null;
		}
		
		/*
     * Check whether an order is a subscription
     */
		private function is_subscription( $order ) {
      return class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order );
		}

	}

	/*
	 * Add the gateway to woocommerce
	 */
	function add_inspire_commerce_gateway( $methods ) {
		$methods[] = 'WC_Inspire';
		return $methods;
	}
	
	add_filter( 'woocommerce_payment_gateways', 'add_inspire_commerce_gateway' );
	
}