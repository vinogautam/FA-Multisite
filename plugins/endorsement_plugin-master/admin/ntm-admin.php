<?php
class Endorsements_admin{
    
    function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_pages' ) );
    }


    function add_plugin_pages() {
        
        if(is_multisite() && is_super_admin() && is_main_site()) {
             
            add_menu_page( 'Endorsements', 'Endorsements', 'manage_options', 'ntmEndorsements', array( $this, 'create_ntmadmin_page' ));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Add Endorser',  9, 'ntmEndorsements&tab=add_endorsers', array( &$this, 'mail_template'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Email Template',  9, 'mail_template', array( &$this, 'mail_template'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Send Gift By Manual',  9, 'send_gift_manual', array( &$this, 'send_gift_manual'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Gift Transaction History',  9, 'gift_transaction_history', array( &$this, 'gift_transaction_history'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Visa Transaction History',  9, 'visa_transaction_history', array( &$this, 'visa_transaction_history'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Settings',  9, 'ntmEndorsements_settings', array( &$this, 'settingsPage'));		
        
        } else {
            
            add_menu_page( 'Endorsements', 'Endorsements', 'manage_options', 'ntmEndorsements', array( $this, 'create_ntmadmin_page' ));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Add Endorser',  9, 'ntmEndorsements&tab=add_endorsers', array( &$this, 'mail_template'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Email Template',  9, 'mail_template', array( &$this, 'mail_template'));
			add_submenu_page( 'ntmEndorsements', 'Endorsements', 'Send Gift By Manual',  9, 'send_gift_manual', array( &$this, 'send_gift_manual'));
        }
   
    } //end ntme_admin_menu()
    
    //our admin tabs navigation
    public function adminTabs($tabs, $default, $page){
        
        if ( isset ( $_GET['tab'] ) ) $current = $_GET['tab']; else $current = $default;
        
        echo '<div id="icon-themes" class="icon32"><br></div>';
        echo '<h2 class="nav-tab-wrapper">';
    
        foreach( $tabs as $tab => $name ){
            $class = ( $tab == $current ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=$page&tab=$tab'>$name</a>";
    
        }
        
        echo '</h2>';
    }
	
       
    /**
     * admin page callback
     */
    public function create_ntmadmin_page()
    {   global $pagenow, $current_user, $ntm_mail;
		if ( isset ( $_GET['tab'] ) ) $current = $_GET['tab']; else $current = 'endorsers';
		
		$tabs = array( 'endorsers' => 'Endorsers', 'add_endorsers' => 'Add New Endorsers', 'add_endorsers_cloudsponge' => 'Invite Endorser by cloudsponge', 'template_list' => 'Letter Template', 'add_template' => 'Add New Template', 'endorsement' => 'Endorsement');
		$current_page = $tabs[$current];
		$current_tab = $current.'_page';
		
		if($current != 'add_endorsers_cloudsponge')
		$error = $this->post_actions();
		
		?>
        <div class="wrap">
            <h2><?php echo $current_page;?></h2>           
            <?php 
				if(isset($error)) echo $error;
				$this->adminTabs($tabs, 'endorsers', 'ntmEndorsements');
				$this->$current_tab();
			?>
        </div>
        <?php
        
    }
	
	public function endorsers_page()
    {
		?>
		<link rel="stylesheet" href="<?php _e(NTM_PLUGIN_URL);?>/assets/css/colorbox.css" />
		<script src="<?php _e(NTM_PLUGIN_URL);?>/assets/js/jquery.colorbox-min.js"></script>
		<form id='postdataendorser' data-amount="<?php echo get_option('giftbit')['amount']?>" method="post">
		<?php
			$endosersTable = new EndoserTable();
			$endosersTable->prepare_items();
			$endosersTable->display();
		?>
		</form>
		<script>
			jQuery(document).ready(function(){
				jQuery(".inline").colorbox({inline:true, width:"50%"});
				
				jQuery(".inline").click(function(){
					type = jQuery(this).data("type");
					jQuery("#modalpopup"+type+" h2").text("Send Gift - "+jQuery(this).data("name"));
					jQuery(".endorser_id").val(jQuery(this).data("id"));
					jQuery.post(
						ajaxurl, 
						{
							'action': 'get_endorsement',
							'id':   jQuery(this).data("id"),
							'type':   type
						}, 
						function(response){
							response = JSON.parse(response);
							if(type == 'new')
							{
								html = ''; 
								jQuery.each(response['converted_endorsement'], function(k,v){
									html += ( k+1 )+'. '+v['name']+' - '+v['email'];
								});
								jQuery("#converted_endorsement").html(html);
								jQuery("#social_converted").html('Facebook-'+ (response['facebook'] ?response['facebook'] : 0) +'<br>Twitter-'+(response['twitter'] ?response['twitter'] : 0));
							}
							else
							{
								html = '';
								jQuery.each(response['converted_endorsement'], function(k,v){
									html += '<option value="'+v['id']+'">. '+v['name']+' - '+v['email']+'</option>';
								});
								jQuery("#endorsement_list").html(html);
							}
						}
					);
				});
			});
		</script>
		<script>
			jQuery(document).ready(function(){
				jQuery("#resendgift").hide();
				jQuery("#sendgift").hide();
				jQuery("#gift_amount").change(function(){
					if(jQuery("#gift_amount").val() <= jQuery("#postdataendorser").data('amount'))
						jQuery("#sendgift").show();
					else
					{
						alert("Insufficient balance");
						jQuery("#sendgift").hide();
					}
				});
				
				jQuery("#resendgift_amount").change(function(){
					if(jQuery("#resendgift_amount").val() <= jQuery("#postdataendorser").data('amount'))
						jQuery("#resendgift").show();
					else
					{
						alert("Insufficient balance");
						jQuery("#resendgift").hide();
					}
				});
			});
		</script>
		<div style='display:none'>
			<div class="modalpopups" id='modalpopupnew' style='padding:10px; background:#fff;'>
				<h2></h2>
				<form method="post" action="<?php admin_url( 'admin.php?page=ntmEndorsements' ); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="blogname">Converted Endorsement</label></th>
								<td id="converted_endorsement">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="blogname">Social Count</label></th>
								<td id="social_converted">
									
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="blogname">Gift Amount</label></th>
								<td><select class="regular-text" id="gift_amount" name="gift_amount">
										<option value="">Select amount</option>
										<option value="5">5$</option>
										<option value="10">10$</option>
										<option value="25">25$</option>
										<option value="50">50$</option>
										<option value="100">100$</option>
										<option value="150">150$</option>
										<option value="200">200$</option>
									</select>
								</td>
							</tr>
							
						</tbody>
					</table>
					<input type="hidden" name="endorser_id" class="endorser_id">
					<?php submit_button('Send Gift', 'primary', 'sendgift');?>
				</form>
			</div>
			<div class="modalpopups" id='modalpopupold' style='padding:10px; background:#fff;'>
				<h2></h2>
				<form method="post" action="<?php admin_url( 'admin.php?page=ntmEndorsements' ); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="blogname">Gift Send Endorsements</label></th>
								<td>
									<select id="endorsement_list" multiple class="regular-text" name="endorsement[]">
										
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="blogname">Gift Amount</label></th>
								<td><select class="regular-text" id="resendgift_amount" name="gift_amount">
										<option value="">Select amount</option>
										<option value="5">5$</option>
										<option value="10">10$</option>
										<option value="25">25$</option>
										<option value="50">50$</option>
										<option value="100">100$</option>
										<option value="150">150$</option>
										<option value="200">200$</option>
									</select>
								</td>
							</tr>
							
						</tbody>
					</table>
					<input type="hidden" name="endorser_id" class="endorser_id">
					<?php submit_button('ReSend Gift', 'primary', 'resendgift');?>
				</form>
			</div>
		</div>
		<?php
	}
    
    public function add_endorsers_page()
    {
		global $wpdb;
		
		$endorser_template = $wpdb->get_results("select * from ".$wpdb->prefix . "mailtemplates where type='Endorser'");
		$endorsement_template = $wpdb->get_results("select * from ".$wpdb->prefix . "mailtemplates where type!='Endorser'");
		
		if(isset($_GET['edit']))
		{	
			$user = get_userdata($_GET['edit']);
			$usermeta = get_user_meta($_GET['edit']);
		}
		
		?>
		<script type='text/javascript' src='<?php _e(NTM_PLUGIN_URL);?>/assets/js/jquery.validate.min.js'></script>
		<script>
			jQuery(document).ready(function(){
				jQuery("#endorser_form").validate();
			})
		</script>
		<style>
		.error{color:red;}
		</style>
		<form id="endorser_form" method="post" action="<?php admin_url( 'admin.php?page=ntmEndorsements' ); ?>">
			<table  class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Firstname</label></th>
						<td><input required type="text" class="regular-text" value="<?php echo isset($usermeta) ? $usermeta['first_name'][0]: '';?>"  name="user[first_name]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Lastname</label></th>
						<td><input required type="text" class="regular-text" value="<?php echo isset($usermeta) ? $usermeta['last_name'][0]: '';?>"  name="user[last_name]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Email</label></th>
						<td><input required type="text" class="regular-text" <?php echo isset($user) ? 'disabled' : '';?> value="<?php echo isset($user) ? $user->user_email: '';?>"  name="user[user_email]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Endorser Letter</label></th>
						<td><select class="regular-text" name="endorser_letter">
								<?php foreach($endorser_template as $r){?>
								<option <?php echo isset($usermeta) && $usermeta['endorser_letter'][0] == $r->id ? 'selected' : '';?> value="<?php _e($r->id);?>"><?php _e($r->name);?></option>
								<?php }?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Endorsement Letter</label></th>
						<td><select class="regular-text" name="endorsement_letter">
								<?php foreach($endorsement_template as $r){?>
								<option <?php echo isset($usermeta) && $usermeta['endorsement_letter'][0] == $r->id ? 'selected' : '';?> value="<?php _e($r->id);?>"><?php _e($r->name);?></option>
								<?php }?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button();?>
		</form>
		<?php
	}
	
	public function add_endorsers_cloudsponge_page()
    {
		global $wpdb, $ntm_mail;
		
		if(isset($_POST['submit']))
		{
			$contact_list = explode(",", $_POST['contact_list']);
			$endorse_letter = $_POST['endorse_letter'];
			foreach($contact_list as $res)
			{
				$ex1 = explode("<", $res);
				$ex2 = explode(">", $ex1[1]);
				
				$user = array();
				$user['role'] = 'endorser';
				$user['user_login'] = strtolower(str_replace(" ", "_", $ex1[0]));
				$user['user_email'] = $ex2[0];
				
				$user_id = username_exists( $user['user_login'] );
				if ( !$user_id and email_exists($user['user_email']) == false ) {
					$user['user_pass'] = wp_generate_password( $length=12, $include_standard_special_chars=false );
					$user_id = wp_insert_user( $user ) ;
					if (  !is_wp_error( $user_id ) )
					{
						update_user_meta( $user_id, 'imcomplete_profile', 1);
						$ntm_mail->send_welcome_mail($user['user_email'], $user_id, $user['user_login'].'#'.$user['user_pass'], $_POST['endorser']);
						$ntm_mail->send_notification_mail($user_id);
					}
				} 
			}
		}
		
		?>
		<script type='text/javascript' src='<?php _e(NTM_PLUGIN_URL);?>/assets/js/jquery.validate.min.js'></script>
		<script>
			jQuery(document).ready(function(){
				jQuery("#endorser_form").validate();
			})
		</script>
		<script>
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
		<style>
		.error{color:red;}
		</style>
		
		<table  class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Social links</label></th>
						<td colspan="2" scope="row">
							<label for="blogname">
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
							</label>
						</td>
					</tr>
				</tbody>
			</table>
			<form id="endorser_form" method="post" >
			<table  class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Contact list</label></th>
						<td><textarea readonly required name="contact_list" id="contact_list" rows="5" cols="73"></textarea></td>
					</tr>
					<?php 
						$mailtemplate 	 	= 	$ntm_mail->get_endorser_invitation_mail();
					?>
					<tr>
						<th scope="row"><label for="blogname">Invitation subject</label></th>
						<td><input name="endorser[subject]" class="regular-text" value="<?php echo $mailtemplate['subject']; ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Invitation template</label></th>
						<td><?php echo wp_editor($mailtemplate['content'], 'endorser_letter', array('textarea_name' => 'endorser[content]', 'editor_height' => 200));?></td>
					</tr>
				</tbody>
			</table>
			<?php submit_button();?>
		</form>
		<?php
	}
	
	public function template_list_page()
    {
		?>
		<form method="post">
		<?php
			$endosersTable = new LetterTable();
			$endosersTable->prepare_items();
			$endosersTable->display();
		?>
		</form>
		<?php
	}
	
	public function endorsement_page()
    {
		?>
		<form method="post">
		<?php
			$endosersTable = new EndorsementTable();
			$endosersTable->prepare_items();
			$endosersTable->display();
		?>
		</form>
		<?php
	}
    
    public function add_template_page()
    {
		global $wpdb;
		
		if(isset($_GET['edit']))
			$template = $wpdb->get_row("select * from ".$wpdb->prefix . "mailtemplates where id='".$_GET['edit']."'");
		?>
		<script>
			jQuery(document).ready(function(){
				jQuery("#ltype").change(function(){
					if(jQuery(this).val() == 'Endorsement')
						jQuery("#landing_page").show();
					else
						jQuery("#landing_page").hide();
				});
			});
		</script>
		<script type='text/javascript' src='<?php _e(NTM_PLUGIN_URL);?>/assets/js/jquery.validate.min.js'></script>
		<script>
			jQuery(document).ready(function(){
				jQuery("#template_form").validate();
			})
		</script>
		<style>
		.error{color:red;}
		</style>
		<form id="template_form" method="post" action="<?php admin_url( 'admin.php?page=ntmEndorsements' ); ?>">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Type</label></th>
						<td><select id="ltype" class="regular-text" name="letter[type]">
								<option <?php echo isset($template) && $template->type == 'Endorser' ? 'selected' : '';?>>Endorser</option>
								<option <?php echo isset($template) && $template->type == 'Endorsement' ? 'selected' : '';?>>Endorsement</option>
							</select>
						</td>
					</tr>
					<tr id="landing_page" style="display:<?php echo isset($template) && $template->type == 'Endorsement' ? '' : 'none';?>">
						<th scope="row"><label for="blogname">Landing page</label></th>
						<td><select  name="letter[page]"> 
							 <option value="">
							<?php echo esc_attr( __( 'Select page' ) ); ?></option> 
							 <?php 
							  $pages = get_pages(); 
							  foreach ( $pages as $page ) {
								$sel = isset($template) && $template->page == $page->ID ? 'selected' : '';
								$option = '<option '.$sel.' value="' . $page->ID . '">';
								$option .= $page->post_title;
								$option .= '</option>';
								echo $option;
							  }
							 ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Letter Name</label></th>
						<td><input required type="text" class="regular-text" value="<?php echo isset($template) ? $template->name: '';?>" name="letter[name]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Subject</label></th>
						<td><input required type="text" class="regular-text" value="<?php echo isset($template) ? $template->subject: '';?>"  name="letter[subject]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Content</label></th>
						<td>
							<?php echo wp_editor(isset($template) ? $template->content: '', 'editor', array('textarea_name' => 'letter[content]', 'editor_height' => 200));?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button('Save Letter', 'primary', 'letter-save');?>
		</form>
		<?php
	}
	
	public function mail_template()
	{
		global $ntm_mail;
	
	$templates = array (

			'welcome_mail' 		=>	 "New Endorser Welcome Email template",
			
			'notification_mail' 		=>	 "New Endorser Notification to Admin Email template",

			'invitation_mail'		=> 	 "Endorsement Invitation Email template",
			
			'endorser_invitation_mail'		=> 	 "Endorser Invitation Email template",
			
			'gift_mail'		=> 	 "Gift Conversion Email template",
			
			'regift_mail'		=> 	 "ReSend Gift Email template",
			
			'manualgift_mail'		=> 	 "Manual Gift Email template"

		);
	
	$key = array_keys($templates);
	$value = array_values($templates);
	
	if(isset($_POST['update_mail_template']))
	{
		$ntm_mail->set_welcome_mail ($_POST['welcome_mail'],$_POST['welcome_mail_subject'],false);
		
		$ntm_mail->set_notification_mail ($_POST['notification_mail'],$_POST['notification_mail_subject'],false);
		
		$ntm_mail->set_invitation_mail ($_POST['invitation_mail'],$_POST['invitation_mail_subject'],false);
		
		$ntm_mail->set_gift_mail ($_POST['gift_mail'],$_POST['gift_mail_subject'],false);
		
		$ntm_mail->set_regift_mail ($_POST['regift_mail'],$_POST['regift_mail_subject'],false);
		
		$ntm_mail->set_manualgift_mail ($_POST['manualgift_mail'],$_POST['manualgift_mail_subject'],false);
		
		$ntm_mail->set_endorser_invitation_mail ($_POST['endorser_invitation_mail'],$_POST['endorser_invitation_mail_subject'],false);
		
	}
	elseif(isset($_GET['reset']))
	{
		$ntm_mail->reset_mail_template ($_GET['reset']);
	}
	
		$get_mail = array();
		
		$get_mail[] 	 	= 	$ntm_mail->get_welcome_mail ();
		
		$get_mail[] 	 	= 	$ntm_mail->get_notification_mail ();
		
		$get_mail[] 	 	= 	$ntm_mail->get_invitation_mail ();
		
		$get_mail[] 	 	= 	$ntm_mail->get_endorser_invitation_mail();
		
		$get_mail[] 	 	= 	$ntm_mail->get_gift_mail ();
		
		$get_mail[] 	 	= 	$ntm_mail->get_regift_mail ();
		
		$get_mail[] 	 	= 	$ntm_mail->get_manualgift_mail ();
		
		
		
	?>
    <link rel="stylesheet" type="text/css" href="<?php _e(NTM_PLUGIN_URL);?>/assets/css/ckeditor.css" media="all" />
    <script type='text/javascript' src='<?php _e(NTM_PLUGIN_URL);?>/assets/js/ckeditor/ckeditor.js'></script>
    
    <div id="poststuff" class="wrap">
    <h2>Email template</h2>
    <?php if(isset($message)){?>
    <div id="message" class="updated"><p><?php echo $message;?></p></div>
    <?php }?>
		<div class="postbox">
            <div class="inside group">
            	<form name="myform" method="post" >
                <table id="country" class="form-table">
                    <?php for($i=0;$i<count($templates);$i++){?>
                    <tr>
                        <td colspan="2" style="border-top: 1px #ddd solid; background: #eee"><strong><?php _e($value[$i]);?></strong><small><a href="admin.php?page=mail_template&reset=<?php _e($key[$i]);?>">Reset</a></small></td>
                    </tr>
                    <tr>
                        <th><label>Subject</label></th>
                        <th><input size="60" name="<?php _e($key[$i]._subject);?>" value="<?php _e($get_mail[$i]['subject']);?>" type="text" /></th>
                    </tr>
                    <tr>
                        <th><label>Content</label></th>
                        <th><textarea cols="80" id="editor<?php _e($i);?>" name="<?php _e($key[$i]);?>" rows="10"><?php _e($get_mail[$i]['content']);?></textarea></th>
                    </tr>
                    <?php }?>
				</table>
                <script>
					<?php for($i=0;$i<count($templates);$i++){?>
					CKEDITOR.replace( 'editor<?php _e($i);?>' );
					<?php }?>
				</script>
                <p class="submit">
                	<input type="hidden" name="role" value="job-seeker">
                    <input name="update_mail_template" class="button-primary seeker_btn" value="<?php _e('Save Changes'); ?>" type="submit" />
                </p>
                </form>
            </div>
        </div>
    </div>    
		
<?php 
	}
	
	function post_actions()
	{
		global $wpdb, $ntm_mail, $current_user;
		
		if(isset($_POST['sendgift']))
		{
			$data = array(
							'endorser_id' =>$_POST['endorser_id'],
							'amout' => $_POST['gift_amount'],
							'fb_count'	=> get_user_meta($_POST['id'], "tracked_fb_counter", true),
							'twitter_count'	=> get_user_meta($_POST['id'], "tracked_tw_counter", true),
							"agent_id" => $current_user->ID,
							'created'	=> date("Y-m-d H:i:s")
							);
			$wpdb->insert($wpdb->prefix . "gift_transaction", $data);
			$gift_id = $wpdb->insert_id;
			$get_results = $wpdb->get_results("select * from ".$wpdb->prefix . "endorsements where endorser_id=".$_POST['endorser_id']." and track_status is not null and gift_status is null");
			
			foreach($get_results as $res)
			{
				$wpdb->insert($wpdb->prefix . "giftendorsements", array(
																	"gift_id" => $gift_id, 
																	"endorser_id" => $_POST['endorser_id'], 
																	"endorsement_id" => $res->id
																	)
								);
				$wpdb->update($wpdb->prefix . "endorsements", array('gift_status' => 1), array('id' => $res->id));
			}
			
			update_user_meta($_POST['endorser_id'], "tracked_fb_counter", 0);
			update_user_meta($_POST['endorser_id'], "tracked_tw_counter", 0);
			update_user_meta($_POST['endorser_id'], "tracked_counter", 0);
			$ntm_mail->send_gift_mail('get_gift_mail', $_POST['endorser_id'], $gift_id);
		}
		elseif(isset($_POST['resendgift']))
		{
			$data = array(
							'endorser_id' =>$_POST['endorser_id'],
							'amout' => $_POST['gift_amount'],
							'agent_id' => get_current_user_id(),
							'created'	=> date("Y-m-d H:i:s")
							);
			$wpdb->insert($wpdb->prefix . "gift_transaction", $data);
			$gift_id = $wpdb->insert_id;
			foreach($_POST['endorsement'] as $res)
			{
				$wpdb->insert($wpdb->prefix . "giftendorsements", array(
																	"gift_id" => $gift_id, 
																	"endorser_id" => $_POST['endorser_id'], 
																	"endorsement_id" => $res
																	)
								);
				$wpdb->update($wpdb->prefix . "endorsements", array('gift_status' => 2), array('id' => $res->id));
			}
			$ntm_mail->send_gift_mail('get_regift_mail', $_POST['endorser_id'], $gift_id);
		}
		elseif(isset($_POST['submit']) && isset($_GET['edit']))
		{
			update_user_meta($_GET['edit'], 'endorser_letter', $_POST['endorser_letter']);
			update_user_meta($_GET['edit'], 'endorsement_letter', $_POST['endorsement_letter']);
			update_user_meta($_GET['edit'], 'first_name', $_POST['user']['first_name']);
			update_user_meta($_GET['edit'], 'last_name', $_POST['user']['last_name']);
		}
		elseif(isset($_POST['submit']))
		{
			$user = $_POST['user'];
			$user['role'] = 'endorser';
			$user['user_login'] = strtolower($user['first_name'].'_'.$user['last_name']);
			
			$user_id = username_exists( $user['user_login'] );
			if ( !$user_id and email_exists($user['user_email']) == false ) {
				$user['user_pass'] = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$user_id = wp_insert_user( $user ) ;
				if (  is_wp_error( $user_id ) ) {
					return __('Something went wrong. Try Again!!!.');
				}
				else
				{
					update_user_meta($user_id, 'endorser_letter', $_POST['endorser_letter']);
					update_user_meta($user_id, 'endorsement_letter', $_POST['endorsement_letter']);
					$ntm_mail->send_welcome_mail($user['user_email'], $user_id, $user['user_login'].'#'.$user['user_pass']);
					$ntm_mail->send_notification_mail($user_id);
				}
			} else {
				return __('User already exists.  Password inherited.');
			}
		}
		elseif(isset($_GET['resend_welcome_email']))
		{
			$userpass = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$user_info = get_userdata($_GET['resend_welcome_email']);
			$username = $user_info->user_login;
			wp_set_password( $userpass, $_GET['resend_welcome_email'] );
			$ntm_mail->send_welcome_mail($user_info->user_email, $_GET['resend_welcome_email'], $username.'#'.$userpass);
		}
		elseif(isset($_POST['letter-save']))
		{
			//echo $wpdb->prefix . "mailtemplates";
			if(isset($_GET['edit']))
			{
				$wpdb->update($wpdb->prefix . "mailtemplates", $_POST['letter'], array('id' => $_GET['edit']));
			}
			else
			{
				$_POST['letter']['created'] = date("Y-m-d H:i:s");
				$wpdb->insert($wpdb->prefix . "mailtemplates", $_POST['letter']);
			}
		}
		elseif(isset($_GET['tab']) && $_GET['tab'] == 'template_list' && isset($_GET['delete']))
			$wpdb->delete($wpdb->prefix . "mailtemplates", array( 'id' => $_GET['delete'] ) );
		elseif(isset($_GET['tab']) && $_GET['tab'] == 'endorsement' && isset($_GET['delete']))
			$wpdb->delete($wpdb->prefix . "endorsements", array( 'id' => $_GET['delete'] ) );
		elseif(isset($_GET['tab']) && $_GET['tab'] == 'endorsers' && isset($_GET['delete']))
			wpmu_delete_user($_GET['delete']);
	}
	
	public function settingsPage()
    {   global $pagenow, $current_user, $ntm_mail;
		if ( isset ( $_GET['tab'] ) ) $current = $_GET['tab']; else $current = 'settingsgeneral';
		
		$tabs = array('settingsgeneral' => 'General', 'cloudsponge' => 'Cloudsponge', 'giftbit' => 'Giftbit', 'SendGrid' => 'SendGrid');
		$current_page = $tabs[$current];
		$current_tab = $current.'_settings';
		
		$error = $this->settings_actions();
		
		?>
        <div class="wrap">
            <h2><?php echo $current_page;?></h2>           
            <?php 
				if(isset($error)) echo $error;
				$this->adminTabs($tabs, 'settingsgeneral', 'ntmEndorsements_settings');
				$this->$current_tab();
			?>
        </div>
        <?php
        
    }
	
	public function settingsgeneral_settings()
	{
		$option = get_option('twitter_text');
		?>
		
		<form method="post">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Twitter text</label></th>
						<td><textarea name="twitter_text" rows="3" cols="60"><?php echo stripslashes($option);?></textarea></td>
					</tr>
					
				</tbody>
			</table>
			<?php submit_button('Save ', 'primary', 'general-save');?>
		</form>
		<?php
	}
	
	public function cloudsponge_settings()
	{
		$option = get_option('cloudsponge');
		?>
		<form method="post">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Javscript Access key</label></th>
						<td><input type="text" class="regular-text" value="<?php echo $option;?>" id="blogname" name="cloudsponge"></td>
					</tr>
				</tbody>
			</table>
			<?php submit_button('Save ', 'primary', 'cloudsponge-save');?>
		</form>
		<?php
	}
	
	public function sendgrid_settings()
	{
		$option = get_option('sendgrid');
		?>
		<form method="post">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th scope="row"><label for="blogname">Sendgrid Api key</label></th>
						<td><input type="text" class="regular-text" value="<?php echo $option['api'];?>" id="blogname" name="sendgrid[api]"></td>
					</tr>
				</tbody>
			</table>
			<?php submit_button('Save ', 'primary', 'sendgrid-save');?>
		</form>
		<?php
	}
	
	public function giftbit_settings()
	{
		
		$option = get_option('giftbit');
		
		$headers = array('Authorization: Bearer '.$option['api']);
		
		if(isset($option['sandbox']))
			$ch = curl_init("https://testbedapp.giftbit.com/papi/v1/marketplace/region");
		else	
			$ch = curl_init("https://api.giftbit.com/papi/v1/marketplace/region");
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$curl_response = curl_exec($ch);
		curl_close($ch);
        
        $regions = array();
		$subregions = array();
		foreach(json_decode($curl_response)->regions as $res)
		{
			if(isset($res->parent_id))
				$subregions[] = $res;
			else
				$regions[] = $res;
		}
		
		//echo "<pre>";print_r(json_decode($curl_response));
		$giftbitregion = get_option('giftbitregion');
		$giftbitsubregion = get_option('giftbitsubregion');
		?>
		<script>
			jQuery(document).ready(function(){
				jQuery("#giftbitregion").change(function(){
					jQuery("#giftbitsubregion").val("");
					if(jQuery.trim(jQuery(this).val()))
					{
						jQuery("#giftbitsubregion option[data-id]").hide();
						jQuery("#giftbitsubregion option[data-id='"+jQuery(this).val()+"']").show();
					}
					else
					{
						jQuery("#giftbitsubregion option[data-id]").hide();
					}
				});
				
				if(jQuery.trim(jQuery("#giftbitregion").val()))
				{
					jQuery("#giftbitsubregion option[data-id]").hide();
					jQuery("#giftbitsubregion option[data-id='"+jQuery("#giftbitregion").val()+"']").show();
				}
			});
		</script>
		<form method="post">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Giftbit amount</label></th>
						<td><input type="text" class="regular-text" value="<?php echo isset($option['amount']) ? $option['amount'] : '';?>" id="blogname" name="giftbit[amount]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Api key</label></th>
						<td><textarea rows="4" cols="80" name="giftbit[api]"><?php echo isset($option['api']) ? $option['api'] : '';?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Sandbox</label></th>
						<td><input type="checkbox" class="regular-text" <?php echo isset($option['sandbox']) ? 'checked' : '';?> value="1" id="blogname" name="giftbit[sandbox]"></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Giftbit Region</label></th>
						<td>
							<select id="giftbitregion" name="giftbitregion">
								<option value="">Select Region</option>
								<?php foreach($regions as $res){ $sel = $res->id == $giftbitregion ? 'selected' : '';?>
								<option <?php _e($sel);?> value="<?php _e($res->id);?>"><?php _e($res->name);?></option>
								<?php }?>
							</select>
						</td>
					</tr>
					<!--<tr>
						<th scope="row"><label for="blogname">Giftbit Sub Region</label></th>
						<td>
							<select id="giftbitsubregion" name="giftbitsubregion">
								<option value="">Select Sub Region</option>
								<?php foreach($subregions as $res){$sel = $res->id == $giftbitsubregion ? 'selected' : '';?>
								<option <?php _e($sel);?> style="display:none;" data-id="<?php _e($res->parent_id);?>" value="<?php _e($res->id);?>"><?php _e($res->name);?></option>
								<?php }?>
							</select>
						</td>
					</tr>-->
				</tbody>
			</table>
			<?php submit_button('Save ', 'primary', 'giftbit-save');?>
		</form>
		<?php
	}
	
	public function settings_actions()
	{
		if(isset($_POST['sendgrid-save']))
		{
			update_option('sendgrid', $_POST['sendgrid']);
		}
		elseif(isset($_POST['cloudsponge-save']))
		{
			update_option('cloudsponge', $_POST['cloudsponge']);
		}
		elseif(isset($_POST['giftbit-save']))
		{
			update_option('giftbit', $_POST['giftbit']);
			
			update_option('giftbitregion', $_POST['giftbitregion']);
			update_option('giftbitsubregion', $_POST['giftbitsubregion']);
		}
		elseif(isset($_POST['general-save']))
		{
			update_option('twitter_text', $_POST['twitter_text']);
		}
	}
	
	public function send_gift_manual()
	{
		global $wpdb, $ntm_mail;
		if(isset($_POST['send_gift']))
		{
			$option = get_option('giftbit');
			
			if($option['amount'] >= $_POST['gift_amount'])
			{
				$this->fa_lead_options = get_option('fa_lead_settings');
				
				Stripe::setApiKey($this->fa_lead_options['api_key']);
				Stripe::setAPIVersion("2015-07-13");
				
				$customer_id = get_user_meta(get_blog_option(get_current_blog_id(), 'agent_id'), "pmpro_stripe_customerid");
				
				$amount = $this->fa_lead_options['admin_fee'] * 100;
				$giftAmount =  $this->fa_lead_options['init_gift'];

				$invoice_item = Stripe_InvoiceItem::create( array(
					'customer'    => $customer_id, // the customer to apply the fee to
					'amount'      => $this->fa_lead_options['admin_fee'], // amount in cents
					'currency'    => 'usd',
					'description' => 'One-time setup fee' // our fee description
				) );
			 
				$invoice = Stripe_Invoice::create( array(
					'customer'    => $customer_id, // the customer to apply the fee to
				) );
			 
				$result = $invoice->pay();
				if(isset($result->object) && $result->object == 'invoice')
				{
				
					$data = array(
								'endorser_id' =>$_POST['endorser_id'],
								'amout' => $_POST['gift_amount'],
								'agent_id' => get_current_user_id(),
								'created'	=> date("Y-m-d H:i:s")
								);
					$wpdb->insert($wpdb->prefix . "gift_transaction", $data);
					$gift_id = $wpdb->insert_id;
					$ntm_mail->send_gift_mail('get_manualgift_mail', $_POST['endorser_id'], $gift_id);
					
					$option['amount'] = $option['amount'] - $_POST['gift_amount'];
					update_option("giftbit", $option);
					
					$message = "Gift send successfully!!";
				}
				else
					$message = "Error when payment";
			}
			else
			{
				$message = "Error! Insufficient balance!";
			}
		}
		//print_r(get_users(array('role'=>'endorser')));
		?>
		
		<script>
			jQuery(document).ready(function(){
				jQuery("#gift_amount").change(function(){
					if(jQuery("#gift_amount").val() <= jQuery("#poststuff").data('amount'))
						jQuery("#send_gift").show();
					else
					{
						alert("Insufficient balance");
						jQuery("#send_gift").hide();
					}
				});
			});
		</script>
		
		<div data-amount="<?php echo get_option('giftbit')['amount']?>" id="poststuff" class="wrap">
		<h2>Send Gift By Manual</h2>
		<?php if(isset($message)){?>
		<div id="message" class="updated"><p><?php echo $message;?></p></div>
		<?php }?>
			<div class="postbox">
				<div class="inside group">
					<form name="myform" method="post" >
					<table id="country" class="form-table">
						<tr>
							<th scope="row"><label for="blogname">Endorser</label></th>
							<td>
								<select class="regular-text" name="endorser_id">
									<option value="">Select Endorser</option>
									<?php 
									foreach(get_users(array('role'=>'endorser')) as $res){?>
									<option value="<?php _e($res->data->ID);?>"><?php _e($res->data->user_login.' - '.$res->data->user_email);?></option>
									<?php }?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="blogname">Gift Amount</label></th>
							<td>
								<select class="regular-text" id="gift_amount" name="gift_amount">
									<option value="5">5$</option>
									<option value="10">10$</option>
									<option value="25">25$</option>
									<option value="50">50$</option>
									<option value="100">100$</option>
									<option value="150">150$</option>
									<option value="200">200$</option>
								</select>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input style="display:none;" id="send_gift" name="send_gift" class="button-primary seeker_btn" value="<?php _e('Save Changes'); ?>" type="submit" />
					</p>
					</form>
				</div>
			</div>
		</div> 
		<?php
	}
    
	function gift_transaction_history()
	{
		
		?>
		<div id="poststuff" class="wrap">
		<h2>Gift Transaction History</h2>
		
			<div class="postbox">
				<div class="inside group">
					<form name="myform" method="post" >
						<link rel="stylesheet" href="<?php _e(NTM_PLUGIN_URL);?>/assets/css/colorbox.css" />
						<script src="<?php _e(NTM_PLUGIN_URL);?>/assets/js/jquery.colorbox-min.js"></script>
						<?php
							$endosersTable = new GiftTable();
							$endosersTable->prepare_items();
							$endosersTable->display();
						?>
					</form>
				</div>
			</div>
		</div> 
		<?php
	}

	function visa_transaction_history()
	{
		global $wpdb;

		if(isset($_GET['action']) && $_GET['action'] == 'change_status')
		{
			$wpdb->update("visa_transaction", array('status' => 1), array('id' => $_GET['id']));
		}
		?>
		<div id="poststuff" class="wrap">
		<h2>Visa Transaction Details</h2>
		
			<div class="postbox">
				<div class="inside group">
					<form name="myform" method="post" >
						<link rel="stylesheet" href="<?php _e(NTM_PLUGIN_URL);?>/assets/css/colorbox.css" />
						<script src="<?php _e(NTM_PLUGIN_URL);?>/assets/js/jquery.colorbox-min.js"></script>
						<?php
							$endosersTable = new VisaTable();
							$endosersTable->prepare_items();
							$endosersTable->display();
						?>
					</form>
				</div>
			</div>
		</div> 
		<?php
	}
} //end class endorsements
     
   
