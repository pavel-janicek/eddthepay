<?php
/*
Plugin Name: Easy Digital Downloads - The Pay
Plugin URL: https://cleverstart.cz
Description: Přidá možnost platby přes ThePay
Version: 1.1.26
Author: Pavel Janíček
Author URI: https://cleverstart.cz
*/

require __DIR__ . '/vendor/autoload.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://plugins.cleverstart.cz/?action=get_metadata&slug=eddthepay',
	__FILE__, //Full path to the main plugin file or functions.php.
	'eddthepay'
);

// registers the gateway
function eddthepay_register_gateway( $gateways ) {
	$gateways['eddthepay'] = array( 'admin_label' => 'ThePay', 'checkout_label' => __( 'Online platební karta nebo převod (ihned)', 'thepay' ) );
	return $gateways;
}
add_filter( 'edd_payment_gateways', 'eddthepay_register_gateway' );

// Remove this if you want a credit card form

function edd_eddthepay_cc_form() {
	return;
}
add_action('edd_eddthepay_cc_form', 'edd_eddthepay_cc_form');

function eddthepay_listener_url(){

	return get_home_url(). "?edd-listener=eddthepay";
}

// processes the payment
function eddthepay_process_payment( $purchase_data ) {
  ///Require our config of thepay
	require_once "payConfig.php";
  require_once (plugin_dir_path(__FILE__) . 'component/classes/helpers/TpUrlHelper.php');
	global $edd_options;


		// record the pending payment
		$payment = edd_insert_payment( $purchase_data );
		$edd_payment = new EDD_Payment($payment);

    ///Create an instance of thepay TpPayment class and give it our config
		$thepayobject = new TpPayment(new PayConfig(!edd_is_test_mode(),$edd_options));
    $thepayobject->setValue($purchase_data['price']);
		$thepayobject->setCurrency($edd_payment->currency);
    $thepayobject->setMerchantData($payment);
    $thepayobject->setReturnUrl(eddthepay_listener_url());
    $tpHelper = new TpUrlHelper($thepayobject);
    $location = $tpHelper->getRedirectUrl();
		edd_empty_cart();
    header('Location: ' . $location);
    exit;
}

add_action( 'edd_gateway_eddthepay', 'eddthepay_process_payment' );

//process pingback
function edd_eddthepay_pingback() {
  ///Require TpReturnedPayment
	require_once(plugin_dir_path(__FILE__) . 'component/classes/TpReturnedPayment.php');
	///Require our configuration of thepay
	require_once "payConfig.php";
	global $edd_options;
  $returnedPayment = new TpReturnedPayment(new PayConfig(!edd_is_test_mode(),$edd_options));
	$returnedOrderNumber = $_GET['merchantData'];


  try{
				/// Verify the payment signature
				$returnedPayment->verifySignature();
				if($returnedPayment->getStatus() == TpReturnedPayment::STATUS_OK){
					 edd_update_payment_status( $returnedOrderNumber, 'publish' );
	  				edd_send_to_success_page();
				}elseif ($returnedPayment->getStatus() ==TpReturnedPayment::STATUS_CANCELED ) {
					edd_update_payment_status( $returnedOrderNumber, 'revoked' );
					$location = get_permalink($edd_options['failure_page']);
					header('Location: ' . $location);
				}elseif ($returnedPayment->getStatus() == TpReturnedPayment::STATUS_ERROR) {
					edd_update_payment_status( $returnedOrderNumber, 'abandoned' );
					$location = get_permalink($edd_options['failure_page']);
					header('Location: ' . $location);
				}elseif ($returnedPayment->getStatus() == TpReturnedPayment::STATUS_UNDERPAID) {
					edd_update_payment_status( $returnedOrderNumber, 'abandoned' );
					$location = get_permalink($edd_options['failure_page']);
					header('Location: ' . $location);
				}elseif ($returnedPayment->getStatus() == TpReturnedPayment::STATUS_WAITING) {
					$location = get_home_url();
					header('Location: ' . $location);
				}else{
					$location = get_permalink($edd_options['failure_page']);
				}

			}catch (Exception $e){
				edd_update_payment_status( $returnedOrderNumber, 'abandoned' );
				$location = get_permalink($edd_options['failure_page']);
			} catch(TpMissingParameterException $e){
				edd_update_payment_status( $returnedOrderNumber, 'abandoned' );
				$location = get_permalink($edd_options['failure_page']);
			}
			header('Location: ' . $location);
			exit;
}

add_action( 'edd_eddthepay_pingback', 'edd_eddthepay_pingback' );

// listen for pingback
function edd_listen_for_eddthepay_pingback() {
	global $edd_options;

	// Regular GoPay IPN
	if ( isset( $_GET['edd-listener'] ) && $_GET['edd-listener'] == 'eddthepay' ) {
		do_action( 'edd_eddthepay_pingback' );
	}
}
add_action( 'init', 'edd_listen_for_eddthepay_pingback' );

function eddthepay_add_settings( $settings ) {

	$gopay_settings = array(
		array(
			'id' => 'eddthepay_settings',
			'name' => '<strong>' . __( 'Nastavení ThePay', 'eddthepay' ) . '</strong>',
			'desc' => __( 'Nastavte propojení s bránou ThePay', 'eedthepay' ),
			'type' => 'header'
		),
		array(
			'id' => 'eddthepay_merchantId',
			'name' => '<strong> ' . __( 'ID Obchodníka', 'eddthepay' ) . '</strong>',
			'desc' => __( 'ID Obchodníka:', 'eddthepay' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'eddthepay_accountId',
			'name' => '<strong> ' . __( 'ID Účtu', 'eddthepay' ) . '</strong>',
			'desc' => __( 'ID Účtu:', 'eddthepay' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'eddthepay_pasword',
			'name' => '<strong> ' . __( 'Heslo', 'eddthepay' ) . '</strong>',
			'desc' => __( 'Heslo:', 'eddthepay' ),
			'type' => 'text',
			'size' => 'regular'
		)

	);

	return array_merge( $settings, $gopay_settings );
}
add_filter( 'edd_settings_gateways', 'eddthepay_add_settings' );
