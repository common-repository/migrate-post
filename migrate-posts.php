<?php
/*
 Plugin Name: Migrate Posts
 Plugin URI: https://www.dotsquares.com/
 Description: This WordPress plug-in allows you to copy the wordpress post to remote server or other wordpress site.
 Version: 1.0
 Author: Dot  Squares
 Author URI: https://www.dotsquares.com
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* create plugin tables
*/

register_activation_hook( __FILE__, 'mp_migratepost_activate');

function mp_migratepost_activate() {

		//create mp_migratepost_domains table into the database
		global $wpdb;
		$table_name = 'mp_migratepost_domains';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			domain_name varchar(255) DEFAULT '' NOT NULL,
			content_directory_name varchar(255) DEFAULT 'wp-content' NOT NULL,
			website_absolute_path  varchar(255) DEFAULT '' NOT NULL,
			ftp_host varchar(50) DEFAULT '' NOT NULL,
			ftp_username varchar(100) DEFAULT '' NOT NULL,
			ftp_password varchar(100) DEFAULT '' NOT NULL,
			db_name varchar(50) DEFAULT '' NOT NULL,
			db_host varchar(50) DEFAULT '' NOT NULL,
			db_username varchar(100) DEFAULT '' NOT NULL,
			db_password varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	
	}
	
	

/**
* css and js migration
*/
function mp_migratepost_scripts_style() {
    // Register the script
    wp_register_script( 'script', plugins_url( '/assets/js/script.js', __FILE__ ) );
    wp_enqueue_script( 'script' );
	
	// Register the style
    wp_register_style( 'style', plugins_url( '/assets/css/style.css', __FILE__ ), array(), '1', 'all' );
	wp_enqueue_style( 'style' );
}
add_action( 'init', 'mp_migratepost_scripts_style' );

