jQuery(document).ready(function() {
    jQuery('#sendgroupmail').click(function(e) {
        var sg_user = jQuery('input#userid').val();
        var sg_group_id = jQuery('input#groupid').val();
        var groupname = jQuery('input#groupname').val();
        var subject = jQuery('input#subject').val();
        var content = tinyMCE.get('messagecontent').getContent();
        var groupmem = jQuery('input#groupmem').val();
        var nonce = jQuery('input#nonce').val();
        var tempdir = jQuery('input#tempdir').val();
        // var attachment = jQuery('input#attachment').val();
        var attachment = jQuery("input.attachment").map(function(){return jQuery(this).attr('data-filename');}).get();
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
    // jQuery ('.ajaxsending').delay(10).append ('<p>Going...</p>');
      var interval=setInterval(function(){
        jQuery ('.ajaxsending').append ('<p>Going...</p>'); 
        if (jQuery ('.ajaxsend').css('display') == "block") {
          clearInterval(interval);
        }
      }, 500);

    
    var data = {
    action: "sendgroupemail",
    subject: subject,
    content: content,
    user: sg_user,
    group: sg_group_id,
    groupname: groupname,
    groupmem: groupmem,
    self_send: sg_self_send,
    tempdir: tempdir,
    attachment: attachment,
    nonce: nonce
  };
  jQuery.post(ajax_object.ajax_url, data, function(response) {
    // jQuery ('.ajaxsending').attr('style','display:none;');
    // console.log(response);
    jQuery ('.ajaxsend').attr('style','display:block;');
    jQuery ('.ajaxsend').append ('<p>Gone!</p><p>Your message:</p><label> "'  + response + '"</label><p>was sent.</p>');
  });
return false;
e.preventDefault();

    });
  });

