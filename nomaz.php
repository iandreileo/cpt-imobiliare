<?php
/**
* Plugin Name: NOMAZ
* Plugin URI: http://yourdomain.com
* Description: Insert a brief description of what your plugin does here.
* Version: 1.0.0
* Author: Your Name
* Author URI: http://yourdomain.com
* License: GPL2
*/


function Generate_Featured_Image( $image_url, $post_id  ){
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if(wp_mkdir_p($upload_dir['path']))
      $file = $upload_dir['path'] . '/' . $filename;
    else
      $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    $res2= set_post_thumbnail( $post_id, $attach_id );
}

// Functia care adauga un buton sus
add_action('views_edit-estate_property', 'buton_top');
function buton_top($id) {
    
    echo '<form action="'.  admin_url('admin-post.php') . '" method="post">
   <input type="hidden" name="action" value="my_media_update">
   <input type="submit" value="Sincronizeaza">
</form>';
}




// Functia care face sincronizarea
function my_media_update() {
    // Aici facem sincronizare
    echo "DA";
    
    // testam

    
    // Apelam API
    
    // Iteram prin listings
    
    // Daca listing nu exista, adaugam
    adauga_listing();
    

    
    wp_redirect( 'https://searchadsdev.ro/imobiliare/wp-admin/edit.php?post_type=estate_property' );
}
add_action( 'admin_post_my_media_update', 'my_media_update' );


function adauga_listing() {
    $titlu= "Titlu postare";
    $descriere = "Descriere listing";
    $pret = 1500;
    $suprafata_utila = "50 MP";
    $suprafata_totala = "75 MP";
    $camere = "4";
    $tip_apartament = "Garsonieră";
    $adresa = "Adresa";
    $latitudine = "25";
    $longitudine = "30";
    $agent = "57";
    $imagine_principala = "https://thumbs.crmrebs.com/sT-SGF7tEr1MGWQ_htjDEImDBSHZUlDyjClmDU0_5nI/fit/1920/1080/ce/0/aHR0cHM6Ly9hZ29y/YS1zdGF0aWMtcHJv/ZC5zMy5hbWF6b25h/d3MuY29tL3Byb3Bl/cnR5X2ltYWdlcy82/MDQ5Lzk5ZmNiZDRj/LWJkMmEtNGVlMi04/MDJhLWU5ODFkYTAy/Yjc1Yy5qcGc.jpg";
    // De facut aici sa caute categoria primita si sa returneze ID-u
    $categorii = "107";
    
    // Tip
    $tip = "57";
    
    // Oras
    $oras = "113";

    // Caracteristici
    $caracteristici = "129";

    // insert the post and set the category
    $post_id = wp_insert_post(array (
        'post_type' => 'estate_property',
        'post_title' => $titlu,
        'post_content' => $descriere,
        'post_status' => 'publish',
        'comment_status' => 'closed',   // if you prefer
        'ping_status' => 'closed',      // if you prefer
    ));
    
    if ($post_id) {
        // insert post meta
        add_post_meta($post_id, 'property_price', $pret);
        add_post_meta($post_id, 'suprafata-utila', $suprafata_utila);
        add_post_meta($post_id, 'suprafata-totala', $suprafata_totala);
        add_post_meta($post_id, 'tip-apartament', $tip_apartament);
        add_post_meta($post_id, 'property_address', $adresa);
        add_post_meta($post_id, 'property_latitude', $latitudine);
        add_post_meta($post_id, 'property_longitude', $longitudine);
        add_post_meta($post_id, 'property_agent', $agent);

        // Imagine featured
        Generate_Featured_Image( $imagine_principala , $post_id );
        
        // Taxonomies
        wp_set_post_terms($post_id, array($categorii) , 'property_category');
        
        wp_set_post_terms($post_id, array($tip) , 'property_action_category');

        wp_set_post_terms($post_id, array($oras) , 'property_city');

        wp_set_post_terms($post_id, array($caracteristici) , 'property_features');


    } 
}

?>
