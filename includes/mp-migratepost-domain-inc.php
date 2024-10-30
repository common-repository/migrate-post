<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Mp_Migratepost_List_Table extends WP_List_Table {
    
	 private $table_name;
	 
     function __construct(){
		
        global $wpdb;
		$this->table_name = 'mp_migratepost_domains';
       
       parent::__construct( array(
            'singular'  => 'domain',     
            'plural'    => 'domains'  
        ) );
        
    }


    /** **********
     * Item lists
     ************* */
    function column_default($item, $column_name){ 
	//print_r($item); exit;
	
	switch($column_name){
            case 'ftp_status':
				//return $ftp_connect_status;
				return $this->mp_migratepost_ftp_connect($item);
			case 'db_status':
				return $this->mp_migratepost_database_connect($item);
            case 'domain_name':
                return $this->mp_migratepost_column_title($item);
			default:
                return '';
        }
    }
	
	/** **********
     * Get the ftp status
     ************* */
	function mp_migratepost_ftp_connect($item = array()) {
	
		$ftp_server     = $item->ftp_host;
		$ftp_username   = $item->ftp_username;
		$ftp_password   = $item->ftp_password;
		$conn_id = ftp_connect($ftp_server);
		
		if (@ftp_login($conn_id, $ftp_username, $ftp_password)) {
			$ftp_connect_status = "Ftp Connected";
			ftp_close( $conn_id );
		} else {
			$ftp_connect_status = "<span style='color:red'>"."Ftp Connection Error"."</span>";
		}
		return $ftp_connect_status;
		
	}
	
	/** **********
     * Get the database status
     ************* */
	function mp_migratepost_database_connect($item = array()) {
	
		$con=mysqli_init();
	
		if (@mysqli_real_connect($con, $item->db_host, $item->db_username, $item->db_password, $item->db_name)) {
			$db_connect_status = "Database Connected";
		} else {
			$db_connect_status = "<span style='color:red'>"."Database Connection Error"."</span>";
		}
		
		mysqli_close($con);
		
		return $db_connect_status;
		
	}


    /** ************************
     * To show the actions links
	 ***************************/
    function mp_migratepost_column_title($item){
        
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&domain=%s">Edit</a>',$_REQUEST['page'],'edit',$item->id),
            'delete'    => sprintf('<a href="?page=%s&action=%s&domain=%s">Delete</a>',$_REQUEST['page'],'delete',$item->id),
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver"></span>%3$s',
            /*$1%s*/ $item->domain_name,
            /*$2%s*/ $item->id,
            /*$3%s*/ $this->row_actions($actions)
        );
    }


    /** *************************
     * checkbox for bulk actions
     ****************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item->id                
        );
    }


    /** **************
     * Column headings
     *****************/
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'domain_name'     => 'Domain Name',
            'ftp_status'    => 'Ftp Status',
            'db_status'  => 'Database Status'
        );
        return $columns;
    }


   /** ************************
     * bulk actions for domains
    ***************************/
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
			'migrate_post'    => 'Migrate Posts'
        );
        return $actions;
    }


    /** *********************
     * Execute bulk actions.
     ***********************/
    function mp_migratepost_process_bulk_action() {
        global $wpdb;
        //Detect when a bulk action is being triggered...
		
        if( 'delete'===$this->current_action() ) {
		if(is_array($_REQUEST['domain'])) {
			$domain_ids = implode(",", $_REQUEST['domain']);
		} else {
			$domain_ids = $_REQUEST['domain'];
		}
			
			$wpdb->query(
              'DELETE  FROM '.$this->table_name.'
               WHERE id in ('.$domain_ids.')'
				);
			
        } else if('edit'===$this->current_action() ) {
			 
			 $domain_id = $_REQUEST['domain'];
			 $data = $wpdb->get_results( "SELECT * FROM ".$this->table_name." WHERE id = ".$domain_id );
			 require_once (dirname(__FILE__).'/mp-migratepost-form.php');
			 exit;
			 
			 } else if('migrate_post'===$this->current_action() ) {
			 
			 $domain_ids = implode(",", $_REQUEST['domain']);
			 require_once (dirname(__FILE__).'/mp-migratepost-migrateform.php');
			 exit;
			
		}
        
    }


    /** ************
     * List domains
     ***************/
    function mp_migratepost_domain_items() {
        
		global $wpdb; //This is used only if making any database queries
		
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 50;
        
        
        /**
         * column headers.
         */
        $columns = $this->get_columns();
        $hidden = array();
       // $sortable = $this->get_sortable_columns();
        
        
        /**
         * column headers.
         */
        $this->_column_headers = array($columns, $hidden, '');
        
        
        /**
         * bulk action for domains
         */
        $this->mp_migratepost_process_bulk_action();
        
        
        /**
         * get the domain records
         */
		$data = $wpdb->get_results( "SELECT * FROM ".$this->table_name );
             
		/**
         * get current page for pagination
        */
        $current_page = $this->get_pagenum();
        
        /**
         * count records. 
        */
        $total_items = count($data);
        
        
        /**
         * prepare for pagination
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        /**
         * List items
        */
        $this->items = $data;
        
        /**
         * Pagination
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }


}