<?php
$server_root = $_SERVER['DOCUMENT_ROOT'];

require (
$server_root.'/wp-load.php');
require (
$server_root.'/wp-admin/includes/file.php');


function change_upload_dir($upload_dir) {
	    $tmp_dir = $_POST['tempdir'];
	    // error_log(var_export($tmp_dir, true));
	//create directory for upload if not exists
// $wp_upload_dir = wp_upload_dir();
// if (!file_exists($upload_dir.'/grp_message_temp/'.$tmp_dir)) {
//     mkdir($upload_dir.'/grp_message_temp/'.$tmp_dir, 0777, true);
// }
    $upload_dir['subdir'] = '/grp_message_temp/'.$tmp_dir . $upload_dir['subdir'];
    $upload_dir['path']   = $upload_dir['basedir'] . $upload_dir['subdir'];
    $upload_dir['url']    = $upload_dir['baseurl'] . $upload_dir['subdir'];
    return $upload_dir;
}


// A list of permitted file extensions
$allowed = array('png', 'jpg', 'jpeg', 'gif','zip', 'pdf', 'doc', 'docx', 'mp3');
$allowed_string = implode(', ', $allowed);

if(isset($_FILES['upl']) && $_FILES['upl']['error'] == 0){
    $extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);
    
    
    if(!in_array(strtolower($extension), $allowed)){
        // error_log(var_export($_FILES, true));
        $filename = $_FILES['upl']['name'];
    	// error_log("disallowed extension");
        // header('Content-type: application/json');
        echo '{"status":"bad_file", "allowed":"'.$allowed_string.'", "filename":"'.$filename.'"}';
        // echo '{"filename":"'.$_FILES['upl'].'"'};
        // echo '{"":"'.$allowed_string.'"}';
        exit;
    }

    else {
        add_filter( 'upload_dir', 'change_upload_dir');
        $upload = wp_handle_upload( $_FILES['upl'], array('test_form' => false) );
        remove_filter( 'upload_dir', 'change_upload_dir' );
        echo '{"status":"success"}';
        exit;
   	}


	    // if(move_uploaded_file($_FILES['upl']['tmp_name'], 'uploads/'.$_FILES['upl']['name'])){
	    //     echo '{"status":"success"}';
	    //     exit;
	    // }
}

echo '{"status":"error"}';
exit;


// add_action( 'wp_ajax_ajax_upload', 'ajax_upload' );
?>