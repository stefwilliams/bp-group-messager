<?php
/** sidebar-group-email.php
 *
 * @author    Stef Williams
 * @package   The Bootstrap
 * @since   1.0.0 - 05.02.2012
 */
tha_sidebars_before(); ?>
<section id="secondary" class="widget-area span4" role="complementary">
  <style type="text/css">
    /*removing buttons from WYSIWG*/
    #messagecontent_justifyleft, #messagecontent_justifycenter, #messagecontent_justifyright, #messagecontent_strikethrough {
      display: none;
    }

  </style>
<?php 



$sg_user = bp_loggedin_user_id();
$sg_group_id = bp_get_current_group_id();
$sg_group = groups_get_group(array('group_id'=>$sg_group_id));  
$ajax_nonce = wp_create_nonce("bp-group-message");
grp_msg_cleanup_temp_files(); /*cleanup any orphaned temp files*/

$groupmember = $sg_group->is_member;

if ($groupmember == 1) {
  $groupmem = 'groupmem';
} else {
  $groupmem = 'notgroupmem';
}

?>
<div id="group-contact" class="well">
  <form id="sg-group-messsage" action="" method="post">
      <h3>Contact <?php echo $sg_group->name; ?></h3>
      <?php if (!is_user_logged_in()) {
        echo "You need to be logged in.";
        goto grp_email_end;
      } ?>
    <fieldset id="fieldset">
<?php $ts = time();?>
    <input type="hidden" name="nonce" id="nonce" value="<?php echo $ajax_nonce; ?>">  
    <input type="hidden" name="tempdir" id="tempdir" value="<?php echo wp_create_nonce($ts);?>">
    <input type="hidden" name="groupmem" id="groupmem" value="<?php echo $groupmem; ?>">  
    <input type="hidden" name="userid" id="userid" value="<?php echo $sg_user; ?>"> 
    <input type="hidden" name="groupname" id="groupname" value="<?php echo $sg_group->name; ?>">
    <input type="hidden" name="groupid" id="groupid" value="<?php echo $sg_group_id; ?>">
    <label for="subject" id="subject-label">Subject</label>
    <p class="text-error" for="subject" id="subject_error" style="display:none;">Please enter a subject.</p>
    <input type="text" name="subject" id="subject" class="input-block-level" ></input>
    <label for="message" id="subject-label">Message</label>
    <p class="text-error" for="content" id="content_error" style="display:none;">You didn't type in a message!</p>
    <!-- <textarea name="content" id="content" rows="15" class="input-block-level tinymce_data" ></textarea> -->
    <?php 
    $editor_settings = array(
      'media_buttons' => false,
      'teeny' => true,
      'textarea_name' => 'messagecontent',
      );
    wp_editor('', 'messagecontent', $editor_settings); ?>
    <br />
        <div id="upload" data-action="<? echo plugins_url ('bp-group-messager').'/ajax_upload.php' ?>">
            <div id="drop">
                Drop Attachments Here

                <a>Browse</a>
                <input type="file" name="upl" multiple />
            </div>

            <ul>
                <!-- The file uploads will be shown here -->
            </ul>

        </div>

    <hr />
    <label class="checkbox">Send a copy to yourself<input type="checkbox" name="self_send" value="send" id="self_send"/></label>
    <button type="submit" class="btn btn-inverse btn-block" id="sendgroupmail">Send Email</button>
    </fieldset> 
    <?php 
grp_email_end:
?>
  </form>
  <div class="ajaxsending" style="display:none;"></div>
<div class="ajaxsend" style="display:none;"></div>
</div>


  <?php 
  tha_sidebar_bottom(); ?>
</section><!-- #secondary .widget-area -->
<?php tha_sidebars_after();
