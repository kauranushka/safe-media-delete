<?php

// term add form
add_action( 'category_add_form_fields', 'smcp_add_category_image', 10, 2 );
function smcp_add_category_image ( $taxonomy ) {
?>
    <div class="form-field term-image-wrap">
        <label for="cat-image"><?php _e( 'Image' ); ?></label>
        <p><a href="#" class="aw_upload_image_button button button-secondary"><?php _e('Upload Image'); ?></a></p>        
        <img src="" style="display:none" class="smcp-taxo-img" width="auto" height="100px" />
        <input type="hidden" name="category_image" id="cat-image-id" value="" />
    </div>
<?php
}

// term edit form
add_action( 'category_edit_form_fields', 'smcp_update_category_image', 10, 2 );
function smcp_update_category_image ( $term, $taxonomy ) { 
  $image_id = get_term_meta($term->term_id, 'category_image', true);
  $image = wp_get_attachment_url($image_id);
?>
    <tr class="form-field term-image-wrap">
        <th scope="row"><label for="category_image"><?php _e( 'Image' ); ?></label></th>
        <td>
            <p><a href="#" class="aw_upload_image_button button button-secondary"><?php _e('Upload Image'); ?></a></p><br/>            
            <?php if($image) { ?>
                <img src="<?php echo esc_url($image); ?>" class="smcp-taxo-img" width="auto" height="100px" />
            <?php } ?>                
            <input type="hidden" name="category_image" id="cat-image-id" value="<?php echo $image_id; ?>" />
        </td>
    </tr>
<?php
}

// save custom terms data 
function smcp_save_taxonomy_custom_meta_field( $term_id ) {
  if ( isset( $_POST['category_image'] ) ) {
      update_term_meta($term_id, 'category_image', $_POST['category_image']);

  }
}  
add_action( 'edited_category', 'smcp_save_taxonomy_custom_meta_field', 10, 2 );  
add_action( 'created_category', 'smcp_save_taxonomy_custom_meta_field', 10, 2 );

// Add new column header 
add_filter( 'manage_media_columns', 'smcp_custom_media_column_header' );

function smcp_custom_media_column_header( $columns ) {
  $new_columns = array();
  foreach ( $columns as $column_name => $column_title ) {
    $new_columns[$column_name] = $column_title;
    if ( $column_name == 'parent' ) {
      $new_columns['linked_object'] = 'Attached Objects';
    }
  }
  return $new_columns;
}

// Add content to the new column
add_action( 'manage_media_custom_column', 'smcp_custom_media_column_content', 10, 2 );

function smcp_custom_media_column_content( $column_name, $media_id ) {
    if ( $column_name == 'linked_object' ) {       

        global $wpdb;

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'meta_key' => '_thumbnail_id',
            'meta_value' => $media_id,
            'fields' => 'ids',            
        );
        
        $post_ids = get_posts( $args );        
        
        if($post_ids){
            foreach ( $post_ids as $post ) {                
                $link = get_edit_post_link($post);
                if($link){
                    echo '<a href="' . $link . '">' . $post . '</a>,';
                }
            }
        }

        $term_ids = $wpdb->get_col( "SELECT DISTINCT `term_id` FROM {$wpdb->prefix}termmeta WHERE `meta_key` = 'category_image' AND `meta_value` = $media_id " );
        
        if($term_ids){
            foreach ( $term_ids as $term ) {
                $link = get_edit_term_link( $term, 'category' );
                if($link) {
                    echo '<a href="' . $link . '">' . $term . '</a>,';
                }
            }
        }    
    }
}

function smcp_prevent_delete_attachment_func() {
    
    // Get all term IDs that have the media attached
    $attachment_id = $_POST['id'];  

    if(empty($attachment_id)){
        return;
    }
    
    if( !empty( $attachment_id )) {

        global $wpdb;
        $terms = $wpdb->get_col( "SELECT DISTINCT `term_id` FROM {$wpdb->prefix}termmeta WHERE `meta_key` = 'category_image' AND `meta_value` = $attachment_id " );    
    
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'meta_key' => '_thumbnail_id',
            'meta_value' => $attachment_id,
            'fields' => 'ids',            
        );
        
        $posts = get_posts( $args );   

        if ( !empty($posts) || !empty($terms) ) {           

            wp_send_json( array(
                'result' => true,
                'message' => 'Sorry, you cannot delete this attachment as it is attached to a post or terms.'
            ));                        
        } 
    } else {
		wp_send_json( array(
			'result' => false,
			'message' => 'attachment Id not found.'
		));
	}
    wp_die();
}

add_action( 'wp_ajax_smcp_prevent_delete_attachment', 'smcp_prevent_delete_attachment_func', 1 );
add_action( 'wp_ajax_nopriv_smcp_prevent_delete_attachment', 'smcp_prevent_delete_attachment_func', 1 );

