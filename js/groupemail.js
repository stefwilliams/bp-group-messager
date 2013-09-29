jQuery(document).ready(function() {
    jQuery('#sendgroupmail').click(function() {
        var sg_user = jQuery('input#userid').val();
        var sg_group_id = jQuery('input#groupid').val();
        var groupname = jQuery('input#groupname').val();
        var subject = jQuery('input#subject').val();
        var content = tinyMCE.get('messagecontent').getContent();
        var groupmem = jQuery('input#groupmem').val();
        var nonce = jQuery('input#nonce').val();
      if (subject == "") {
        jQuery('p#subject_error').attr('style', 'display:block;');
        jQuery('input#subject').focus();
        return false;
      }
      if (content == "") {
        jQuery('p#content_error').attr('style', 'display:block;');
        jQuery('textarea#content').focus();
        return false;
      }
      if (jQuery ('#self_send').prop('checked')) {
          var sg_self_send = 'send';
      } else {
        var sg_self_send = 'nosend';
      } 

    jQuery ('fieldset#fieldset').attr('style','display:none;');
    jQuery ('.ajaxsending').attr('style','display:block;');
    jQuery ('.ajaxsending').delay(100).append ('<p>Going...</p>');
    
    var data = {
    action: "sendgroupemail",
    subject: subject,
    content: content,
    user: sg_user,
    group: sg_group_id,
    groupname: groupname,
    groupmem: groupmem,
    self_send: sg_self_send,
    nonce: nonce
  };
  jQuery.post(ajax_object.ajax_url, data, function(response) {
    jQuery ('.ajaxsending').attr('style','display:none;');
    jQuery ('.ajaxsend').attr('style','display:block;');
    jQuery ('.ajaxsend').append ('<p>Your message "'  + response + '" was sent.</p>');
  });
return false;


    });
  });