add_action( 'wp_enqueue_scripts', 'mp_migratepost_enqueue_scripts' );
function mp_migratepost_enqueue_scripts(){
  wp_register_script( 'ajaxHandle', plugins_url( '/assets/js/script.js', __FILE__ ), array(), false, true );
  wp_enqueue_script( 'ajaxHandle' );
  wp_localize_script( 'ajaxHandle', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
* menu registration, css and js migration
*/

add_action('admin_menu', 'mp_migratepost_menus');

function mp_migratepost_menus() {

	//create configuration menu
	add_menu_page( "Migrate Post Domains", "Migrate Post Domains", 'manage_options', 'mp_migratepost_domain', 'mp_migratepost_domainlist', '', 107 );
	add_submenu_page( 'mp_migratepost_domain', "Add New", "Add New", 'manage_options', "add_new_domain", 'display_mp_migratepost_domain_form');
}


/**
* list domain page
*/

function mp_migratepost_domainlist() {

    require_once (dirname(__FILE__).'/includes/mp-migratepost-domain-inc.php');
	//Create an instance of our package class...
    $domainlist_tbl = new Mp_Migratepost_List_Table();
    //list data...
    $domainlist_tbl->mp_migratepost_domain_items();
	require_once (dirname(__FILE__).'/includes/mp-migratepost-domains.php');
}

/**
* create domains forms
*/
function display_mp_migratepost_domain_form() {

	require_once (dirname(__FILE__).'/includes/mp-migratepost-form.php');
}
	

/**
*copy feature image to remote server
*/

function mp_migratepost_attachments($new_id, $post = array(), $domain_details = array()){
	
        // get thumbnail ID
        $old_thumbnail_id = get_post_thumbnail_id($post->ID);
        $url = wp_get_attachment_url($old_thumbnail_id);
        $tmp = download_url( $url ); 
        $path_parts = pathinfo($url);
		
		//$upload_dir_details = wp_upload_dir();
		//$upload_dir_basepath = $upload_dir_details['basedir'];
		
		$attach_meta_data = get_post_meta( $old_thumbnail_id, '_wp_attachment_metadata', true );
	
		$file_array = array();
		$file_array['name'] = basename($url);
		$file_array['tmp_name'] = $tmp;
		
		// Now do your FTP connection
		$ftp_server     = $domain_details[0]->ftp_host;
		$ftp_username   = $domain_details[0]->ftp_username;
		$ftp_password   = $domain_details[0]->ftp_password;
		$conn_id = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
		
		@ftp_login($conn_id, $ftp_username, $ftp_password);
		
		@ftp_pasv($conn_id, true);
		
		$website_absolute_path = $domain_details[0]->website_absolute_path;
		$firstCharacter = substr($website_absolute_path, 0, 1);
		$lastCharacter = substr($website_absolute_path, -1);
		
		if($firstCharacter != '/') {
			$website_absolute_path = "/".$website_absolute_path;
		}
		
		if($lastCharacter != '/') {
			$website_absolute_path = $website_absolute_path."/";
		}
		
		if($website_absolute_path == '' || @ftp_chdir($conn_id, '/'.$domain_details[0]->content_directory_name)) {
			$website_absolute_path = '/';
		}
		
		$upload_dir_basepath = $website_absolute_path.$domain_details[0]->content_directory_name.'/uploads';
		
		//echo $upload_dir_basepath; exit;
		
		if (@!ftp_chdir($conn_id, $upload_dir_basepath)) { 
				
			@ftp_mkdir($conn_id, $upload_dir_basepath);
		}
			
		//$upload_dir_basepath = '/'.$domain_details[0]->content_directory_name.'/uploads';
		
		//create year directory in uploads folder
		$remote_year_directory = $upload_dir_basepath."/".date("Y");
		
		if (@!ftp_chdir($conn_id, $remote_year_directory)) { 
				@ftp_mkdir($conn_id, $remote_year_directory);
		} 
		
		//create month directory in uploads folder
		$remote_month_directory = $upload_dir_basepath."/".date("Y")."/".date("m");
		
		if (@!ftp_chdir($conn_id, $remote_month_directory)) { 
				
			@ftp_mkdir($conn_id, $remote_month_directory);
		} 
	
		$remote_file_path = $upload_dir_basepath."/".date("Y")."/".date("m")."/" . basename( $url );
		
		// Put the original file on the server
		@ftp_put( $conn_id, $remote_file_path, $url, FTP_BINARY );
		//echo "<pre>"; print_r($attach_meta_data['sizes']); exit;
                
                //put the all sizes images to the server
                foreach ($attach_meta_data['sizes'] as $key => $value) {
                    
                    $filename_1 = $attach_meta_data['sizes'][$key]['file'];
                    
                    if (isset($filename_1) && !empty($filename_1)) {
                        $path = $upload_dir_basepath."/".date("Y")."/".date("m")."/" .$filename_1;
                        $url = $path_parts['dirname'].'/'.$filename_1;
                        @ftp_put( $conn_id, $path, $url, FTP_BINARY );
                    }
                    
                }
                
		// Close the connection
		ftp_close( $conn_id );
		
		$attachment_url = $domain_details[0]->domain_name.$remote_file_path;
		global $wpdb;
		
		$attachment_data = array(
						'post_date' => date("Y-m-d h:i:s"),
						'post_date_gmt' => date("Y-m-d h:i:s"),
						'post_parent' => $new_id,
						'post_mime_type' => 'image/jpeg',
						'post_status' => 'inherit',
						'post_modified' => date("Y-m-d h:i:s"),
						'post_modified_gmt' => date("Y-m-d h:i:s"),
						'post_type' => 'attachment',
						'guid' => $attachment_url
						);
		$mydb = new wpdb($domain_details[0]->db_username, $domain_details[0]->db_password, $domain_details[0]->db_name, 
		$domain_details[0]->db_host);
				
		$mydb->insert( $wpdb->prefix . "posts", $attachment_data );
		$attachment_id = $mydb->insert_id;
		
		$attachment_meta_data = array(
						'meta_value' => $remote_file_path,
						'meta_key' => '_wp_attached_file',
						'post_id' => $attachment_id
						);
		$mydb->insert( $wpdb->prefix . "postmeta", $attachment_meta_data );
					
		$attachment_meta_data_thumb = array(
						'meta_value' => $attachment_id,
						'meta_key' => '_thumbnail_id',
						'post_id' => $new_id
						);
		$mydb->insert( $wpdb->prefix . "postmeta", $attachment_meta_data_thumb );
		
		$attachment_meta_data_serialize = array(
						'meta_value' => serialize($attach_meta_data),
						'meta_key' => '_wp_attachment_metadata',
						'post_id' => $attachment_id
						);
		$mydb->insert( $wpdb->prefix . "postmeta", $attachment_meta_data_serialize );
					
					
					
}

/**
* upload content part images to the remote directory 
*/

function mp_migratepost_copyimages($domain_details = array(), $url = '') {
		
		$ftp_server     = $domain_details->ftp_host;
		$ftp_username   = $domain_details->ftp_username;
		$ftp_password   = $domain_details->ftp_password;
		$conn_id = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
		
		@ftp_login($conn_id, $ftp_username, $ftp_password);
		
		@ftp_pasv($conn_id, true);
		
		$img_url_split = explode("/",$url);
		$month_dir = $img_url_split[sizeof($img_url_split)-2];
		$year_dir = $img_url_split[sizeof($img_url_split)-3];
		
		//$upload_dir_details = wp_upload_dir();
		$website_absolute_path = $domain_details->website_absolute_path;
		$firstCharacter = substr($website_absolute_path, 0, 1);
		$lastCharacter = substr($website_absolute_path, -1);
		
		if($firstCharacter != '/') {
			$website_absolute_path = "/".$website_absolute_path;
		}
		
		if($lastCharacter != '/') {
			$website_absolute_path = $website_absolute_path."/";
		}
		
		if($website_absolute_path == '' || @ftp_chdir($conn_id, '/'.$domain_details->content_directory_name)) {
			$website_absolute_path = '/';
		}
		
		$upload_dir_basepath = $website_absolute_path.$domain_details->content_directory_name.'/uploads';
		
		if (@!ftp_chdir($conn_id, $upload_dir_basepath)) { 
				
			@ftp_mkdir($conn_id, $upload_dir_basepath);
		}
		
		//create year directory in uploads folder
		$remote_year_directory = $upload_dir_basepath."/".$year_dir;
		
		if (@!ftp_chdir($conn_id, $remote_year_directory)) {
				@ftp_mkdir($conn_id, $remote_year_directory);
			} 
		
		//create month directory in uploads folder
		$remote_month_directory = $upload_dir_basepath."/".$year_dir."/".$month_dir;
		if (@!ftp_chdir($conn_id, $remote_month_directory)) {
				@ftp_mkdir($conn_id, $remote_month_directory);
			} 
		$remote_file_path = $upload_dir_basepath."/".$year_dir."/".$month_dir."/" . basename( $url );
		
		//echo $remote_file_path; exit;
		
		// Put the file on the server
		@ftp_put( $conn_id, $remote_file_path, $url, FTP_BINARY );
		
}


/**
* check ajax response 
*/

add_action('wp_ajax_my_action', 'mp_migratepost_action_callback');

function mp_migratepost_action_callback() {

	if(isset($_POST['ftp_username']) && !empty($_POST['ftp_username']) && wp_verify_nonce($_POST['nonce'], 'add_domainform_details')) {

		$ftp_host = sanitize_text_field($_POST['ftp_host']);
		$ftp_username = sanitize_text_field($_POST['ftp_username']);
		$ftp_password = sanitize_text_field($_POST['ftp_password']);

		$conn_id = ftp_connect($ftp_host);
		
			if ( @ftp_login($conn_id, $ftp_username, $ftp_password) ) {
				echo "1";
			} else {
				echo "0";
			}
		exit;
	}

	/*
	* check database connection
	*/
	if(isset($_POST['db_username']) && !empty($_POST['db_username']) && wp_verify_nonce($_POST['nonce'], 'add_domainform_details')) {
		
		global $wpdb;
		$db_name = sanitize_text_field($_POST['db_name']);
		$db_host = sanitize_text_field($_POST['db_host']);
		$db_username = sanitize_text_field($_POST['db_username']);
		$db_password = sanitize_text_field($_POST['db_password']);
		
		$con=mysqli_init();
		
		if (!$con)
		 {
			echo "0";
			exit;
		 }
		
		if (@mysqli_real_connect($con, $db_host, $db_username, $db_password, $db_name))
		  {
			echo "1";
		  } else {
			echo "0";
		  }

		mysqli_close($con);
		exit;
	}
}

/**
* drop table from database when plugin delete 
*/

register_uninstall_hook(__FILE__, 'mp_migratepost_delete_plugin_tables');

function mp_migratepost_delete_plugin_tables() {
	global $wpdb;
	$table_name = 'mp_migratepost_domains';
	$sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}