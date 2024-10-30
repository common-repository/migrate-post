<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// does the inserting, in case the form is filled and submitted
    if (isset($_POST["submit_form"]) && wp_verify_nonce($_REQUEST['nonce'], 'copy_post_to_domain')) {
	//print_r($_SERVER);
	//exit;
		global $wpdb;
		$domains_ids = explode(",", $_REQUEST['domains_ids']);
		$post_table_name = $wpdb->prefix . "posts";
		
		foreach($domains_ids as $domain_id) {
			
			
			$domain_tbl = "mp_migratepost_domains";
			$domain_details = $wpdb->get_results("select * from ".$domain_tbl." WHERE id = ".$domain_id);
			$mydb = new wpdb($domain_details[0]->db_username, $domain_details[0]->db_password, $domain_details[0]->db_name, $domain_details[0]->db_host);
			
			$adminuser = "SELECT `ID` 
					FROM ".$wpdb->prefix."users 
					INNER JOIN ".$wpdb->prefix."usermeta ON ( ".$wpdb->prefix."users.ID = ".$wpdb->prefix."usermeta.user_id ) 
					WHERE 1=1 
						AND ( ( ( ".$wpdb->prefix."usermeta.meta_key = 'wp_capabilities' 
							AND ".$wpdb->prefix."usermeta.meta_value LIKE '%\"Administrator\"%' ) ) ) 
					ORDER BY user_login ASC";
					$adminuser_details = $mydb->get_results($adminuser, OBJECT);
			
			$site_posts = $_REQUEST['site_posts'];
			foreach($site_posts as $postid) {
			
				$post = get_post($postid);
				
				$data = array(
				'post_date' => date("Y-m-d h:i:s"),
				'post_date_gmt' => date("Y-m-d h:i:s"),
				'comment_status' => $post->comment_status,
				'post_author' => $adminuser_details[0]->ID,
				'ping_status' => $post->ping_status,
				'post_excerpt' => $post->post_excerpt,
				'post_mime_type' => $post->post_mime_type,
				'post_status' => $post->post_status,
				'post_title' => $post->post_title,
				'post_modified' => date("Y-m-d h:i:s"),
				'post_modified_gmt' => date("Y-m-d h:i:s"),
				'post_type' => $post->post_type,
				'post_name' => $post->post_name
				);
				
				if(isset($_REQUEST['post_content']) && $_REQUEST['post_content'] == 1) {
				   
					$data['post_content'] = $post->post_content;
				  
					preg_match_all('/<img[^>]+>/i',$post->post_content, $results); 
					$content_images = $results[0];
					foreach($content_images as $con_img) {
						preg_match_all('/(src)=("[^"]*")/i',$con_img, $img_src);
						$img_src_url = trim($img_src[2][0], '"'); 
						mp_migratepost_copyimages($domain_details[0], $img_src_url);
						
					}
				}
				
				$query = $mydb->prepare(
					'SELECT ID FROM ' . $post_table_name . '
					WHERE post_name = %s
					AND post_type = \'post\'',
					$post->post_name
				);
				$mydb->query( $query );
				
				if ( $mydb->num_rows ) {
					
					$existing_data = $mydb->get_results($query);
					$curr_post_id = $existing_data[0]->ID;
					$post_date = date("Y-m-d h:i:s");
					
					$mydb->query( 
						 $mydb->prepare(
							  "UPDATE $post_table_name SET post_date = '$post_date', post_date_gmt = '$post_date', 
							  comment_status = '$post->comment_status', ping_status = '$post->ping_status', 
							  post_excerpt = '$post->post_excerpt', post_status = '$post->post_status', 
							  post_title = '$post->post_title', post_modified = '$post_date', 
							  post_modified_gmt = '$post_date'
							  WHERE ID=%s",
							  $curr_post_id
						 )
					);
					$lastid = $curr_post_id;
				} else {
					$mydb->insert($post_table_name, $data);
					$lastid = $mydb->insert_id;
				}
				
				
				
				
				if(isset($_REQUEST['post_content']) && $_REQUEST['post_content'] == 1) {
					$site_url = get_bloginfo('url');
					$domain_name_rep = $domain_details[0]->domain_name;
					$mydb->query( 
						 $mydb->prepare(
							  "UPDATE $post_table_name SET post_content = 
							  replace('$post->post_content', '$site_url', '$domain_name_rep')
							  WHERE ID=%s",
							  $lastid
						 )
					);
				}

				if(isset($_REQUEST['post_feature_image']) && $_REQUEST['post_feature_image'] == 1) {
				
						//add attachment
						$post_thumbnail_id = get_post_thumbnail_id($post->ID);
						if(isset($post_thumbnail_id) && !empty($post_thumbnail_id)) {
							mp_migratepost_attachments($lastid, $post, $domain_details);
						}
					
					
					}
				
				
			}
		}
	
       if ($lastid ) {
            echo "<div style='font-weight: bold;margin-top: 5px;font-size: 15px;'>Your posts successfully copied on selected domains. Thanks!!</div>";
        } else {
            echo "<div style='font-weight: bold;margin-top: 5px;font-size: 15px;'>Something wrong please check and try again!!</div>";
        }
    }
	
$site_posts = get_posts(array('numberposts' => -1));

//echo "<pre>";
//print_r($site_posts); exit;
	
?>
<form action="#copypost_todomain_form" method="post" id="copypost_todomain_form">
       <div class="copy_doain_form">
		 <h1>Migrate Posts To Selected Domains</h1>
		 <div class="field_group">
			<label>Select Posts *</label>
			<select name="site_posts[]" multiple>
			 <?php foreach($site_posts as $post) { ?>
				<option value="<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></option>
			  <?php } ?>
			</select>
			 </div>
		 
		 <div class="field_group">
			<label>Post elements to copy</label>
			<input type="checkbox" disabled="disabled" checked="checked" name="post_title">Title<br />
			<input type="checkbox" value="1" checked="checked" name="post_content">Content<br />
			<input type="checkbox" value="1" checked="checked" name="post_feature_image">Featured Image
		 </div>
		
		 <div class="copypost_submit_btn"><input type="hidden" name="domains_ids" value="<?php echo $domain_ids; ?>">
		 <?php wp_nonce_field('copy_post_to_domain', 'nonce'); ?>
		 <input type="submit" name="submit_form" value="Submit"></div>
	   </div>
</form>