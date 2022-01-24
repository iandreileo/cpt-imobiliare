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
    $filename = $post_id.basename($image_url);
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
    add_post_meta($attach_id, 'url-imagine', $image_url);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    $res2= set_post_thumbnail( $post_id, $attach_id );
}

// Functia care adauga un buton sus pentru proprietati
add_action('views_edit-estate_property', 'buton_top');
function buton_top($id) {
    
    echo '<form action="'.  admin_url('admin-post.php') . '" method="post">
   <input type="hidden" name="action" value="sincronizeaza_proprietati">
   <input type="submit" value="Sincronizeaza" class="page-title-action">
</form>';
}

// Functia care adauga un buton sus pentru dezvoltatori
add_action('views_edit-estate_developer', 'buton_top_developers');
function buton_top_developers($id) {
    
    echo '<form action="'.  admin_url('admin-post.php') . '" method="post">
   <input type="hidden" name="action" value="sincronizeaza_proprietati">
   <input type="submit" value="Sincronizeaza" class="page-title-action">
</form>';
}

// Sincronizare Proprietati
function sincronizeaza_proprietati() {
	$KEY = "c218ce8fb0266e9744c367898ab15a9ff3670066";
	$request_link = 'https://nomaz.crmrebs.com/api/public/property/?api_key=' . $KEY;
	
	// Requestul
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 
        $request_link
    );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $content = curl_exec($ch);
    $json = json_decode($content, true);
	
	// Trage toti Agentii
	$args_agenti = array(
      'numberposts' => -1,
      'post_type'   => 'estate_agent'
    );
    $agenti = get_posts( $args_agenti );
	
	// Trage toate Proprietatile
	$args_proprietati = array(
      'numberposts' => -1,
      'post_type'   => 'estate_property'
    );
 
    $proprietati = get_posts( $args_proprietati );
	
	// Iteram prin toate proprietatile primite prin API 	
	foreach($json['objects'] as $proprietate){
        adauga_listing($proprietate, $agenti, $posts);
    }
    
    // Redirectionam
    wp_redirect( 'https://nomaz.ro/wp-admin/edit.php?post_type=estate_property' );
}
add_action( 'admin_post_sincronizeaza_proprietati', 'sincronizeaza_proprietati' );


