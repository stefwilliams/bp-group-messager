<?php
$server_root = $_SERVER['DOCUMENT_ROOT'];

require (
$server_root.'/wp-load.php');
require (
$server_root.'/wp-admin/includes/file.php');


function change_upload_dir($upload_dir) {
    error_log('change_upload_dir called');

	//create directory for upload if not exists

// $wp_upload_dir = wp_upload_dir();

if (!file_exists($upload_dir.'/grp_message_attachments')) {
    mkdir($upload_dir.'/grp_message_attachments', 0777, true);
}


    $upload_dir['subdir'] = '/grp_message_attachments' . $upload_dir['subdir'];
    $upload_dir['path']   = $upload_dir['basedir'] . $upload_dir['subdir'];
    $upload_dir['url']    = $upload_dir['baseurl'] . $upload_dir['subdir'];
    return $upload_dir;
}


	// A list of permitted file extensions
	$allowed = array('png', 'jpg', 'gif','zip');

	if(isset($_FILES['upl']) && $_FILES['upl']['error'] == 0){
	    $extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);
    
    error_log('files found');




	    if(!in_array(strtolower($extension), $allowed)){
	        echo '{"status":"error"}';
	        exit;
	    }

        add_filter( 'upload_dir', 'change_upload_dir' );
        $upload = wp_handle_upload( $_FILES['upl'], array('test_form' => false) );
        remove_filter( 'upload_dir', 'change_upload_dir' );


	    if(move_uploaded_file($_FILES['upl']['tmp_name'], 'uploads/'.$_FILES['upl']['name'])){
	        echo '{"status":"success"}';
	        exit;
	    }
	}

	echo '{"status":"error"}';
	exit;


// add_action( 'wp_ajax_ajax_upload', 'ajax_upload' );
?>