<?php
Class NTM_Frontend
{
	function frontend()
	{
		error_reporting(0);
		if(isset($_GET['gift']))
		{
			$this->select_vendor(base64_decode(base64_decode($_GET['gift'])));
			return false;
		}
		/*elseif(!$this->check_login())
		{
			echo "Invalid Autologin link!!!";
			return false;
		}*/
		
		global $ntm_mail, $current_user, $wpdb;
	
		$endorser_letter = get_user_meta($current_user->ID, 'endorsement_letter', true);
		if($endorser_letter)
		{
			$res = $wpdb->get_row("select * from ".$wpdb->prefix . "mailtemplates where id=".$endorser_letter);
			$mailtemplate = $res->content;
		}
		else
		{
			$mailtemplate 	 	= 	$ntm_mail->get_invitation_mail ();
			$mailtemplate = $mailtemplate['content'];
		}
		if(isset($_POST['join_endorser']))
		{
			update_user_meta($current_user->ID, 'first_name', $_POST['endorser_firstname']);
			update_user_meta($current_user->ID, 'last_name', $_POST['endorser_lastname']);
			update_user_meta($current_user->ID, 'imcomplete_profile', 0);
		}
		else
			$invitation_status = $this->frontend_action();
	?>
    <link rel="stylesheet" type="text/css" href="<?php _e(NTM_PLUGIN_URL);?>/assets/css/ckeditor.css" media="all" />
    <script type='text/javascript' src='<?php _e(NTM_PLUGIN_URL);?>/assets/js/ckeditor/ckeditor.js'></script>
    <script type="text/javascript" src="http://platform.linkedin.com/in.js">
    	api_key: <?= LI_APP_ID; ?>
	    authorize: true
	</script>
    <script>

      (function(d, s, id) {
	    var js, fjs = d.getElementsByTagName(s)[0];
	    if (d.getElementById(id)) return;
	    js = d.createElement(s); js.id = id;
	    js.src = "//connect.facebook.net/en_US/sdk.js";
	    fjs.parentNode.insertBefore(js, fjs);
	  }(document, 'script', 'facebook-jssdk'));

      window.fbAsyncInit = function() {
	  FB.init({
	    appId      : '<?= FB_APP_ID; ?>',
	    cookie     : true,  // enable cookies to allow the server to access 
	                        // the session
	    xfbml      : true,  // parse social plugins on this page
	    version    : 'v2.5' // use graph api version 2.5
	  });
      
      	function checkLoginState() {
		    FB.getLoginStatus(function(response) {
		     	if (response.status === 'connected') {
		     		FB.api('/me', function(response) {
						jQuery.get('<?php echo site_url();?>/wp-admin/admin-ajax.php?action=check_social_share&user_id=<?= $current_user->ID; ?>&id='+response.id).then(function(res){
							if(res)
							{
								FB.ui(
								{
								  method: 'share',
								  href: '<?php echo get_permalink($pagelink).'?ref='.base64_encode(base64_encode($current_user->ID.'#&$#fb'));?>'
								}, function(response1){
									jQuery.get('<?php echo site_url();?>/wp-admin/admin-ajax.php?action=social_share&user_id=<?= $current_user->ID; ?>&id='+response.id).then(function(res){
									
									});
								});
							}
						});
						
				    });
					
					
			    }
		    });
		}

		var payload = { 
	      "comment": "<?php echo get_permalink($pagelink).'?ref='.base64_encode(base64_encode($current_user->ID.'#&$#li'));?>", 
	      "visibility": { 
	        "code": "anyone"
	      } 
	    };
	    var reqestId;
	    function successhare(data) {
	    	jQuery.get('<?php echo site_url();?>/wp-admin/admin-ajax.php?action=social_share&user_id=<?= $current_user->ID; ?>&id='+reqestId).then(function(res){
									
				});
	    }

		function onSuccess(data) {
	        reqestId = data.id;
			
			jQuery.get('<?php echo site_url();?>/wp-admin/admin-ajax.php?action=check_social_share&user_id=<?= $current_user->ID; ?>&id='+reqestId).then(function(res){
				if(res)
				{
					IN.API.Raw("/people/~/shares?format=json")
					  .method("POST")
					  .body(JSON.stringify(payload))
					  .result(successhare)
					  .error(onError);
				}
			});
	    }

	    // Handle an error response from the API call
	    function onError(error) {
	        console.log(error);
	    }

		function getProfileData() {
			console.log('fgggghf');
	        IN.API.Raw("/people/~").result(onSuccess).error(onError);
			
	    }

		function checkLoginStateLinkedin() {
			IN.UI.Authorize().params({"scope":["r_basicprofile", "r_emailaddress"]}).place();
			IN.Event.on(IN, "auth", getProfileData);
		}

		/* Linked In share*/
		(function(d, s, id){
	        var js, pjs = d.getElementsByTagName(s)[0];
	        if (d.getElementById(id)) {return;}
	        js = d.createElement(s); js.id = id;
	        js.src = "//assets.pinterest.com/sdk/sdk.js";
	        pjs.parentNode.insertBefore(js, pjs);
	    }(document, 'script', 'pinterest-jssdk'));

    	window.pAsyncInit = function() {
	        PDK.init({
	            appId: "<?= PI_APP_ID; ?>", // Change this
	            cookie: true
	        });
	    }
        
		function checkLoginStatePinterest() {
			PDK.login({ scope : 'read_relationships,read_public' }, function(response){
	            if (!response || response.error) {
	              //alert('Error occurred');
	            } else {
	               //console.log(JSON.stringify(response));
	            }
	        //get board info
				var pins = [];
				PDK.request('/v1/me/', function (response) {
				  if (!response || response.error) {
					//alert('Error occurred');
				  } else {
					console.log(JSON.stringify(response));
					//console.log(PDK.getSession().accessToken);
					
					reqestId = response.id;
			
					jQuery.get('<?php echo site_url();?>/wp-admin/admin-ajax.php?action=check_social_share&user_id=<?= $current_user->ID; ?>&id='+reqestId).then(function(res){
						if(res)
						{

						PDK.pin("http://financialinsiders.ca/wp-content/themes/fi-2016/includes/images/financial-insiders-logo.png", "http://financialinsiders.ca/", "http://financialinsiders.ca/", successhare);
						}
					});
				  }
				});
	        });
		}

		/* End Linked In share*/

	  (function(u){
		var d=document,s='script',a=d.createElement(s),m=d.getElementsByTagName(s)[0];
		a.async=1;a.src=u;m.parentNode.insertBefore(a,m);
	  })('//api.cloudsponge.com/widget/<?php echo get_option('cloudsponge');?>.js');
	  window.csPageOptions = { 
				textarea_id: "contact_list" ,
				skipSourceMenu:true, // suppresses the source menu unless linked to directly
			  // delay making the links that launch a popup clickable
			  // until after the widget has initialized completly. a popup window must 
			  // be opened in an onclick handler, so we don't support queueing these actions
			  afterInit:function() {
				var i, links = document.getElementsByClassName('delayed');
				for (i = 0; i < links.length; i++) {
				  // make the links that launch a popup clickable by setting the href property
				  links[i].href = "#";
				}

				// if this is not a mobile browser, we can show and enable the desktop-only links
				if (!cloudsponge.mobile) {
				  links = document.getElementsByClassName('desktop-only');
				  for (i = 0; i < links.length; i++) {
					// show it
					links[i].style.display = "";
					// make it clickable
					links[i].href = "#";
				  }
				}
			  }
		};
	</script>
    <div id="poststuff" class="wrap">
    <?php if($invitation_status){?>
    <div id="message" class="updated"><p>Your invitation sent successfully.</p></div>
    <?php }?>
		
		<?php if(!get_user_meta($current_user->ID, 'imcomplete_profile', true)){?>
		
		<p>Welcome <?php echo get_user_meta( $current_user->ID, 'first_name', true).' '.get_user_meta($current_user->ID, 'last_name', true);?></p>
		<?php $res = $wpdb->get_row("select * from ".$wpdb->prefix . "mailtemplates where id=".(get_user_meta($current_user->ID, 'endorsement_letter', true) ? get_user_meta($current_user->ID, 'endorsement_letter', true) : 0)); ?><br>
		<?php $pagelink = isset($res->page) ? $res->page : get_option('ENDORSEMENT_FRONT_END');?>
		<div class="postbox">
            <div class="inside group">
					<div class="social_share">
						<a onclick="checkLoginState()"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/fbshare.png"/></a>
						<a onclick="checkLoginStateLinkedin()"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/linkedin.png"/></a>
						<a onclick="checkLoginStatePinterest()">Pinterest</a>
						<!-- <a onclick="window.open('http://www.facebook.com/sharer.php?u='+encodeURIComponent('<?php echo get_permalink($pagelink).'?ref='.base64_encode(base64_encode($current_user->ID.'#&$#fb'));?>'),'sharer','toolbar=0,status=0,width=626,height=436');return false;"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/fbshare.png"/></a> -->
						<a onclick="window.open('https://twitter.com/intent/tweet?text=<?php echo get_option('twitter_text');?>&url='+encodeURIComponent('<?php echo get_permalink($pagelink).'?ref='.base64_encode(base64_encode($current_user->ID.'#&$#tw'));?>'),'sharer','toolbar=0,status=0,width=626,height=436');return false;"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/twshare.png"/></a>
					</div>
					<br>
					<div class="social_button">
						<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('linkedin');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/linkedin.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('yahoo');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/yahoo.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('windowslive');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/outlook.com.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('gmail');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/gmail.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('aol');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/aol.png"/></a>
						<a href="#" class="deep-link" onclick="return cloudsponge.launch('plaxo');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/plaxo.png"/></a>
						<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('addressbook');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/apple-desktop.png"/></a>
						<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('outlook');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/outlook-desktop.png"/></a>
					</div>
					<span>Or</span>
					<p>Add Contact directly</p>
					<p>Name: <input type="text" id="contactname">Email: <input type="email" id="contactemail"><button onclick="addcontact(jQuery);">Add</button></p>
				<form name="myform" method="post" >
					<textarea name="contact_list" id="contact_list" rows="5" cols="73"></textarea>
					<br><br>
					<textarea cols="80" id="editor" name="endorse_letter" rows="10"><?php _e($mailtemplate);?></textarea>
					<script>
						CKEDITOR.replace( 'editor' );
						function addcontact($){
							if(!$("#contactemail").val())
								return false;
							
							contact = $("#contactname").val()+' <'+$("#contactemail").val()+'>';
							if($.trim($("#contact_list").val()))
								$("#contact_list").val($("#contact_list").val()+', '+contact);
							else
								$("#contact_list").val(contact);
							
							$("#contactname").val('');
							$("#contactemail").val('');
						}
					</script>
					<br>
					<p class="submit">
					<input name="send_invitation" class="button-primary seeker_btn" value="<?php _e('Invite your friends'); ?>" type="submit" />
					</p>
                </form>
            </div>
        </div>
		<?php }else{?>
			<form method="post">
				<p><label>Firstname</label> : <input type="text" name="endorser_firstname"></p>
				<p><label>Lastname</label> : <input type="text" name="endorser_lastname"></p>
				<input type="submit" name="join_endorser" value="Join">
			</form>
		<?php }?>
    </div>  
	<?php 
	}
	
	function select_vendor($id){
		global $wpdb;
		$result = $wpdb->get_row("select * from ".$wpdb->prefix . "gift_transaction where gift_sent is null and id = ".$id);
		//echo base64_encode(base64_encode(2));
		if(!count($result))
		{
			echo "Already used this gift!!";
			return;
		}
		$option = get_option('giftbit');
		
		$headers = array('Authorization: Bearer '.$option['api']);
		
		$region = get_option('giftbitregion');
		$amount = $result->amout * 100;
		$user_info = get_userdata($result->endorser_id);
		if(isset($_POST['gifts']))
		{
			if($_POST['gifts'] == 'visa')
			{
				$status = true;
				$wpdb->update($wpdb->prefix . "gift_transaction", array('gift_sent' => 1, 'giftbitref_id' => 'visa'), array('id' => $id));
				$wpdb->insert("visa_transaction", array('endorser_id' => $result->endorser_id, 'gift_id' => $id, 'transaction_on' => date("Y-m-d H:i:s"), "blog_id" => get_current_blog_id(), 'amount' => $result->amout));
			}
			else
			{
				$headers = array('Authorization: Bearer ' . $option['api'], 'Accept: application/json', 'Content-Type: application/json');
					$data_string = array(
									 'subject' => 'Endorser Gift',
									 'message' => 'Test message',
									 'contacts' => array(array('firstname' => get_user_meta( $result->endorser_id, 'first_name', true), 'lastname' => get_user_meta( $result->endorser_id, 'first_name', true), 'email' => $user_info->user_email)),
									 'marketplace_gifts' => array(array('id' => $_POST['gifts'], 'price_in_cents' => $amount)),
									 'expiry' => date('Y-m-d', strtotime('+6 months')),
									 'gift_template' => 'XJUPY',
									 'delivery_type' => 'SHORTLINK',
									 'id' => time()
									);
				//echo json_encode($data_string);				
				if(isset($option['sandbox']))
					$ch = curl_init("https://testbedapp.giftbit.com/papi/v1/campaign");
				else	
					$ch = curl_init("https://api.giftbit.com/papi/v1/campaign");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_string));
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$curl_response2 = curl_exec($ch);
				curl_close($ch);
				
				$option = get_option('giftbit');
				$option['amount'] = $option['amount'] - $amount;
				update_option("giftbit", $option);
				
				$status = true;
				
				//echo '<pre>'; print_r(json_decode($curl_response2));
				
				$giftbitref_id = json_decode($curl_response2)->campaign->uuid;
				
				$wpdb->update($wpdb->prefix . "gift_transaction", array('gift_sent' => 1, 'giftbitref_id' => $giftbitref_id), array('id' => $id));
			}

			setcookie("endorsement_gift_sent", 1, time() + (86400 * 365), "/");
		}
		else
		{
			if(isset($option['sandbox']))
				$ch = curl_init("https://testbedapp.giftbit.com/papi/v1/marketplace/?min_price_in_cents=".$amount."&max_price_in_cents=".$amount."&region=".$region);
			else	
				$ch = curl_init("https://api.giftbit.com/papi/v1/marketplace/?min_price_in_cents=".$amount."&max_price_in_cents=".$amount."&region=".$region);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$curl_response3 = curl_exec($ch);
			curl_close($ch);
		}
		?>
		
		<div id="poststuff" class="wrap">
			<?php if(isset($gift_status)){?>
			<div id="message" class="updated"><p>Your invitation sent successfully.</p></div>
			<?php }?>
				<p>Welcome <?php echo get_user_meta( $result->endorser_id, 'first_name', true).' '.get_user_meta($result->endorser_id, 'last_name', true);?></p>
				<p>Your gift amount : <b>$ <?php _e($result->amout);?></b></p>
				<div class="postbox">
					<div class="inside group">
						<form name="myform" method="post" >
							<?php if(count(json_decode($curl_response3)->marketplace_gifts)){?>
							<ul>
								<li>
									<input onchange="document.myform.submit();" id="vendor_visa" value="visa" type="radio" name="gifts">
									<label for="vendor_visa">Visa</label>
								</li>
								<?php foreach(json_decode($curl_response3)->marketplace_gifts as $res){?>
								<li>
									<input onchange="document.myform.submit();" id="vendor_<?php _e($res->id);?>" value="<?php _e($res->id);?>" type="radio" name="gifts">
									<label for="vendor_<?php _e($res->id);?>"><img alt="<?php _e($res->name);?>" title="<?php _e($res->name);?>" width="100" src="<?php _e($res->image_url);?>"></label>
								</li>
								<?php }?>
							</ul>
							<?php }elseif(isset($status)){?>
							<p>Gift Send Successfully</p>
							<?php }else{?>
							<p>No Gifts Available now. Please Try again later!!</p>
							<?php }?>
						</form>
					</div>
				</div>
			</div>  
		
		<?php
	}
	
	function check_login(){
		
		global $current_user;
		
		$autologin = explode("#", base64_decode(base64_decode($_GET['autologin'])));
		$creds = array();
		$creds['user_login'] = $autologin[0];
		$creds['user_password'] = $autologin[1];
		$creds['remember'] = true;
		$current_user = wp_signon( $creds, false );
		if ( is_wp_error($current_user) )
			return false;
		
		return true;
	}
	
	function frontend_action(){
		global $wpdb, $current_user, $ntm_mail, $endorsements;
		
		if(isset($_POST['send_invitation']))
		{
			$contact_list = explode(",", $_POST['contact_list']);
			$endorse_letter = $_POST['endorse_letter'];
			foreach($contact_list as $res)
			{
				$ex1 = explode("<", $res);
				$ex2 = explode(">", $ex1[1]);
				
				$info = array(
					"name" => $ex1[0], 
					"created" => date("Y-m-d H:i:s"), 
					"email" => $ex2[0],
					"endorser_id" => $current_user->ID,
					"tracker_id" => wp_generate_password( $length=12, $include_standard_special_chars=false ),
					"share_from" => get_permalink()
				);
				$wpdb->insert($wpdb->prefix . "endorsements", $info);
				$send_mail = $ntm_mail->send_invitation_mail($info, $current_user->ID, $wpdb->insert_id, $endorse_letter);
				if($send_mail)
				{
					if(isset($_POST['from_widget']))
					{
						$points = 5;
						$type = 'Mail Invitation from Widget';
					}
					else
					{
						$points = 25;
						$type = 'Mail Invitation from Endorsement page';
					}

					$new_balance = $endorsements->get_endorser_points($current_user->ID) + $points;
					$data = array('points' => $points, 'credit' => 1, 'endorser_id' => $current_user->ID, 'new_balance' => $new_balance, 'transaction_on' => date("Y-m-d H:i:s"), 'type' => $type);
					$endorsements->add_points($data);
				}
			}
			
			update_user_meta($current_user->ID, "invitation_sent", (get_user_meta($current_user->ID, "invitation_sent", true) + count($contact_list)));

			return true;
		}
		return false;
	}

	function redeem_points()
	{
		global $wpdb, $current_user, $ntm_mail, $endorsements;

		if(!is_user_logged_in() || !in_array( 'endorser', $current_user->roles ))
			return;

		$endorser_points = $endorsements->get_endorser_points($current_user->ID);

		if(isset($_POST['reward_redeem']))
		{
			if($endorser_points >= 25 && $endorser_points >= $_POST['points'] && 10000 >= $_POST['points'])
			{
				$data = array(
					"request_on" => date("Y-m-d H:i:s"),
					"endorser_id" => $current_user->ID,
					"points" => $_POST['points'],
					"status" => 0
				);
				$wpdb->insert($wpdb->prefix . "points_request", $data);
			}
			else
				$msg = '<p>Invalid request</p>';
		}
		
		?>
		<h3>Redeem Rewards</h3>
		<?= isset($msg) ? $msg : '';?>
		Your Points <?= $endorsements->get_endorser_points($current_user->ID); ?>
		<form method="post">
			<div>
				<label>Enter you points to redeem</label>
				<input name="points" type="number" minlength="25" maxlength="10000">
			</div>
			<input type="submit" name="reward_redeem">
		</form>
		<?php
	}

	function redeem_requests()
	{
		global $wpdb, $current_user, $ntm_mail, $endorsements;

		if(!is_user_logged_in() || !in_array( 'endorser', $current_user->roles ))
			return;
		$results = $wpdb->get_results('select * from '.$wpdb->prefix . "points_request where endorser_id=".$current_user->ID. " order by id desc");
		$st = array("Pending", "Accepted", "Cancelled");
		?>
		<h3>Redeem Requests</h3>
		Your Points <?= $endorsements->get_endorser_points($current_user->ID); ?>
		<table width="100%">
			<tr>
				<th>#</th>
				<th>Request on</th>
				<th>Points</th>
				<th>Status</th>
				<th>Notes</th>
			</tr>
			<?php foreach($results as $k=>$res){?>
			<tr>
				<td><?= $k+1; ?></td>
				<td><?= $res->request_on; ?></td>
				<td><?= $res->points; ?></td>
				<td><?= $st[$res->status]; ?></td>
				<td><?= $res->notes; ?></td>
			</tr>
			<?php }?>
		</table>
		<?php
	}

	function points_transaction()
	{
		global $wpdb, $current_user, $ntm_mail, $endorsements;

		if(!is_user_logged_in() || !in_array( 'endorser', $current_user->roles ))
			return;
		$results = $wpdb->get_results('select * from '.$wpdb->prefix . "points_transaction where endorser_id=".$current_user->ID. " order by id desc");
		?>
		<h3>Points Transactions</h3>
		Your Points <?= $endorsements->get_endorser_points($current_user->ID); ?>
		<table width="100%">
			<tr>
				<th>#</th>
				<th>Transaction type</th>
				<th>Transaction on</th>
				<th>Points</th>
				<th>New Balance</th>
			</tr>
			<?php foreach($results as $k=>$res){?>
			<tr>
				<td><?= $k+1; ?></td>
				<td><?= $res->type; ?></td>
				<td><?= $res->transaction_on; ?></td>
				<td><?= $res->points; ?></td>
				<td><?= $res->new_balance; ?></td>
			</tr>
			<?php }?>
		</table>
		<?php
	}
}