function adauga_listing($proprietate, $agenti, $posts) {
	// Field-urile proprietatii 	
    $titlu = $proprietate['title'];
    $descriere = $proprietate['description'];
    $suprafata_utila = $proprietate['surface_useable'];
    if($suprafata_utila != ""){
        $suprafata_utila .= " MP";
    }
    $suprafata_totala = $proprietate['surface_total'];
    if($suprafata_totala != ""){
        $suprafata_totala .= " MP";
    }
    $camere = $proprietate['rooms'];
    $tip_tranzactie = "";
    $pret = "";
    $tip = "";
    $id_proprietate = $proprietate['id'];
    if($proprietate['for_rent']){
        $tip = "54";
        $pret = $proprietate['price_rent'];
    }
    else{
        $tip = "57";
        $pret = $proprietate['price_sale'];
    }
    
	// Iteram prin toate complexele
	// Si testam daca 
    $args_dev = array(
      'numberposts' => -1,
      'post_type'   => 'estate_developer'
    );
 
    $gasit_complex = false;
    $complex = "";
    $posts_dev = get_posts( $args_dev );
    $id_complex = $proprietate['residential_complex']["id"];
	// Iteram 
	foreach($posts_dev as $dev){
		// luam metadatele de la acest id
        $meta = get_post_meta($dev->ID);
        print_r($meta);
        echo("=============");
        $id_crm_complex = $meta["developer_license"][0];
        if($id_complex == $id_crm_complex){
            $gasit_complex = true;
            $complex = $dev->ID;
        }
    }
            
    $tip_apartament = "";
    switch($proprietate['apartment_type']){
        case 1 :
            $tip_apartament = "Garsonieră";
            break;
        case 2 :
            $tip_apartament = "Penthouse";
            break;
        case 5 :
            $tip_apartament = "Duplex";
            break;
        case 6 :
            $tip_apartament = "Apartament";
            break;
        case 7 :
            $tip_apartament = "Triplex";
            break;
    }

	// Formam adresa
    $adresa = $proprietate['street'];
    $latitudine = $proprietate['lat'];
    $longitudine = $proprietate['lng'];

	// Imaginea principala
    $imagine_principala = $proprietate['resized_images'][0];
	
	// Taxonomiile
    $categorii = "";
    switch($proprietate['property_type']){
        case 1 :
            $categorii = "107";
            break;
        case 3 :
            $categorii = "108";
            break;
        case 4 :
            $categorii = "110";
            break;
        case 5 :
            $categorii = "111";
            break;
        case 6 :
            $categorii = "109";
            break;
        case 7 :
            $categorii = "112";
            break;
    }
    
    
    // Oras
    $oras = "113";
    
	// Caracteristici
    $dormitoare = $proprietate["bedrooms"];
    $bai = $proprietate["bathrooms"];
    $size = $proprietate["surface_total"];
    $caracteristici = [];
    $tags = $proprietate["tags"];
    foreach($tags as $key=>$tag){
        $parent_term = term_exists( $key, 'property_features' );
        if($parent_term == null){
            wp_insert_term(
            $key,   // the term 
            'property_features', // the taxonomy
            array(
                'slug'        => strtolower( str_ireplace( ' ', '-', $key ) )
            )
        );
        }
        $parent_term = term_exists( $key, 'property_features' );
        $parent_term_id = $parent_term['term_id'];
        
        foreach($tag as $child_tag){
            
            $child_tag_exists = term_exists( $child_tag, 'property_features' );
            if( $child_tag_exists == null){
                $id_child_tag = wp_insert_term(
                    $child_tag,   // the term 
                    'property_features', // the taxonomy
                    array(
                        'slug'        => strtolower( str_ireplace( ' ', '-', $child_tag ) ),
                        'parent'      => $parent_term_id,
                    )
                );
            }
            else{
                $id_child_tag = $child_tag_exists['term_id'];
            }
            
            $caracteristici[] = $id_child_tag;
           
        }

    }
    
	
    $agent_asignat = "21993";
    foreach($agenti as $agent){
        $meta = get_post_meta($agent->ID);
        $id_crm = $meta["agent_pinterest"][0];
        echo $proprietate["agent"]["id"]." --- ";
        if($id_crm == $proprietate["agent"]["id"]){
            $agent_asignat = $agent->ID;
        }
    }
	
	echo "\nAGENT: " . $agent_asignat . ' ';

    $post_gasit = false;
	// Decomenteaza updateul
//     foreach($posts as $post){
//         $meta = get_post_meta($post->ID);
//         $id_crm = $meta["id-proprietate-crm"][0];
//         if($id_crm == $proprietate["id"]){
            
//             $post_gasit = true;
//             $post_update = array(
//                 'ID'         => $post->ID,
//                 'post_title' => $titlu,
//                 'post_content' => $descriere,
//                 'post_status' => 'publish',
//                 'comment_status' => 'closed', 
//                 'ping_status' => 'closed',
//             );  
//             wp_update_post( $post_update );
        
//             update_post_meta($post->ID, 'property_price', $pret);
//             if($gasit_complex == true){ 
//                 echo "Aici e agent complex".$complex." ".$post->ID. "  ";
//                 update_post_meta($post->ID, 'property_agent_secondary', array($agent_asignat));
//                 update_post_meta($post->ID, 'property_agent', $complex);
//             }
//             else{
//                 echo "Aici nu e agent complex";
//                 update_post_meta($post->ID, 'property_agent', $agent_asignat);
//             }
//             // update_post_meta($post->ID, 'property_agent_secondary', $complex); 
//             // update_post_meta($post->ID, 'property_agent', $agent_asignat);
// //             update_post_meta($post->ID, 'property_agent', 22866);
//             update_post_meta($post->ID, 'suprafata-utila', $suprafata_utila);
//             update_post_meta($post->ID, 'suprafata-totala', $suprafata_totala);
//             update_post_meta($post->ID, 'tip-apartament', $tip_apartament);
//             update_post_meta($post->ID, 'property_address', $adresa);
//             update_post_meta($post->ID, 'property_latitude', $latitudine);
//             update_post_meta($post->ID, 'property_longitude', $longitudine);
//             update_post_meta($post->ID, 'property_bathrooms', $bai);
//             update_post_meta($post->ID, 'property_bedrooms', $dormitoare);
//             update_post_meta($post->ID, 'property_size', $size);
// // 			update_post_meta($post->ID, 'original_author', 1);
// // 			update_post_meta($post->ID, 'property_user', 1);
				
				
// 			// Testare
// 			add_post_meta($post->ID, 'sidebar_agent_option', "global");
// 			add_post_meta($post->ID, 'header_transparent', "global");
// 			add_post_meta($post->ID, 'topbar_transparent', "global");
// 			add_post_meta($post->ID, 'topbar_border_transparent', "global");
// 			add_post_meta($post->ID, 'page_show_adv_search', "global");
// 			add_post_meta($post->ID, 'page_use_float_search', "global");
// 			add_post_meta($post->ID, 'property_year_tax', "0");
// 			add_post_meta($post->ID, 'property_lot_size', "0");
// 			add_post_meta($post->ID, 'prop_featured', "0");
// 			add_post_meta($post->ID, 'property_theme_slider', "0");
// 			add_post_meta($post->ID, 'embed_video_type', "vimeo");
// 			add_post_meta($post->ID, 'page_custom_zoom', "16");
// 			add_post_meta($post->ID, 'google_camera_angle', "0");
// 			add_post_meta($post->ID, 'use_floor_plans', "0");
// 			add_post_meta($post->ID, 'post_show_title', "yes");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
// 			add_post_meta($post->ID, 'property_hoa', "0");
//             update_post_meta($post->ID, 'property_rooms', $camere);
//             update_post_meta($post->ID, 'id-proprietate-crm', $id_proprietate);
//             if($tip == "54"){
//                 update_post_meta($post->ID, 'property_label', "/lună");
//             }
//             wp_set_post_terms($post->ID, array($categorii) , 'property_category');
            
//             wp_set_post_terms($post->ID, array($tip) , 'property_action_category');
    
//             wp_set_post_terms($post->ID, array($oras) , 'property_city');
    
//             wp_set_post_terms($post->ID, $caracteristici , 'property_features');
            
//            adaugare_imagini($proprietate, $post->ID, true);
//         }}
	
	
    if($post_gasit == false){
        
        $post_id = wp_insert_post(array (
            'post_type' => 'estate_property',
            'post_title' => $titlu,
            'post_content' => $descriere,
            'post_status' => 'publish',
            'comment_status' => 'closed', 
            'ping_status' => 'closed',      
        ));
        
        
        if ($post_id) {			
            // insert post meta
            add_post_meta($post_id, 'sidebar_agent_option', "global");
			add_post_meta($post_id, 'header_transparent', "global");
			add_post_meta($post_id, 'topbar_transparent', "global");
			add_post_meta($post_id, 'topbar_border_transparent', "global");
			add_post_meta($post_id, 'page_show_adv_search', "global");
			add_post_meta($post_id, 'page_use_float_search', "global");
			add_post_meta($post_id, 'property_year_tax', "0");
			add_post_meta($post_id, 'property_lot_size', "0");
			add_post_meta($post_id, 'prop_featured', "0");
			add_post_meta($post_id, 'property_theme_slider', "0");
			add_post_meta($post_id, 'embed_video_type', "vimeo");
			add_post_meta($post_id, 'page_custom_zoom', "16");
			add_post_meta($post_id, 'google_camera_angle', "0");
			add_post_meta($post_id, 'use_floor_plans', "0");
			add_post_meta($post_id, 'post_show_title', "yes");
			add_post_meta($post_id, 'property_hoa', "0");
			
			if($gasit_complex == true){ 
//                 echo "Aici e agent complex".$complex." ".$post->ID. "  ";
                add_post_meta($post_id, 'property_agent_secondary', array($agent_asignat));
                add_post_meta($post_id, 'property_agent', $complex);
            }
            else{
//                 echo "Aici nu e agent complex";
                add_post_meta($post_id, 'property_agent', $agent_asignat);
            }
			
            add_post_meta($post_id, 'property_price', $pret);
            add_post_meta($post_id, 'suprafata-utila', $suprafata_utila);
            add_post_meta($post_id, 'suprafata-totala', $suprafata_totala);
            add_post_meta($post_id, 'tip-apartament', $tip_apartament);
            add_post_meta($post_id, 'property_address', $adresa);
            add_post_meta($post_id, 'property_latitude', $latitudine);
            add_post_meta($post_id, 'property_longitude', $longitudine);
            add_post_meta($post_id, 'property_bathrooms', $bai);
            add_post_meta($post_id, 'property_bedrooms', $dormitoare);
            add_post_meta($post_id, 'property_size', $size);
            add_post_meta($post_id, 'property_rooms', $camere);
			add_post_meta($post_id, 'original_author', array(1));
			add_post_meta($post_id, 'property_user', array(1));
            add_post_meta($post_id, 'id-proprietate-crm', $id_proprietate);
            if($tip == "54"){
                add_post_meta($post_id, 'property_label', "/lună");
            }
    
            // Imagine featured
            Generate_Featured_Image( $imagine_principala , $post_id );
            
            // Taxonomies
            wp_set_post_terms($post_id, array($categorii) , 'property_category');
            
            wp_set_post_terms($post_id, array($tip) , 'property_action_category');
    
            wp_set_post_terms($post_id, array($oras) , 'property_city');
    
            wp_set_post_terms($post_id, $caracteristici , 'property_features');
            
           adaugare_imagini($proprietate, $post_id, false);
            
            wp_update_post(array('ID' => $post_id));
			
    
        }
    }
}

