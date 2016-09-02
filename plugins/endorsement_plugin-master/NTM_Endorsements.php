<?php
/* Plugin Name: NTM Endorsements
 * Author: Vinodhagan Thangarajan
 * Author URI: http://ultimatedevelopments.com
 * Plugin URI: http://ultimatedevelopments.com
 * Description: Custom endorsements plugin for Neil Thomas
 * Version: 1.0
 */
 
 $dir = pathinfo(__FILE__);
 define('NTM_PLUGIN_URL', plugin_dir_url( __FILE__ ));
 define('NTM_PLUGIN_DIR',$dir['dirname']);
 
 include 'includes.php';
 
 global $endorsements, $ntmadmin, $ntm_mail;
 $endorsements = new Endorsements();
 $ntm_mail = new NTM_mail_template();
 
 Class Endorsements
 {
	function Endorsements()
	{
		register_activation_hook(__FILE__, array( &$this, 'Endorsement_install'));
		//register_uninstall_hook(__FILE__, array( &$this, 'Endorsement_uninstall'));
		
		add_shortcode('ENDORSEMENT_FRONT_END', array( &$this, 'Endorsement_frontend'));
		add_action( 'admin_enqueue_scripts', array( &$this, 'Endorsement_load_js_and_css' ));
		
		add_role( 'endorser', 'Endorser');
		add_role( 'agents', 'Agents', array( 'read' => true, 'level_0' => true ) );
		
		add_action( 'wp_ajax_get_endorsement', array( &$this, 'get_endorsement'), 100 );
		
		$ntmadmin = new Endorsements_admin();
		
		if(isset($_GET['ref']))
			setcookie("endorsement_track_link", $_GET['ref'], time() + (86400 * 365), "/");
		if(isset($_COOKIE['endorsement_track_link']) && !isset($_COOKIE['endorsement_tracked']))
			add_action( 'wp_footer', array( &$this, 'affiliate_script'), 100 );
		
		$this->create_tabes();
	}
	
	function get_endorsement()
	{
		global $wpdb;
		if($_POST['type'] == 'new')
			$get_results = $wpdb->get_results("select * from ".$wpdb->prefix . "endorsements where endorser_id=".$_POST['id']." and track_status is not null and gift_status is null");
		else
			$get_results = $wpdb->get_results("select * from ".$wpdb->prefix . "endorsements where endorser_id=".$_POST['id']." and track_status is not null and gift_status = 1");
		
		$get_results = $get_results ? $get_results : array();
		
		echo json_encode(array(
								"converted_endorsement" => $get_results, 
								"facebook" => get_user_meta($_POST['id'], "tracked_fb_counter", true), 
								"twitter" => get_user_meta($_POST['id'], "tracked_tw_counter", true)
								)
						);
		
		die(0);
	}
	
	function affiliate_script() {
		if(!count($_POST)) return;
		
		global $wpdb;
		
		$track_link = explode("#&$#", base64_decode(base64_decode($_COOKIE['endorsement_track_link'])));
		
		if(count($track_link) == 3)
		{
			$get_results = $wpdb->get_row("select * from ".$wpdb->prefix . "endorsements where id=".$track_link[0]." and tracker_id = '".$track_link[2]."' and track_status is null");
			//print_r("select * from ".$wpdb->prefix . "endorsements where id=".$track_link[0]." and tracker_id = '".$track_link[2]."' and track_status is null");
			if(count($get_results))
			{
				//Track and send gift to endorser
				
				$wpdb->update($wpdb->prefix . "endorsements", array("track_status" => 1, "post_data" => serialize($_POST)), array('id' => $track_link[0]));
				update_user_meta($track_link[1], "tracked_invitation", (get_user_meta($track_link[1], "tracked_invitation", true) + 1));
				update_user_meta($track_link[1], "tracked_counter", (get_user_meta($track_link[1], "tracked_counter", true) + 1));
				setcookie("endorsement_tracked", true, time() + (86400 * 365), "/");
			}
		}
		else
		{
			update_user_meta($track_link[0], "tracked_".$track_link[1]."_invitation", (get_user_meta($track_link[1], "tracked_".$track_link[1]."_invitation", true) + 1));
			update_user_meta($track_link[0], "tracked_".$track_link[1]."_counter", (get_user_meta($track_link[1], "tracked_".$track_link[1]."_counter", true) + 1));
			setcookie("endorsement_tracked", true, time() + (86400 * 365), "/");
		}
	}
	
	function Endorsement_install()
	{
		global $wpdb;
		
		if(!get_option('ENDORSEMENT_FRONT_END'))
		{
			$cpage = array('post_title' => 'Endorsement', 'post_content' => '[ENDORSEMENT_FRONT_END]', 'post_type' => 'page', 'post_status' => 'publish');
			update_option('ENDORSEMENT_FRONT_END', wp_insert_post( $cpage));
		}
		
		$this->create_tabes();
	}
	
	function create_tabes()
	{
		global $wpdb;
		
		$mailtemplates = $wpdb->prefix . "mailtemplates";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $mailtemplates .'"') != $mailtemplates){
			$sql_one = "CREATE TABLE " . $mailtemplates . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   created datetime NOT NULL,
			   name tinytext NOT NULL,
			   subject tinytext NOT NULL,
			   content text NOT NULL,
			   type tinytext NOT NULL,
			   page int(11),
			  PRIMARY KEY  (id) ) ENGINE=InnoDB";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_one);
		}
		
		$endorsements = $wpdb->prefix . "endorsements";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $endorsements .'"') != $endorsements){
			$sql_one = "CREATE TABLE " . $endorsements . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   created datetime NOT NULL,
			   name tinytext NOT NULL,
			   email tinytext NOT NULL,
			   endorser_id int(11),
			   track_status int(1),
			   gift_status int(1),
			   post_data text NOT NULL,
			   tracker_id tinytext NOT NULL,
			   type tinytext NOT NULL,
			  PRIMARY KEY  (id) ) ENGINE=InnoDB";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_one);
		}
		
		$gift = $wpdb->prefix . "gift_transaction";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $gift .'"') != $gift){
			$sql_one = "CREATE TABLE " . $gift . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   created datetime NOT NULL,
			   endorser_id int(11),
			   lead_id int(11),
			   agent_id int(11),
			   gift_id tinytext NOT NULL,
			   amout tinytext NOT NULL,
			   giftbitref_id tinytext NOT NULL,
			   fb_count int(11),
			   twitter_count int(11),
			   gift_sent int(1),
			  PRIMARY KEY  (id) ) ENGINE=InnoDB";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_one);
		}
		
		$giftendorsements = $wpdb->prefix . "giftendorsements";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $giftendorsements .'"') != $giftendorsements){
			$sql_one = "CREATE TABLE " . $giftendorsements . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   created datetime NOT NULL,
			   gift_id tinytext NOT NULL,
			   endorser_id int(11),
			   endorsement_id int(11),
			  PRIMARY KEY  (id) ) ENGINE=InnoDB";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_one);
		}

		global $wpdb;
		
		$mailtemplates = "visa_transaction";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $mailtemplates .'"') != $mailtemplates){
			$sql_one = "CREATE TABLE " . $mailtemplates . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   transaction_on datetime NOT NULL,
			   gift_id tinytext NOT NULL,
			   blog_id text NOT NULL,
			   amout tinytext NOT NULL, 
			   endorser_id text NOT NULL,
			   status tinytext NOT NULL,
			  PRIMARY KEY  (id) ) ENGINE=InnoDB";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_one);
		}
	}
	
	function Endorsement_uninstall()
	{
		
	}
	
	function Endorsement_menu()
	{
		
	}
	
	function Endorsement_frontend()
	{
		$ntm_front_end = new NTM_Frontend();
		
		return $ntm_front_end->frontend();
	}
	
	function Endorsement_load_js_and_css()
	{
		
	}
	
 }