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

include ('cpt-sg-grp-msg.php');

add_action('plugins_loaded', 'delete_old_grp_messages'); //deletes old mesages and attachments

add_action( 'before_delete_post', 'before_delete_grp_messages' ); //deletes attachments if messages manually deleted in the backend.

function before_delete_grp_messages( $postid ){
    // We check if the global post type isn't ours and just return
	global $post_type;   
	if ( $post_type != 'sg_grp_msg' ) return;
	else {
		find_and_delete_attachments_folder($postid);
	}
	
}

function delete_old_grp_messages() {
	$expirydate = mktime(0, 0, 0, date("m")-1, date("d"), date("Y")); //one month expiry
	$args = array(
		'post_type' => 'sg_grp_msg',
		'date_query' => array(
			array(
				'before'    => array(
					'year'  => date("Y" ,$expirydate),
					'month' => date("m" ,$expirydate),
					'day'   => date("d" ,$expirydate),
					),
				'inclusive' => true,
				),
			),
		'posts_per_page' => -1,
		);
	$query = new WP_Query( $args );
	$posts_to_delete = array();
	if ($query->found_posts > 0) {
		$posts_found = $query->posts;
		foreach ($posts_found as $post_found) {
			array_push($posts_to_delete, $post_found->ID);
			find_and_delete_attachments_folder($post_found->ID);
		}
	// while ( $query->have_posts() ) : $query->the_post(); 
	// 	array_push($posts_to_delete, $query->post->ID);
	// 	find_and_delete_attachments_folder($query->post->ID);
	// endwhile;

		foreach ($posts_to_delete as $post) {
			wp_delete_post($post, true);
		}
	}
	// Reset Post Data
	wp_reset_postdata();
	// print_r($posts_to_delete);
	
}

function find_and_delete_attachments_folder($post_id) {
	$attachments = get_post_meta ($post_id, 'attachments',true);
	if($attachments){
		$attachment_folders_to_delete = array();
		foreach ($attachments as $attachment){
			$attachment_folder = dirname($attachment);
			// print_r($attachment_folder);
			array_push($attachment_folders_to_delete, $attachment_folder);
		}
		array_unique($attachment_folders_to_delete);
		// print_r($attachment_folders_to_delete);
	// error_log(var_export($attachment_folders_to_delete, true));
		delete_folders_and_contents($attachment_folders_to_delete);
	}
	// print_r($attachment_folders_to_delete);
}

function grp_msg_cleanup_temp_files() {
	$seconds_old = 43200; /*12 hours*/
	$bp_grp_upload_dir = wp_upload_dir();
	$directory = $bp_grp_upload_dir['path'].'/grp_message_temp';
	$to_delete = array();

	if (is_dir($directory)) 
	{
		$objects = scandir($directory);
		foreach ($objects as $object) 
		{
			if ($object != "." && $object != "..") 
			{     
				/*find folders older than $seconds_old and record them in $to_delete array*/       
				if (filemtime($directory."/".$object) <= time()-$seconds_old) array_push($to_delete, $directory."/".$object);
			}
		}
		reset($objects);
        //rmdir($directory);
	}

	/*delete all files in $to_delete folders, then delete folder*/
	if ($to_delete) {
		delete_folders_and_contents($to_delete);
	}
}