function adaugare_imagini($proprietate, $post_id, $update){
	$args = array(
    	'numberposts' => -1,
		'post_type'   => 'attachment',
		'post_parent' => $post_id
	);
         
	$imagini = get_posts( $args );
	foreach($proprietate["resized_images"] as $key=>$img){
		$imagine_gasita = false;
		foreach($imagini as $imagine){
			$meta_imagine = get_post_meta($imagine->ID);
			if($img == $meta_imagine['url-imagine'][0]){
				$imagine_gasita = true;
			}
		}
		if($key == 0 || $imagine_gasita == true){
			continue;
		}
		$image_url = $img;
        
		$upload_dir = wp_upload_dir();
                
		$image_data = file_get_contents( $image_url );
                
		$filename = $key.$post_id.basename( $image_url );
                
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		}
		else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}
                
		file_put_contents( $file, $image_data );
		$wp_filetype = wp_check_filetype( $filename, null );
        
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name( $filename ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
                
                
                
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id);
		add_post_meta($attach_id, 'url-imagine', $img);
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}
	
	if($update){
		foreach($imagini as $imagine){
                $img_found = false;
                foreach($proprietate["resized_images"] as $key=>$img){
                    $meta_imagine = get_post_meta($imagine->ID);
                    if($img == $meta_imagine['url-imagine'][0]){
                        $img_found = true;
                    }
                }
                if($img_found == false){
                    wp_delete_attachment($imagine->ID, true);
                }
            }
	}
}

