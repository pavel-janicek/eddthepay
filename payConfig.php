<?php

///Require TpMerchantConfig
require_once(plugin_dir_path(__FILE__) . 'component/classes/TpMerchantConfig.php');



///Class with our thepay configuration, it extends TpMerchantConfig
class PayConfig extends TpMerchantConfig {

	///Our id
	public $merchantId;

	///Our account id
	public $accountId;

	///Our password
	public $password;

	///Url of thepay gate
	public $gateUrl;

	public function __construct($isProd,$edd_options){
		if($isProd){
			if(!empty($edd_options['eddthepay_merchantId'])){
        $this->merchantId = $edd_options['eddthepay_merchantId'];
			}
			if(!empty($edd_options['eddthepay_accountId'])){
				$this->accountId = $edd_options['eddthepay_accountId'];
			}
			if(!empty($edd_options['eddthepay_pasword'])){
				$this->password = $edd_options['eddthepay_pasword'];
			}
			$this->gateUrl = 'https://www.thepay.cz/gate/';
		}else{
			 $this->merchantId = 1;
			 $this->accountId = 3;
			 $this->password = 'my$up3rsecr3tp4$$word';
			 $this->gateUrl = "https://www.thepay.cz/demo-gate/";
		}
	}

}
