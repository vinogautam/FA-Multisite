<?php
include 'admin/ntm-admin.php';
include 'admin/ntm-mail_class.php';
include 'admin/ntm-listtable.php';
include 'frontend/frontend.php';
include 'sendgrid-php/sendgrid-php.php';

class CloudSponge_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'foo_widget', // Base ID
			__( 'Cloud Sponge', 'text_domain' ), // Name
			array( 'description' => __( 'Cloud Sponge', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {

		$user = wp_get_current_user();
		if(is_user_logged_in() && !in_array( 'endorser', (array) $user->roles))
		return;


		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
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

		  (function(u){
			var d=document,s='script',a=d.createElement(s),m=d.getElementsByTagName(s)[0];
			a.async=1;a.src=u;m.parentNode.insertBefore(a,m);
		  })('//api.cloudsponge.com/widget/<?php echo $instance['access'];?>.js');
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
		<div class="social_share">
			<a onclick="checkLoginState()"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/fbshare.png"/></a>
			<a onclick="checkLoginStateLinkedin()"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/linkedin.png"/></a>
			<!-- <a onclick="window.open('http://www.facebook.com/sharer.php?u='+encodeURIComponent('<?php echo get_permalink($pagelink).'?ref='.base64_encode(base64_encode($current_user->ID.'#&$#fb'));?>'),'sharer','toolbar=0,status=0,width=626,height=436');return false;"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/fbshare.png"/></a> -->
			<a onclick="window.open('https://twitter.com/intent/tweet?text=<?php echo get_option('twitter_text');?>&url='+encodeURIComponent('<?php echo get_permalink($pagelink).'?ref='.base64_encode(base64_encode($current_user->ID.'#&$#tw'));?>'),'sharer','toolbar=0,status=0,width=626,height=436');return false;"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/twshare.png"/></a>
		</div>
		<br>
		<div class="social_button">
			<?php if($instance['linkedin']){?><a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('linkedin');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/linkedin.png"/></a>
			<?php } if($instance['yahoo']){?>
			<a class="deep-link delayed" onclick="return cloudsponge.launch('yahoo');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/yahoo.png"/></a>
			<?php } if($instance['windowslive']){?>
			<a class="deep-link delayed" onclick="return cloudsponge.launch('windowslive');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/outlook.com.png"/></a>
			<?php } if($instance['gmail']){?>
			<a class="deep-link delayed" onclick="return cloudsponge.launch('gmail');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/gmail.png"/></a>
			<?php } if($instance['aol']){?>
			<a class="deep-link delayed" onclick="return cloudsponge.launch('aol');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/aol.png"/></a>
			<?php } if($instance['plaxo']){?>
			<a href="#" class="deep-link" onclick="return cloudsponge.launch('plaxo');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/plaxo.png"/></a>
			<?php } if($instance['addressbook']){?>
			<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('addressbook');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/apple-desktop.png"/></a>
			<?php } if($instance['outlook']){?>
			<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('outlook');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/outlook-desktop.png"/></a>
			<?php }?>

			<textarea name="contact_list" id="contact_list" rows="5" cols="73"></textarea>
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
			<input type="hidden" name="from_widget">
			<input name="send_invitation" class="button-primary seeker_btn" value="<?php _e('Invite your friends'); ?>" type="submit" />
			</p>
        </form>

		<?php
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );

		$access = ! empty( $instance['access'] ) ? $instance['access'] : __( 'Access Key', 'text_domain' );
		
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">

		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'access' ) ); ?>"><?php _e( esc_attr( 'Access key:' ) ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'access' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'access' ) ); ?>" type="text" value="<?php echo esc_attr( $access ); ?>">

		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'linkedin' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/linkedin.png"/></label> 
		<input <?= $instance['linkedin'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'linkedin' ) ); ?>" type="checkbox" value="1">

		<label for="<?php echo esc_attr( $this->get_field_id( 'yahoo' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/yahoo.png"/></label> 
		<input <?= $instance['yahoo'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'yahoo' ) ); ?>" type="checkbox" value="1">

		<label for="<?php echo esc_attr( $this->get_field_id( 'windowslive' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/outlook.com.png"/></label> 
		<input <?= $instance['windowslive'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'windowslive' ) ); ?>" type="checkbox" value="1">
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'gmail' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/gmail.png"/></label> 
		<input <?= $instance['gmail'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'gmail' ) ); ?>" type="checkbox" value="1">

		<label for="<?php echo esc_attr( $this->get_field_id( 'aol' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/aol.png"/></label> 
		<input <?= $instance['aol'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'aol' ) ); ?>" type="checkbox" value="1">

		<label for="<?php echo esc_attr( $this->get_field_id( 'plaxo' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/plaxo.png"/></label> 
		<input <?= $instance['plaxo'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'plaxo' ) ); ?>" type="checkbox" value="1">
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'addressbook' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/apple-desktop.png"/></label> 
		<input <?= $instance['addressbook'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'addressbook' ) ); ?>" type="checkbox" value="1">

		<label for="<?php echo esc_attr( $this->get_field_id( 'outlook' ) ); ?>"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>/icon-set/outlook-desktop.png"/></label> 
		<input <?= $instance['outlook'] ? "checked" : ""; ?> name="<?php echo esc_attr( $this->get_field_name( 'outlook' ) ); ?>" type="checkbox" value="1">

		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {

		
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['access'] = ( ! empty( $new_instance['access'] ) ) ? strip_tags( $new_instance['access'] ) : '';
		$instance['linkedin'] = isset($new_instance['linkedin']) ? 1 : 0;
		$instance['yahoo'] = isset($new_instance['yahoo']) ? 1 : 0;
		$instance['windowslive'] = isset($new_instance['windowslive']) ? 1 : 0;
		$instance['gmail'] = isset($new_instance['gmail']) ? 1 : 0;
		$instance['aol'] = isset($new_instance['aol']) ? 1 : 0;
		$instance['plaxo'] = isset($new_instance['plaxo']) ? 1 : 0;
		$instance['addressbook'] = isset($new_instance['addressbook']) ? 1 : 0;
		$instance['outlook'] = isset($new_instance['outlook']) ? 1 : 0;

		return $instance;
	}

}