function smcp_wp_prevent_media_delete( $post_id ) {

    if(empty($post_id)) {
        return;
    }
    
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'meta_key' => '_thumbnail_id',
        'meta_value' => $post_id,
        'fields' => 'ids',            
    );
    
    $posts = get_posts( $args );  

    global $wpdb;
    $terms = $wpdb->get_col( "SELECT DISTINCT `term_id` FROM {$wpdb->prefix}termmeta WHERE `meta_key` = 'category_image' AND `meta_value` = $post_id " );    
 
    if ( !empty( $posts ) || !empty($terms) ) {
        wp_die( 'This media file cannot be deleted because it is attached to a post.' );
    }
}
 
add_action( 'delete_attachment', 'smcp_wp_prevent_media_delete', 10, 1 );

// *****  Code for Delete and get image detail - Begin ******

//Rest API to get a image details by image id
add_action( 'rest_api_init', function () {
    register_rest_route( '/assignment/v1', '/get_image_details/', array(
      'methods' => 'GET',
      'callback' => 'smcp_actionImgDetails_callback',
    ) );
  });
  
  //Rest API to delete a image by id
  add_action( 'rest_api_init', function () {
    register_rest_route( '/assignment/v1', '/delete_image/', array(
      'methods' => 'POST',
      'callback' => 'smcp_actionDeleteImg_callback',
    ) );
  });
  
  
  function smcp_actionImgDetails_callback(WP_REST_Request $request){
      $response = [];
      $data = [];
      $parent_data = [];
      
      $body = $request->get_params();
  
      if(!isset($body['img_id'])){
          return ['status'=>false, 'message'=>'img_id param is required'];
      }
  
      $post_id = $body['img_id'];
  
      if(!ctype_digit($post_id)){
          return ['status'=>false, 'message'=>'img_id should be numeric'];
      }
  
      $attachment_args = array(
          'post_type' => 'attachment',
          'post_mime_type' => 'image',
          'p' => $post_id
      );
  
      $attachment_check = new Wp_Query( $attachment_args );
  
      if ( $attachment_check->have_posts() ) {
  
          $parent_data_fetch = new WP_Query(
              array(
                  'post_type' => 'post',
                  'meta_query' => array(
                      array(
                        'key' => '_thumbnail_id',
                        'value' => $post_id,
                      )
                  ),
              )   
          );
  
          if ( $parent_data_fetch->have_posts() ) {
              foreach($parent_data_fetch->posts as $k => $v){
                  $tmp = [];
                  $tmp['ID'] = $v->ID;
                  $tmp['slug'] = $v->post_name;
                  $tmp['date'] = $v->post_date;
                  $parent_data[] = $tmp;
              }
          }
  
          $response['status'] = true;
          $response['message'] = __("Image exists");
          $data['ID'] = $attachment_check->posts[0]->ID;
          $data['date'] = $attachment_check->posts[0]->post_date;
          $data['slug'] = $attachment_check->posts[0]->post_name;
          $data['type'] = $attachment_check->posts[0]->post_mime_type;
          $data['link'] = $attachment_check->posts[0]->guid;
          $data['alt_text'] = get_post_meta($attachment_check->posts[0]->ID, '_wp_attachment_image_alt', TRUE);
          $data['attached_to'] = $parent_data;
          $response['data'] = $data;
      }else{
          $response['status'] = false;
          $response['message'] = __("Image does not exists");
      }
  
      return wp_send_json( $response ) ;
  }
  
  
function smcp_actionDeleteImg_callback(WP_REST_Request $request){
        $response = [];
        $body = $request->get_params();
  
        if(!isset($body['img_id'])){
            return ['status'=>false, 'message'=>'img_id param is required'];
        }
  
        $post_id = $body['img_id'];
  
        if(!ctype_digit($post_id)){
            return ['status'=>false, 'message'=>'img_id should be numeric'];
        }
  
        $attachment_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'p' => $post_id
        );
  
        $attachment_check = new Wp_Query( $attachment_args );        
  
        if ( $attachment_check->have_posts() ) {

            global $wpdb;
            $terms = $wpdb->get_col( "SELECT DISTINCT `term_id` FROM {$wpdb->prefix}termmeta WHERE `meta_key` = 'category_image' AND `meta_value` = $post_id " );    
    
            $parent_data_fetch = new WP_Query(
                array(
                  'post_type' => 'post',
                  'meta_query' => array(
                      array(
                        'key' => '_thumbnail_id',
                        'value' => $post_id,
                      )
                  ),
                )   
            );           

            if ( $parent_data_fetch->have_posts() || !empty( $terms ) ) {
                $response['status'] = false;
                $response['message'] = __("Cannot deleted image, since it is already attached to posts or terms.");

            }else{
                wp_delete_attachment($post_id, true);
                $response['status'] = true;
                $response['message'] = __("Image deleted successfully");
            }

        } else {
            $response['status'] = false;
            $response['message'] = __("Image does not exists");
        }   

        return wp_send_json( $response );
  }

// *****  Code for Delete and get image detail - END ******

