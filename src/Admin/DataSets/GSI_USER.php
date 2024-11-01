<?php
namespace RAOGSI_COMPOSER\Admin\DataSets;
use RAOGSI_COMPOSER\Framework\Traits\RGSIHooker;
class GSI_USER {
    private $entity;
    public function __construct( $post_type = 'post' ) {
        $this->entity = 'user';

    }

    public function get_options() {
        $data = $this->get_basic_options();
        $data = array_merge($data, $this->get_linked_options());
        $data = array_merge($data, $this->get_meta_options());
       // $data = apply_filters( 'rgsi_user_options', $data );
        return $data;
    }

    public function get_basic_options() {
        $base_options = [
            '_basic_ID'    =>  'ID',
            '_basic_user_login' => 'User Login',
            '_basic_user_email' =>  'User Email',
            '_basic_user_firstname' => 'User First Name',
            '_basic_user_lastname'  =>  'User Last Name',
            '_basic_display_name'    =>  'Display Name',
            
        ];
        return $base_options;
    }

    public function get_linked_options() {
        return [];
    }

    public function get_meta_options() {

        global $wpdb;
        $meta_options = [];
		$table1 = $wpdb->prefix.'users'; //db call ok
		$table2 = $wpdb->prefix.'usermeta'; //db call ok
		
        $meta_keys = wp_cache_get('sheetlink_user_meta_keys','sheetlink_group');
        $meta_keys = [];
        if(!$meta_keys) {
        
        $meta_keys = $wpdb->get_col("SELECT DISTINCT( meta_key ) FROM wp_usermeta"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        wp_cache_set('sheetlink__user_meta_keys',$meta_keys,'sheetlink_group',3600);
        }
        
        if( empty( $meta_keys ) ) {
            return array();
        } else {
            foreach( $meta_keys as $key => $meta_key ) {
                $meta_options["_meta_".$meta_key] = "Meta - ".$meta_key;
            }
        }
        
        return $meta_options;
    }
}