function delete_folders_and_contents ($to_delete) {
	foreach ($to_delete as $folder) {
			$files = glob($folder.'/*'); // get all file names
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
			rmdir($folder);
		}
	}

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

	function add_groupemailscripts(){
		global $bp;
		$sg_is_groups = $bp -> current_component;
		$sg_is_single = $bp -> is_single_item;
		if (($sg_is_groups == 'groups') && ($sg_is_single == '1')) {
			$pluginsurl = plugins_url ('bp-group-messager');
			wp_enqueue_script( 'groupemail', $pluginsurl.'/js/groupemail.js', array( 'jquery' ) );
			wp_localize_script( 'groupemail', 'ajax_object',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
    //ajax upload scripts
			wp_register_style( 'googlefonts', 'http://fonts.googleapis.com/css?family=PT+Sans+Narrow:400,700');
			wp_enqueue_style('googlefonts');
			wp_register_style( 'file_upload_css', $pluginsurl.'/css/file_upload.css');
			wp_enqueue_style('file_upload_css');
			wp_enqueue_script( 'jquery_knob', $pluginsurl.'/js/jquery.knob.js', array( 'jquery' ), '1.2.0', true );
			wp_enqueue_script( 'jquery_iframe', $pluginsurl.'/js/jquery.iframe-transport.js', array( 'jquery' ), '1.6.1', true );
			wp_enqueue_script( 'jquery_upload', $pluginsurl.'/js/jquery.fileupload.js', array( 'jquery' ), '5.26', true );
			wp_enqueue_script( 'jquery_attachment', $pluginsurl.'/js/attachment.js', array( 'jquery' ), '0.1', true );
			wp_localize_script( 'jquery_attachment', 'ajax_upload',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
		}
	}
	add_action( 'wp_print_scripts', 'add_groupemailscripts' );



	function bp_messager_mail_from($old) {
	//get details of sender
	// $user = get_current_user_id();
	// $user_object = get_userdata($user);
	// $user_email = $user_object->user_email;
 // return $user_email;
		return 'noreply@sambagalez.info';
	}
	function bp_messager_mail_from_name($old) {
	//get details of sender
		$user = get_current_user_id();
		$user_object = get_userdata($user);
		$user_name = $user_object->display_name;
		return $user_name.' via the Samba Website';
	}



	function send_group_email($bp_grp_upload_dir){
		global $bp;
		$subject = stripslashes_deep ($_POST ['subject']);
		$content = stripslashes_deep( $_POST ['content'] );
		$user = $_POST ['user'];
		$sg_group_id = $_POST ['group'];
	//get URL of group
		$group_array = groups_get_group( array( 'group_id' => $sg_group_id ) );
		$group_url = home_url( $bp->groups->slug . '/' . $group_array -> slug );

		$sg_groupname = $_POST ['groupname'];
		$self_send = $_POST ['self_send'];
		$nonce = $_POST ['nonce'];
		$noncecheck = check_ajax_referer( 'bp-group-message', 'nonce' );
		$groupmem = $_POST ['groupmem'];
		$tempdir = $_POST ['tempdir'];
		if (isset($_POST['attachment'])) {
			$attachments = $_POST['attachment'];
		}	
		$ts = time();
	// error_log(var_export($_POST,true)); 
		$bp_grp_upload_dir = wp_upload_dir();
		$bp_grp_upload_dir = $bp_grp_upload_dir['basedir'];
	// error_log(var_export($bp_grp_upload_dir, true));
		$temp_path = $bp_grp_upload_dir.'/grp_message_temp/'.$tempdir;
		$end_path = $bp_grp_upload_dir.'/grp_message_attachments/'.$user.'-'.$ts;

		
// move attachments from tempdir to group_msg_attachment directory and delete temp directory

		if (isset($attachments)) {
			$attachments_tosend = array();
			mkdir($end_path, 0777, true);
			foreach ($attachments as $attachment) {	

				$sanitized_attachment = sanitize_file_name($attachment);
				$attachment_parts = pathinfo($sanitized_attachment);
				$lowcase_extension = strtolower($attachment_parts['extension']);
				$attachment = $attachment_parts['filename'].'.'.$lowcase_extension;

				error_log(var_export($attachment_parts, true));
		// wp_handle_upload( $file, $overrides, $time );

				rename($temp_path.'/'.$attachment, $end_path.'/'.$attachment);

		//create array with full path to attachment for wp_mail below
				array_push($attachments_tosend, $end_path.'/'.$attachment);

			}
//find any orphaned files in temp dir and delete
$files = glob($bp_grp_upload_dir.'/grp_message_temp/'.$tempdir.'/*'); // get all file names
if ($files) {
	foreach ($files as $file) { // iterate files
		if(is_file($file))
	    unlink($file); // delete file
}
}
// delete temp directory
rmdir($bp_grp_upload_dir.'/grp_message_temp/'.$tempdir);
}
else {
	$attachments_tosend = NULL;
}
	//





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
$sg_group_admins = groups_get_group_admins ($sg_group_id);	
$sg_group_mods = groups_get_group_mods ($sg_group_id);	
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


$boundary = "--00multipartboundary00";
//insert header for HTML emails
add_filter('wp_mail_content_type',function($content_type){
	return 'multipart/alternative;
	boundary="00multipartboundary00"';
});

// create 'to' field
// $to_field = $sg_groupname . '<noreply@sambagalez.info>';
$to_field = $sg_groupname." <".$group_array -> slug."@sambagalez.info>";

	//get details of sender
$user_object = get_userdata($user);
$user_email = $user_object->user_email;
$user_name = $user_object->display_name;


//define EOL tags in $content
$newlineTags = array(
	'<br>',
	'<br/>',
	'<br />',
	'</p>',
	);

//remove tags and replace with '\n' or reply-to EOL character
$newline_content = str_replace($newlineTags, PHP_EOL, $content);
// $newline_replyto = str_replace($newlineTags, '%0D%0A ', $content);

//strip all other tags
$newline_textcontent = wp_strip_all_tags( $newline_content, false);
// $newline_textreplyto = wp_strip_all_tags( $newline_replyto, false);

//encode as quoted printable
$textcontent = quoted_printable_encode($newline_textcontent);
$url_textreplyto = rawurlencode( $newline_textcontent );
$textreplyto = quoted_printable_encode($url_textreplyto);

// create mail headers
// $mail_headers[] = 'From:'.$user_name.'<noreply@sambagalez.info>'."\r\n";
// $mail_headers[] = 'Reply-to:'.$user_name.'<'.$user_email.'>'."\r\n";
$mail_headers[] = 'Bcc:'.implode( ",", $sg_all_group_emails );


//wysiwyg content converted to 8bit then qp (just qp did not work for some reason)
// $content_8bit = utf8_encode($content);
$content_qp = quoted_printable_encode($content);
$time_string = date('D, M j, Y \a\t g:i A');

//wrapping text has = signs as =3D to comply with quoted-printable encoding
//also appears that line-break between <html> adn <body> is critical for iOS mail to show content.
//also need NO <head> section, else it fails... what a palaver
//%0D%0A is line break
$mailcontent = 
$boundary.'
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable
'.$textcontent.'

'.$boundary.'
Content-type: text/html; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" =
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html =
xmlns=3D"http://www.w3.org/1999/xhtml"> =
<head> =
<meta http-equiv=3D"Content-Type" content=3D"text/html; charset=3DUTF-8" =
/> =
</head> =
<body> = 
'
.$content_qp.' =
<hr /><p>This message was sent via the Samba Gal&ecirc;z website by =
<strong>'.$user_name.'</strong></p> =
<p>To reply directly, please do not use your normal reply as IT WILL NOT WORK, =
<a href=3D"mailto:=
'.$user_email.'?subject=3DRe:'.$subject.'&body=3D=
%0D%0A %0D%0AOn =
'.$time_string.', =
'.$user_name.' =
<'.$user_email.'> said: %0D%0A=
'.$textreplyto.' =
">try this link</a> instead.</p> =
<p> To message all recipients in '.$sg_groupname.', =
please use the form on the <a href=3D"'.$group_url.'">group page</a>.</p> =
<hr /> =
</body> =
</html> =
'.$boundary.'--';
// $mailcontent_8bit = utf8_encode($mailcontent);
// $mailcontent_qp = quoted_printable_encode($mailcontent);
// $mailcontent_type = mb_detect_encoding($content);

add_filter('wp_mail_from', 'bp_messager_mail_from');
add_filter('wp_mail_from_name', 'bp_messager_mail_from_name');
wp_mail($to_field, $subject, $mailcontent, $mail_headers, $attachments_tosend);
remove_filter('wp_mail_from', 'bp_messager_mail_from');
remove_filter('wp_mail_from_name', 'bp_messager_mail_from_name');


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
$attachments_sent = update_post_meta($grp_msg_id, 'attachments', $attachments_tosend);


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