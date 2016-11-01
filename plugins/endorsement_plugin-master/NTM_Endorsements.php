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
 
 global $endorsements, $ntmadmin, $ntm_mail, $ntm_front;
 $endorsements = new Endorsements();
 $ntm_mail = new NTM_mail_template();
 $ntm_front = new NTM_Frontend();

 Class Endorsements
 {
	function Endorsements()
	{
		global $ntm_front;

		register_activation_hook(__FILE__, array( &$this, 'Endorsement_install'));
		//register_uninstall_hook(__FILE__, array( &$this, 'Endorsement_uninstall'));
		
		add_shortcode('ENDORSEMENT_FRONT_END', array( &$this, 'Endorsement_frontend'));
		add_shortcode('ENDORSER_REDEEM_REQUESTS', array( &$this, 'Endorsement_redeem_requests'));
		add_shortcode('ENDORSER_REDEEM_POINTS', array( &$this, 'Endorsement_redeem_points'));
		add_shortcode('ENDORSER_POINTS_TRANSACTION', array( &$this, 'Endorsement_points_transaction'));

		add_action( 'admin_enqueue_scripts', array( &$this, 'Endorsement_load_js_and_css' ));
		
		add_role( 'endorser', 'Endorser');
		add_role( 'agents', 'Agents', array( 'read' => true, 'level_0' => true ) );
		
		add_action( 'wp_ajax_get_endorsement', array( &$this, 'get_endorsement'), 100 );
		add_action( 'widgets_init', array( &$this, 'register_foo_widget') );
		$ntmadmin = new Endorsements_admin();
		
		if(isset($_GET['ref']))
			setcookie("endorsement_track_link", $_GET['ref'], time() + (86400 * 365), "/");
		if(isset($_COOKIE['endorsement_track_link']) && !isset($_COOKIE['endorsement_tracked']))
			add_action( 'wp_footer', array( &$this, 'affiliate_script'), 100 );
		
		$this->create_tabes();

		add_action( 'init', array( &$this, 'autologin') );
		add_filter( 'login_redirect', array( &$this, 'my_login_redirect'), 10, 3 );

		add_action( 'wp_ajax_social_share', array( &$this, 'social_share'), 100 );
		add_action( 'wp_ajax_nopriv_social_share', array( &$this, 'social_share'), 100 );
		add_action( 'wp_ajax_check_social_share', array( &$this, 'check_social_share'), 100 );
		add_action( 'wp_ajax_nopriv_check_social_share', array( &$this, 'check_social_share'), 100 );
	}
	
	function check_social_share()
	{
		$fb_id = get_user_meta($_GET['user_id'], 'fb_id', true);
		$fb_id = is_array($fb_id) ? $fb_id : array();

		if(!in_array($_GET['id'], $fb_id))
			echo 1;

		die(0);
	}

	function social_share()
	{
		$fb_id = get_user_meta($_GET['user_id'], 'fb_id', true);
		$fb_id = is_array($fb_id) ? $fb_id : array();
		$fb_id[] = $_GET['id'];
		update_user_meta($_GET['user_id'], 'fb_id', $fb_id);

		$points = 50;
		$type = 'FB share';

		$new_balance = $this->get_endorser_points($_GET['user_id']) + $points;
		$data = array('points' => $points, 'credit' => 1, 'endorser_id' => $_GET['user_id'], 'new_balance' => $new_balance, 'transaction_on' => date("Y-m-d H:i:s"), 'type' => $type);
		$this->add_points($data);

		die(0);
	}

	function my_login_redirect( $redirect_to, $request, $user ) {
		//is there a user to check?
		
		if ( isset( $user->roles ) && is_array( $user->roles ) ) {
			//check for admins
			if ( in_array( 'endorser', $user->roles ) ) {
				return get_permalink(get_option('ENDORSEMENT_FRONT_END'));
			} else {
				return $redirect_to;
			}
		} else {
			return $redirect_to;
		}
	}

	function autologin()
	{
		if(isset($_GET['autologin']) && !is_user_logged_in())
			NTM_Frontend::check_login();
		elseif(isset($_POST['from_widget']) && isset($_POST['send_invitation']))
			NTM_Frontend::frontend_action();

	}

	function register_foo_widget() {
    	register_widget( 'CloudSponge_Widget' );
	}

	function add_points($data)
	{
		global $wpdb;

		$wpdb->insert($wpdb->prefix . "points_transaction", $data);
	}

	function get_endorser_points($endorser_id)
	{
		global $wpdb;

		$result = $wpdb->get_row("select * from ".$wpdb->prefix . "points_transaction where endorser_id=".$endorser_id." order by id desc");

		return isset($result->new_balance) ? $result->new_balance : 0;
	}

	function get_endorser_transactions($endorser_id)
	{
		global $wpdb;

		$result = $wpdb->get_results("select * from ".$wpdb->prefix . "points_transaction order by id desc");

		return $result;
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

				$points = 125;
				$type = 'Successfull conversion from email invitation';

				$new_balance = $endorsements->get_endorser_points($track_link[1]) + $points;
				$data = array('points' => $points, 'credit' => 1, 'endorser_id' => $track_link[1], 'new_balance' => $new_balance, 'transaction_on' => date("Y-m-d H:i:s"), 'type' => $type);
			}
		}
		else
		{
			update_user_meta($track_link[0], "tracked_".$track_link[1]."_invitation", (get_user_meta($track_link[1], "tracked_".$track_link[1]."_invitation", true) + 1));
			update_user_meta($track_link[0], "tracked_".$track_link[1]."_counter", (get_user_meta($track_link[1], "tracked_".$track_link[1]."_counter", true) + 1));
			setcookie("endorsement_tracked", true, time() + (86400 * 365), "/");

			$points = 125;
			$type = 'Successfull conversion from '.$track_link[1];

			$new_balance = $endorsements->get_endorser_points($track_link[0]) + $points;
			$data = array('points' => $points, 'credit' => 1, 'endorser_id' => $track_link[0], 'new_balance' => $new_balance, 'transaction_on' => date("Y-m-d H:i:s"), 'type' => $type);
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
			   share_from tinytext NOT NULL,
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

		$mailtemplates = $wpdb->prefix . "points_transaction";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $mailtemplates .'"') != $mailtemplates){
			$sql_one = "CREATE TABLE " . $mailtemplates . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   transaction_on datetime NOT NULL,
			   points int(11),
			   endorser_id int(11),
			   blog_id int(11),
			   credit int(1),
			   debit int(1),
			   new_balance int(11),
			   type tinytext NOT NULL,
			  PRIMARY KEY  (id) ) ENGINE=InnoDB";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_one);
		}

		$mailtemplates = $wpdb->prefix . "points_request";
		
		if($wpdb->get_var('SHOW TABLES LIKE "' . $mailtemplates .'"') != $mailtemplates){
			$sql_one = "CREATE TABLE " . $mailtemplates . "(
			  id int(11) NOT NULL AUTO_INCREMENT,
			   request_on datetime NOT NULL,
			   points int(11),
			   endorser_id int(11),
			   status int(11),
			   notes text,
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
		global $ntm_front;

		return $ntm_front->frontend();
	}
	
	function Endorsement_redeem_points()
	{
		global $ntm_front;

		return $ntm_front->redeem_points();
	}

	function Endorsement_points_transaction()
	{
		global $ntm_front;

		return $ntm_front->points_transaction();
	}

	function Endorsement_redeem_requests()
	{
		global $ntm_front;

		return $ntm_front->redeem_requests();
	}

	function Endorsement_load_js_and_css()
	{
		
	}
	
 }