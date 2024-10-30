<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// does the inserting, in case the form is filled and submitted
    if (isset($_POST["submit_form"]) && wp_verify_nonce($_REQUEST['nonce'], 'add_domainform_details')) {
		
        $domain_name = sanitize_text_field($_POST["domain_name"]);
		$content_directory_name = sanitize_text_field($_POST["content_directory_name"]);
		$website_absolute_path = sanitize_text_field($_POST["website_absolute_path"]);
		$ftp_host = sanitize_text_field($_POST["ftp_host"]);
		$ftp_username = sanitize_text_field($_POST["ftp_username"]);
		$ftp_password = sanitize_text_field($_POST["ftp_password"]);
		$db_name = sanitize_text_field($_POST["db_name"]);
		$db_host = sanitize_text_field($_POST["db_host"]);
		$db_username = sanitize_text_field($_POST["db_username"]);
		$db_password = sanitize_text_field($_POST["db_password"]);
		$editid = $_POST["editid"];
		
		global $wpdb;
		$table_name = 'mp_migratepost_domains';
		
		if($editid) { 
			$wpdb->update( 
				$table_name, 
				array( 
					'domain_name' => $domain_name,
					'content_directory_name' => $content_directory_name,
					'website_absolute_path' => $website_absolute_path,
					'ftp_host' => $ftp_host,
					'ftp_username' => $ftp_username,
					'ftp_password' => $ftp_password,
					'db_name' => $db_name,
					'db_host' => $db_host,
					'db_username' => $db_username,
					'db_password' => $db_password
				),
				array(
					 'id' => $editid
				)
			); 
			$data = array();
			
			
		} else {
		
			$wpdb->insert( 
				$table_name, 
				array( 
					'domain_name' => $domain_name,
					'content_directory_name' => $content_directory_name,
					'website_absolute_path' => $website_absolute_path,
					'ftp_host' => $ftp_host,
					'ftp_username' => $ftp_username,
					'ftp_password' => $ftp_password,
					'db_name' => $db_name,
					'db_host' => $db_host,
					'db_username' => $db_username,
					'db_password' => $db_password
				)
			);
		
		}
       echo "<div style='font-weight: bold;margin-top: 5px;font-size: 15px;'>Your domain details successfully recorded. Thanks!!</div>";
    }
	
?>
<form action="#copy_domain_form" method="post" id="copy_domain_form">
       <div class="copy_doain_form">
		 <h1>Migrate Post Domain</h1>
		 <div class="field_group">
			<label>Domain Name *</label>
			<input type="url" name="domain_name" id="domain_name" placeholder="http://yourdomain.com" value="<?php if(isset($data[0]->domain_name) && !empty($data[0]->domain_name)) { echo $data[0]->domain_name; } ?>" required="required">
			
		 </div>
		 <div class="field_group">
			<label>Content Directory Name *</label>
			<input type="text" name="content_directory_name" id="content_directory_name"  value="<?php if(isset($data[0]->content_directory_name) && !empty($data[0]->content_directory_name)) { echo $data[0]->content_directory_name; } else { echo "wp-content"; } ?>" required="required">
			<p>Default is 'wp-content', if you changed this directory name then replace 'wp-content' to 'your directory name'.</p>
		 </div>
		 <div class="field_group">
			<label>Website absolute path</label>
			<input type="text" name="website_absolute_path" id="website_absolute_path"  value="<?php if(isset($data[0]->website_absolute_path) && !empty($data[0]->website_absolute_path)) { echo $data[0]->website_absolute_path; }  ?>">
			<p>If below ftp details not pointing to wordpress install directory then enter website Absolute Path (Like - /home/ds/public_html).</p>
		 </div>
		 
		 <div class="copypost_block_heading">Ftp Details <small>( Install wordpress directory FTP )</small></div>
		 <div class="field_group">
			<label>Ftp Host *</label>
			<input type="text" name="ftp_host" id="ftp_host" value="<?php if(isset($data[0]->ftp_host) && !empty($data[0]->ftp_host)) { echo $data[0]->ftp_host; } ?>" required="required">
		 </div>
		 <div class="field_group">
			<label>Ftp Username *</label>
			<input type="text" name="ftp_username" id="ftp_username" value="<?php if(isset($data[0]->ftp_username) && !empty($data[0]->ftp_username)) { echo $data[0]->ftp_username; } ?>" required="required">
		 </div>
		 <div class="field_group">
			<label>Ftp Password *</label>
			<input type="text" name="ftp_password" id="ftp_password" value="<?php if(isset($data[0]->ftp_password) && !empty($data[0]->ftp_password)) { echo $data[0]->ftp_password; } ?>" required="required">
		 </div>
		
		<div class="field_group"><a href="javascript:void(0)" class="test_ftp_connection">Test ftp connection</a> <span id="ftp_connection_status" class="connection_status"></span></div>
		 <div class="copypost_block_heading">Database Details <small>( Remotely allowed database )</small></div>
		 <div class="field_group">
			<label>Database Name *</label>
			<input type="text" name="db_name" id="db_name" value="<?php if(isset($data[0]->db_name) && !empty($data[0]->db_name)) { echo $data[0]->db_name; } ?>" required="required">
		 </div>
		 <div class="field_group">
			<label>Database Host *</label>
			<input type="text" name="db_host" id="db_host" value="<?php if(isset($data[0]->db_host) && !empty($data[0]->db_host)) { echo $data[0]->db_host; } ?>" required="required">
		 </div>
		 <div class="field_group">
			<label>Database Username *</label>
			<input type="text" name="db_username" id="db_username" value="<?php if(isset($data[0]->db_username) && !empty($data[0]->db_username)) { echo $data[0]->db_username; } ?>" required="required">
		 </div>
		 <div class="field_group">
			<label>Database Password *</label>
			<input type="text" name="db_password" id="db_password" value="<?php if(isset($data[0]->db_password) && !empty($data[0]->db_password)) { echo $data[0]->db_password; } ?>" required="required">
		 </div>
		 <div class="field_group"><a href="javascript:void(0)" class="test_db_connection">Test database connection</a><span id="db_connection_status" class="connection_status"></span></div>
		 <div class="copypost_submit_btn">
		 <input type="hidden" name="migrate-post-plugin-url" id="migrate-post-plugin-url" value="<?php echo plugins_url(); ?>">
		 <input type="hidden" name="ftp-connection-status" value="0" id="ftp-connection-status">
		 <input type="hidden" name="db-connection-status" value="0" id="db-connection-status">
		 <input type="hidden" name="editid" value="<?php if(isset($data[0]->id) && !empty($data[0]->id)) { echo $data[0]->id; } ?>">
		 <div id="db_ftp_connection_issue"></div>
		 <?php wp_nonce_field('add_domainform_details', 'nonce'); ?>
		 <input type="hidden" name="ajaxurl" id="form_ajaxurl" value="<?php echo admin_url('admin-ajax.php'); ?>">
		 <input type="submit" name="submit_form" id="submit_connection_form" value="Submit"></div>
	   </div>
    </form>