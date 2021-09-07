<?php
/*
Plugin Name: Automation.app Referrer Tracking
Plugin URI: https://automation.app/
description: Automation.app Referrer Tracker adds the referrer (website, google, Facebook etc.) to the order meta data without using cookies. So that you can evaluate marketing efforts and create segmentation for groups of users with no added data privacy complexity.
Version: 1.0.0
Author: Automation.app
Author URI: https://automation.app/
License: GPL2
*/
if ( ! defined( 'ABSPATH' ) ) exit;
class wooDocReferrer{
	function __construct() {
		session_start();
		add_action('wp_head',array($this,'WDR_load_js_script'));
		add_action("wp_ajax_WDR_js_write_session_call",array($this,'WDR_js_write_session_call'));
		add_action("wp_ajax_nopriv_WDR_js_write_session_call",array($this,'WDR_js_write_session_call'));
		add_action('woocommerce_new_order',array($this,'WDR_after_order_placed'),1,1);
    }
	
	public function WDR_load_js_script()
	{
		$session=$this->WDR_get_referrer_session();
		$as=(isset($session['url']))?$session['url']:'not--set';
		$ms=$_SERVER['SERVER_NAME'];
		echo '<script type="text/javascript">
					var $WDR = document.referrer;
					var $WDRC = "'.$as.'";
					
					if((!$WDR.includes("'.$ms.'") && $WDR!="") || $WDRC=="not--set")
					{
						if($WDR.includes("'.$ms.'")) $WDR="";
						
						WDR_js_write_session({"url":$WDR,"time":"'.time().'"});
					}
					
					function WDR_js_write_session($text) {
						jQuery.ajax({
						 type : "post",
						 dataType : "json",
						 url : "'.admin_url( 'admin-ajax.php' ).'",
						 data:{action: "WDR_js_write_session_call", sess_data : JSON.stringify($text)},
						 success: function(response){console.log("Doc referrer added.");}
					  });
				}					
			 </script>';
	}
	
	public function WDR_js_write_session_call()
	{
		session_start();
		$_SESSION['WDR_get_referrer']=$_POST['sess_data'];
		echo '{"status":"1"}';
		exit;		
	}
		
	public function WDR_get_referrer_session()
	{
		session_start();
		return (!empty($_SESSION['WDR_get_referrer']))?json_decode(stripslashes($_SESSION['WDR_get_referrer']),true):[];
	}
	
	public function WDR_after_order_placed($order_id)
    {
		$session=$this->WDR_get_referrer_session();
		if(!empty($session))
		{
			update_post_meta($order_id,'doc_referrer',(!empty($session['url']))?$session['url']:'Direct');
			update_post_meta($order_id,'doc_referrer_time',date("Y-m-d H:i:s",$session['time']));
			$this->WDR_destroy_session();
		}
	}
		
	public function WDR_destroy_session()
	{
		session_start();
		unset($_SESSION['WDR_get_referrer']); 
	}
}
new wooDocReferrer();
?>