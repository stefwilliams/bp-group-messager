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
include ('cpt-sg-grp-msg.php');

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

function change_upload_dir($upload_dir) {

	//create directory for upload if not exists

if (!file_exists('/grp_message_attachments')) {
    mkdir('/grp_message_attachments', 0777, true);
}


    $upload_dir['subdir'] = '/grp_message_attachments' . $upload_dir['subdir'];
    $upload_dir['path']   = $upload_dir['basedir'] . $upload_dir['subdir'];
    $upload_dir['url']    = $upload_dir['baseurl'] . $upload_dir['subdir'];
    return $upload_dir;
}

function send_group_email(){
	$subject = $_POST ['subject'];
	$content = stripslashes_deep( $_POST ['content'] );
	$user = $_POST ['user'];
	$sg_group_id = $_POST ['group'];
	$sg_groupname = $_POST ['groupname'];
	$self_send = $_POST ['self_send'];
	$nonce = $_POST ['nonce'];
	$noncecheck = check_ajax_referer( 'bp-group-message', 'nonce' );
	$groupmem = $_POST ['groupmem'];
	$attachments = $_POST['file_upload'];
error_log(var_export($_POST,true)); 

// UPLOAD attachments to custom group_msg_attachment directory


	//

    if (is_array($attachments)) {
        // error_log('if is array?');
        foreach ($attachments as $attachment) {

            if($_FILES[$attachment]['size'] > 0 ) {
                // error_log('if not empty?');
                $file = $_FILES[$attachment];

                add_filter( 'upload_dir', 'change_upload_dir' );
                $upload = wp_handle_upload( $file, array('test_form' => false) );
                remove_filter( 'upload_dir', 'change_upload_dir' );

                if(!isset($upload['error']) && isset($upload['file'])) {

                    $upload = array_merge($upload, array('filesize'=>$file[size]));

                    $upload = array_merge($upload, array('name'=>$file[name]));




                }
            }         

        }
    }



// end upload attachment code

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
$mail_headers[] = 'Cc:'.implode( ",", $sg_all_group_emails );

wp_mail($to_field, $subject, $content, $mail_headers, $attachments);


//INSERT CPT into database to allow sent emails to be reviewed

$post_args = array(
	'post_title'	=> $subject,
	'post_content'	=> $content,
	'post_author'	=> $user,
	'post_type'		=> 'sg_grp_msg',
	'post_status'	=> 'publish',
	);


$grp_msg_id = wp_insert_post($post_args, true);

//THEN UPDATE POST META with other info
$g_id_update = update_post_meta($grp_msg_id, 'group_id', $sg_group_id);
$g_name_update = update_post_meta($grp_msg_id, 'group_name', $sg_groupname);
$mem_sent_update = update_post_meta($grp_msg_id, 'sent_by_member', $groupmem);
$self_sent_update = update_post_meta($grp_msg_id, 'sent_to_self', $self_send);


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