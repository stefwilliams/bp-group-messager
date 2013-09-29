<?php
/*
Plugin Name: Buddypress Group Messager
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Add a widget to Buddypress Groups pages with a contact form. Sends Buddypress messages and wp_mail to all the group members
Version: 1.1
Author: Stef Williams
Author URI: http://URI_Of_The_Plugin_Author
License: GPL2
*/

/* get if code from sidebar.php and make it point to new sidebar in widgets folder
Rework sidebar code to point to admin ajax, and copy js code to js folder...
use: http://www.colinjmorgan.com/using-ajax-in-wordpress/
and: http://net.tutsplus.com/tutorials/javascript-ajax/submit-a-form-without-page-refresh-using-jquery/
http://codex.wordpress.org/AJAX_in_Plugins
for reference
*/

function add_groupemail_sidebar() 
{
	global $bp;
	$sg_is_groups = $bp -> current_component;
	$sg_is_single = $bp -> is_single_item;
		if (($sg_is_groups == 'groups') && ($sg_is_single == '1')) {
	    include ('sidebar-group-email.php');
	}
}
add_action( 'get_sidebar', 'add_groupemail_sidebar' );

function add_groupemailscript(){
	$pluginsurl = plugins_url ('bp-group-messager');
    wp_enqueue_script( 'groupemail', $pluginsurl.'/js/groupemail.js', array( 'jquery' ) );
    wp_localize_script( 'groupemail', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
}
add_action( 'init', 'add_groupemailscript' );

function send_group_email(){
	$subject = $_POST ['subject'];
	$content = $_POST ['content'];
	$user = $_POST ['user'];
	$sg_group_id = $_POST ['group'];
	$sg_groupname = $_POST ['groupname'];
	$self_send = $_POST ['self_send'];
	$nonce = $_POST ['nonce'];
	$noncecheck = check_ajax_referer( 'bp-group-message', 'nonce' );
	$groupmem = $_POST ['groupmem'];

//set up the message to send
global $wpdb;
global $bp;
$pluginsurl = plugins_url('buddypress');
// include ($pluginsurl.'/bp-messages/bp-messages-functions.php');
$sg_group_members = groups_get_group_members ( 
	array(
		'group_id'=>$sg_group_id
		//'exclude_admins_mods'=>false (doesn't seem to work)
		)
	);	
$sg_group_members = $sg_group_members['members'];
$sg_group_admins = groups_get_group_admins ( 
	array(
		'group_id'=>$sg_group_id
		)
	);	
$sg_group_mods = groups_get_group_mods ( 
	array(
		'group_id'=>$sg_group_id
		)
	);	
$sg_all_group_members = array();
foreach ($sg_group_members as $member) {
	array_push($sg_all_group_members, $member->user_id);
}
foreach ($sg_group_admins as $member) {
	array_push($sg_all_group_members, $member->user_id);
}
foreach ($sg_group_mods as $member) {
	array_push($sg_all_group_members, $member->user_id);
}

//process send/nosend and member/nonmemb options
//if (non-member AND nosend) OR (member AND send) we don't need to do anythig and continue with full array

//if (member AND nosend) we need to remove member id from array
if (($groupmem == 'groupmem') AND ($self_send == 'nosend')) {
	$sg_all_group_members = array_diff($sg_all_group_members, array($user));
}

//if (non-member AND send) we need to add member to array
if (($groupmem == 'notgroupmem') AND ($self_send == 'send')) {
	array_push($sg_all_group_members, $user);
}

//sg_group_email ();

//SEND MAILS

//get email addresses of all members
$sg_all_group_emails = array();
foreach ($sg_all_group_members as $member) {
	$member_object = get_userdata($member);
	$member_email = $member_object->user_email;
	array_push($sg_all_group_emails, $member_email);
}

//insert header for HTML emails
add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));

// create 'to' field
$to_field = $sg_groupname . '<noreply@sambagalez.info>';

//get details of sender
$user_object = get_userdata($user);
$user_email = $user_object->user_email;
$user_name = $user_object->display_name;

// create mail headers
$mail_headers[] = 'From:'.$user_name.'<'.$user_email.'>'."\r\n";
$mail_headers[] = 'Bcc:'.implode( ",", $sg_all_group_emails );

wp_mail($to_field, $subject, $content, $mail_headers);

// $msg_args = 	array (
// 		'sender_id' => $user,
// 		'recipients' => $sg_all_group_members,
// 		'subject' => $subject,
// 		'content' => $content
// 		);



// messages_new_message ($msg_args);

// return values to	sidebar form
		$success = $subject;
		if(!empty($success)) {
			echo $success;
		} else {
			echo 'There was a problem. Please try again.';
		}
	die();
}

// create custom Ajax call for WordPress
//add_action( 'wp_ajax_nopriv_sendgroupemail', 'send_group_email' );
add_action( 'wp_ajax_sendgroupemail', 'send_group_email' );