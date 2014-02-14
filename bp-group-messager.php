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
	//print_r($query);
	$posts_to_delete = array();

	while ( $query->have_posts() ) : $query->the_post(); 
		array_push($posts_to_delete, $query->post->ID);
		find_and_delete_attachments_folder($query->post->ID);
	endwhile;

	foreach ($posts_to_delete as $post) {
		wp_delete_post($post, true);
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
	$upload_dir = wp_upload_dir();
	$directory = $upload_dir['path'].'/grp_message_temp';
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
add_action( 'init', 'add_groupemailscripts' );



function send_group_email($upload_dir){
	$subject = stripslashes_deep ($_POST ['subject']);
	$content = stripslashes_deep( $_POST ['content'] );
	$user = $_POST ['user'];
	$sg_group_id = $_POST ['group'];
	$sg_groupname = $_POST ['groupname'];
	$self_send = $_POST ['self_send'];
	$nonce = $_POST ['nonce'];
	$noncecheck = check_ajax_referer( 'bp-group-message', 'nonce' );
	$groupmem = $_POST ['groupmem'];
	$tempdir = $_POST ['tempdir'];
	$attachments = $_POST['attachment'];
	$ts = time();
	// error_log(var_export($_POST,true)); 
	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];
	// error_log(var_export($upload_dir, true));
		$temp_path = $upload_dir.'/grp_message_temp/'.$tempdir;
		$end_path = $upload_dir.'/grp_message_attachments/'.$user.'-'.$ts;

		
// move attachments from tempdir to group_msg_attachment directory and delete temp directory

if ($attachments) {
	$attachments_tosend = array();
    	mkdir($end_path, 0777, true);
	foreach ($attachments as $attachment) {	

		$attachment = sanitize_file_name($attachment);
		rename($temp_path.'/'.$attachment, $end_path.'/'.$attachment);

		//create array with full path to attachment for wp_mail below
		array_push($attachments_tosend, $end_path.'/'.$attachment);

	}
//find any orphaned files in temp dir and delete
$files = glob($upload_dir.'/grp_message_temp/'.$tempdir.'/*'); // get all file names
if ($files) {
	foreach ($files as $file) { // iterate files
	  if(is_file($file))
	    unlink($file); // delete file
	}
}
// delete temp directory
rmdir($upload_dir.'/grp_message_temp/'.$tempdir);
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

wp_mail($to_field, $subject, $content, $mail_headers, $attachments_tosend);


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