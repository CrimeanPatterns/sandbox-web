<?php
require "../../kernel/public.php";
require_once 'lib/config.php';

$user = User::fbc_getLoggedIn();
($user) ? $fb_active_session = $user->fbc_is_session_active() : $fb_active_session = FALSE;

$sTitle = "Tell your Facebook buddies about AwardWallet!"; /*Checked by Alexi*/

require("$sPath/design/header.php");

if (is_fbconnect_enabled()) {
	echo render_fbconnect_init_js();
}
if (!$user) {
	// DISPLAY PAGE WHEN USER IS NOT LOGGED IN TO FB CONNECT
	echo "<table align=center><tr><td>";
	$Interface->DrawBeginBox(400);
	echo "<div style='text-align: left'>You need to authorize ".SITE_NAME." on Facebook to invite your friends.<br><br>Please press the button below to do so:</div>"; /*checked by Alexi*/
	echo '<br><div style="text-align: center;" id="buttonDiv"><a href="#" onclick="connect(); return false;" >
		<img id="fb_login_image" border="0" src="http://static.ak.fbcdn.net/images/fbconnect/login-buttons/connect_light_medium_long.gif" alt="Connect"/>
		</a></div>';
	$Interface->DrawEndBox();
	echo "</td></tr></table>";
	//$onload_js .= ';FB.Connect.requireSession();';
}
else{
	echo '<link type="text/css" rel="stylesheet" href="' . CONNECT_CSS_PATH . 'style.css" />';
	if ($user->fbc_is_facebook_user()) {
		$friends = $user->fbc_get_unconnected_friends(TRUE);
		$conFriends = $user->fbc_get_connected_friends(TRUE);
		if(false && empty($friends)){
			$Interface->DrawMessageBox("No friends to invite.", "warning");
		}
		else{
			echo "<form id=inviteForm method=post action=\"http://www.facebook.com/multi_friend_selector.php\" style=\"padding: 5px 0px;\">";
			$arExclude = array();
//			if(!empty($conFriends))
//				foreach($conFriends as $friend)
//					$arExclude[] = $friend['uid'];
			$params = array(
				'api_key' => FACEBOOK_KEY,
				'content' => "AwardWallet.com keeps track of your rewards for you and tells you exactly what you need to know to make the most of your reward programs. <fb:req-choice url='http://{$_SERVER['HTTP_HOST']}/' label='Visit AwardWallet.com' />", /*checked by Alexi*/
				'type' => 'Site',
				'action' => 'http://'.$_SERVER['HTTP_HOST']."/",
				'actiontext' => 'Let your friends know about AwardWallet.com', /*checked by Alexi*/
				'invite' => 'true',
				'rows' => 10,
				'max' => 20,
				'exclude_ids' => implode(",", $arExclude),
			);
			$params['sig'] = facebook_client()->generate_sig($params, FACEBOOK_SECRET);
			DrawHiddens($params);
			echo 'Redirecting to Facebook..';
			$onload_js .= '; inviteFriends();';
			echo "</form>";
		}
	}
	// Print out all onload function calls

}
if (isset($onload_js)) {
	echo '<script type="text/javascript">'
		.'window.onload = function() { ' . $onload_js . ' };'
		.'</script>';
}

?>
<script>
function inviteFriends(){
	var form = document.getElementById('inviteForm');
	var exclude = '';
	for( var i = 0; i < form.length; i++){
		var control = form[i];
		if((control.type == 'checkbox')){
			if(control.checked){
				if(exclude != '')
					exclude += ',';
				exclude += control.value;
			}
			control.disabled = true;
		}
	}
	//form.exclude_ids.value = exclude;
	form.submit();
}

function connect(){
//	setTimeout("document.getElementById('buttonDiv').innerHTML = 'Loading..'", 100);
	if(FB.Connect && FB.Facebook && FB.Facebook.apiKey){
		FB.Connect.requireSession();
	}
	else
		setTimeout('connect()', 200);
}

function authComplete(){
	setTimeout('location.reload()', 500);
}
</script>
<?

require("$sPath/design/footer.php");

/*
// USER CONNECTED TO APPLICATION

	//facebook_client()->api_client->feed_publishUserAction($template_bundle_id, $template_data, implode(',', $target_ids), $body_general, $story_size);

	if ($_POST["comment"] != "") {
		// PUBLISH STORY TO PROFILE FEED
		$template_data = array(
			'post-title'=>idx($GLOBALS, 'sample_post_title'),
			'post-url'=>idx($GLOBALS, 'sample_post_url'),
			'post'=>$_POST["comment"]);
		$target_ids = array();
		$body_general = '';
		$publish_success = $user->fbc_publishFeedStory(idx($GLOBALS, 'feed_bundle_id'), $template_data);
		if ($publish_success) { $publish_result = "Published story via PHP to your profile feed!"; } else { $publish_result = "Error publishing story!"; }
	}

	if ($_POST["status"] != "") {
		// PUBLISH STORY TO PROFILE FEED
		$status_success = $user->fbc_setStatus($_POST["status"]);
		if ($status_success) { $status_result = "Updated your status via PHP!"; } else { $status_result = "Error updating your status!"; }
	}

	echo render_header($user);

	// SHOW FACEBOOK STATUS
	echo render_status($user, $status_result);

	// POST FEED TO PROFILE
	echo render_feed_form($user, $publish_result);

	// SHOW ALL FRIENDS
	$friends = $user->fbc_get_all_friends(TRUE);
	echo render_friends_table_html($friends, 0, 10, "fbconnect_friend", "All Friends");

	// SHOW ALL CONNECTED FRIENDS TO APPLICATION
	$friends = $user->fbc_get_connected_friends(FALSE);
	echo render_friends_table_html($friends, 0, 10, "fbconnect_friend", "Connected Friends");


	echo render_footer();
} else {
	echo render_header($user);
	echo render_footer();
}
*/
?>