function update_complexe(){
    $KEY = "c218ce8fb0266e9744c367898ab15a9ff3670066";
    $request_link = 'https://nomaz.crmrebs.com/api/public/residentialcomplex/?api_key='.$KEY;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 
        $request_link
    );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $content = curl_exec($ch);
    $json = json_decode($content, true);
    
    $args = array(
      'numberposts' => -1,
      'post_type'   => 'estate_developer'
    );
 
    $posts = get_posts( $args );
    foreach($json['objects'] as $complex){
        $gasit_complex = false;
        foreach($posts as $post){
            $meta = get_post_meta($agent->ID);
            $id_crm = $meta["user_meda_id"][0];
            if($complex["id"] == $id_crm ){
                // update_complex($post, $complex);
                $gasit_complex = true;
            }
        }
        
        if($gasit_complex == false){
            adauga_complex($complex);
        }
    }
    
}

function adauga_complex($complex){
    $titlu = $complex["name"];
    $descriere = $complex["description"];
    $latitudine = $complex["lat"];
    $longitudine = $complex["lng"];
    $imagine_principala = $complex["full_images"][0];
    $id_complex = $complex["id"];
    $post_id = wp_insert_post(array (
            'post_type' => 'estate_developer',
            'post_title' => $titlu,
            'post_content' => $descriere,
            'post_status' => 'publish',
            'comment_status' => 'closed', 
            'ping_status' => 'closed',      
        ));
        
        
        if ($post_id) {
            // insert post meta
            // add_post_meta($post_id, 'property_price', $pret);
            // $agenti22 = [];
            // $agenti22[] = "21993";
            // $agenti22[] = "22284";
            // add_post_meta($post_id, 'property_agent_secondary', $agenti22); 
            add_post_meta($post_id, 'user_meda_id', $id_complex);
            add_post_meta($post_id, 'developer_latitude', $latitudine);
            add_post_meta($post_id, 'developer_longitude', $longitudine);
            // add_post_meta($post_id, 'id-proprietate-crm', $id_proprietate);
            // if($tip == "54"){
            //     add_post_meta($post_id, 'property_label', "/lună");
            // }
    
            // Imagine featured
            Generate_Featured_Image( $imagine_principala , $post_id );
            
            // Taxonomies
            // wp_set_post_terms($post_id, array($categorii) , 'property_category');
            
            // wp_set_post_terms($post_id, array($tip) , 'property_action_category');
    
            // wp_set_post_terms($post_id, array($oras) , 'property_city');
    
            // wp_set_post_terms($post_id, $caracteristici , 'property_features');
            
            // foreach($proprietate["resized_images"] as $key=>$img){
            //     if($key == 0){
            //         continue;
            //     }
            //     $image_url = $img;
        
            //     $upload_dir = wp_upload_dir();
                
            //     $image_data = file_get_contents( $image_url );
                
            //     $filename = basename( $image_url );
                
            //     if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            //       $file = $upload_dir['path'] . '/' . $filename;
            //     }
            //     else {
            //       $file = $upload_dir['basedir'] . '/' . $filename;
            //     }
                
            //     file_put_contents( $file, $image_data );
            //     $wp_filetype = wp_check_filetype( $filename, null );
        
            //     $attachment = array(
            //       'post_mime_type' => $wp_filetype['type'],
            //       'post_title' => sanitize_file_name( $filename ),
            //       'post_content' => '',
            //       'post_status' => 'inherit'
            //     );
                
                
                
            //     $attach_id = wp_insert_attachment( $attachment, $file, $post_id);
            //     add_post_meta($post_id, 'id-proprietate-crm', $id_proprietate);
            //     add_post_meta($attach_id, 'url-imagine', $img);
            //     require_once( ABSPATH . 'wp-admin/includes/image.php' );
            //     $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            //     wp_update_attachment_metadata( $attach_id, $attach_data );
            // }
    
        }
}
function update_complex($post, $complex){
    $titlu = $complex["name"];
    $descriere = $complex["description"];
    $latitudine = $complex["lat"];
    $longitudine = $complex["lng"];
    $imagine_principala = $complex["full_images"][0];
    $id_complex = $complex["id"];
    $post_update = array(
                'ID'         => $post->ID,
                'post_title' => $titlu,
                'post_content' => $descriere,
                'post_status' => 'publish',
                'comment_status' => 'closed', 
                'ping_status' => 'closed',
    );  
            wp_update_post( $post_update );
        
        
            // insert post meta
            update_post_meta($post_id, 'property_price', $pret);
            // $agenti22 = [];
            // $agenti22[] = "21993";
            // $agenti22[] = "22284";
            // add_post_meta($post_id, 'property_agent_secondary', $agenti22); 
            update_post_meta($post_id, 'property_latitude', $latitudine);
            update_post_meta($post_id, 'property_longitude', $longitudine);
            // add_post_meta($post_id, 'id-proprietate-crm', $id_proprietate);
    
            // Imagine featured
            Generate_Featured_Image( $imagine_principala , $post_id );
            
            // Taxonomies
            // wp_set_post_terms($post_id, array($categorii) , 'property_category');
            
            // wp_set_post_terms($post_id, array($tip) , 'property_action_category');
    
            // wp_set_post_terms($post_id, array($oras) , 'property_city');
    
            // wp_set_post_terms($post_id, $caracteristici , 'property_features');
            
            foreach($complex["full_images"] as $key=>$img){
                if($key == 0){
                    continue;
                }
                $image_url = $img;
        
                $upload_dir = wp_upload_dir();
                
                $image_data = file_get_contents( $image_url );
                
                $filename = basename( $image_url );
                
                if ( wp_mkdir_p( $upload_dir['path'] ) ) {
                  $file = $upload_dir['path'] . '/' . $filename;
                }
                else {
                  $file = $upload_dir['basedir'] . '/' . $filename;
                }
                
                file_put_contents( $file, $image_data );
                $wp_filetype = wp_check_filetype( $filename, null );
        
                $attachment = array(
                  'post_mime_type' => $wp_filetype['type'],
                  'post_title' => sanitize_file_name( $filename ),
                  'post_content' => '',
                  'post_status' => 'inherit'
                );
                
                
                
                $attach_id = wp_insert_attachment( $attachment, $file, $post_id);
                add_post_meta($post_id, 'id-proprietate-crm', $id_proprietate);
                add_post_meta($attach_id, 'url-imagine', $img);
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                wp_update_attachment_metadata( $attach_id, $attach_data );
            }
    
        }


?>
