<?php
//Register post type
add_action( 'init', 'register_sg_grp_msg' );

function register_sg_grp_msg() {
  register_post_type( 'sg_grp_msg',
    array(
      'labels' => array(
        'name' => __('Group Messages','lmw'),
        'singular_name' => 'Message',
        'add_new_item' => 'Add New Message',
        'menu_name' => 'Group Messages', //<== make this the root content type for all Message Hub items
        'all_items' => _x('Messages','lmw'),
        'edit_item' => 'Edit Message',
        'new_item' => 'New Message',
        'view_item' => 'View Message',
        'search_items' => 'Search Messages',
        'not_found' => 'No Messages found',
        'not_found_in_trash' => 'No Messages found in Trash',
      ),
    'public' => false,
    'has_archive' => false,
    'rewrite' => false,
    'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields', 'comments')
    )
  );
}
?>