<?php
namespace RAOGSI_COMPOSER\Admin\DataSets;
use RAOGSI_COMPOSER\Framework\Traits\RGSIHooker;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class GSI_POST {
    use RGSIHooker;
    private $post_type;
    public function __construct( $post_type = 'post' ) {
        $this->post_type = $post_type;
    }

    public function get_options() {
        $data = $this->get_basic_options();
        $data = array_merge($data, $this->get_linked_options());
        $data = array_merge($data, $this->get_meta_options());
        $data = apply_filters( 'rgsi_data_options', $data );
        return $data;
    }

    /**
     * Get Basic Options
     * 
     */
    public function get_basic_options() {
        $base_options = [
            '_basic_ID'    =>  'ID',
            '_basic_post_author' => 'Post Author',
            '_basic_post_date' =>  'Post Date',
            '_basic_post_date_gmt' => 'Post Date GMT',
            '_basic_post_content'  =>  'Post Content',
            '_basic_post_title'    =>  'Post Title',
            '_basic_post_excerpt'  =>  'Post Excerpt',
            '_basic_post_status'   =>  'Post Status',
            '_basic_comment_status'=>  'Comment Status',
            '_basic_ping_status'   =>  'Ping Status',
            '_basic_post_password' =>  'Post Password',
            '_basic_post_name'     =>  'Post Name',
            '_basic_to_ping'       =>  'Ping',
            '_basic_pinged'        =>  'Pinged',
            '_basic_post_modified' =>  'Post Modified',
            '_basic_post_modified_gmt' => 'Post Modified GMT',
            '_basic_post_content_filtered' => 'Post Content Filtered',
            '_basic_post_parent'   =>  'Post Parent',
            '_basic_guid'          =>  'Guid',
            '_basic_menu_order'    =>  'Menu Order',
            '_basic_post_type'     =>  'Post Type',
            '_basic_post_mime_type'=>  'Post Mime Type',
            '_basic_comment_count' =>  'Comment Count'
        ];
        return $base_options;
    }

    /**
     * Get Linked Options
     */
    public function get_linked_options() {
        $linked_options = [
        '_linked_user_login'     =>  'User Login',
        '_linked_user_nicename' =>  'Author Nicename',
        '_linked_display_name'  =>  'Display Name',
        '_linked_user_email'    =>  'Author Email',
        '_linked_user_role'     =>  'User Role'
        ];
        return $linked_options;
    }

    /**
     * Get Meta Options
     */
    public function get_meta_options() {
        global $wpdb;
		$meta_options = [];
        //temporary
		$table1 = $wpdb->prefix.'posts'; //db call ok

		$table2 = $wpdb->prefix.'postmeta'; //db call ok

		

        $meta_keys = wp_cache_get('sheetlink_meta_keys','sheetlink_group');

        if(!$meta_keys) {

        //temporary
        $meta_keys = $wpdb->get_col($wpdb->prepare("SELECT  DISTINCT( pm.meta_key ) FROM $table1 as p LEFT JOIN $table2 as pm ON p.ID = pm.post_id WHERE p.post_type = %s AND pm.meta_key != ''",$table1, $table2, $this->post_type)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        wp_cache_set('sheetlink_meta_keys',$meta_keys,'sheetlink_group',3600);

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