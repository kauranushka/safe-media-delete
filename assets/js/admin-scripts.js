jQuery(function($){
    $('body').on('click', '.aw_upload_image_button', function(e){
        e.preventDefault();
        aw_uploader = wp.media({
            title: 'Custom image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        }).on('select', function() {
            var attachment = aw_uploader.state().get('selection').first().toJSON();            
            $('.smcp-taxo-img').attr( 'src', attachment.url ).show();
            $('#cat-image-id').val(attachment.id);
        })
        .open();
    });

    $('body').on('click', '.delete-attachment', function(e) {
        e.preventDefault();

        // get the URL parameter called "param"
        const urlParams = new URLSearchParams(window.location.search);
        const post_id = urlParams.get('item');
        
        if(post_id){
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : smcp_ajax_object.ajax_url,
                data : { 
                    action: "smcp_prevent_delete_attachment", 
                    id : post_id,
                },
                success: function(response) {                    
                    if( response.result == true ) {
                        alert(response.message);
                    }
                }
            });    
        }
    });

    $('body.taxonomy-category form#addtag #submit').click(function(){
        setTimeout(function(){            
            $('.smcp-taxo-img').attr( 'src', '' ).hide();
        }, 1000);
    });
});
