// Ajax upload scripts taken from : http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/
// v.0.1


jQuery(function(){

    var ul = jQuery('#upload ul');

    jQuery('#drop a').click(function(){
        // Simulate a click on the file input button
        // to show the file browser dialog
        jQuery(this).parent().find('input').click();
    });

    // Initialize the jQuery File Upload plugin
    jQuery('#upload').fileupload({

        // This element will accept file drag/drop uploading
        dropZone: jQuery('#drop'),
        // the path to the upload handler file
        url: jQuery('#upload').data('action'),       
        dataType: 'json',

        // This function is called when a file is added to the queue;
        // either via the browse button, or via drag/drop:
        add: function (e, data) {

            // data.tempdir = jQuery('#upload').data('temp');

            var tpl = jQuery('<li class="working"><input class="attachment" data-filename="empty" type="text" value="0" data-width="48" data-height="48"'+
                ' data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043" name="attachment[]" /><p class="filename"></p><span></span></li>');

            // Append the file name and file size
            tpl.find('p.filename').text(data.files[0].name)
                         .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

                         tpl.find('input.attachment').attr('data-filename', data.files[0].name);

            // Add the HTML to the UL element
            data.context = tpl.appendTo(ul);

            // Initialize the knob plugin
            tpl.find('input').knob();

            // Listen for clicks on the cancel icon
            tpl.find('span').click(function(){

                if(tpl.hasClass('working')){
                    jqXHR.abort();
                }

                tpl.fadeOut(function(){
                    tpl.remove();
                });

            });

            // Automatically upload the file once it is added to the queue
            var jqXHR = data.submit();
            // var response = jqXHR.responseText;
            // console.log(data);
            // console.log(jqXHR);
            // console.log(response);
        },

        progress: function(e, data){

            // Calculate the completion percentage of the upload
            var progress = parseInt(data.loaded / data.total * 100, 10);

            // Update the hidden input field and trigger a change
            // so that the jQuery knob plugin knows to update the dial
            data.context.find('input').val(progress).change();

            if(progress == 100){
                data.context.removeClass('working');
            }
        },

        done: function(e, data){
            var response = data.jqXHR.responseText;
            // var upload = jQuery()
            var jresponse = JSON.parse(response);
            // console.log(jresponse);
            if (jresponse.status=="bad_file") {
                var target = data.context.find('input.attachment').attr('data-filename', jresponse.filename);
                target.siblings('canvas').remove();
                var parentli = target.parent().parent('li');
                parentli.html('<p class="error"><strong>'+ jresponse.filename +'</strong> This filetype is not allowed.<br /> Allowed filetypes are: <br />' + jresponse.allowed + '</p>');
            };
        },

        fail:function(e, data){
            // Something has gone wrong!
            data.context.addClass('error');
            // console.log(e);
            // console.log(data);
        }

    });


    // Prevent the default action when a file is dropped on the window
    jQuery(document).on('drop dragover', function (e) {
        e.preventDefault();
    });

    // Helper function that formats the file sizes
    function formatFileSize(bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }

        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        }

        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        }

        return (bytes / 1024).toFixed(2) + ' KB';
    }

});