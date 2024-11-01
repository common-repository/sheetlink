<?php
namespace RAOGSI_COMPOSER\Admin;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class RGSIIntegrationOptions {
    
    protected $custom_post_types;

    protected $blocked_cpts;

    protected $cpt_actions;

    public function __construct() {
        $this->initialize_blocked_cpts();
    }

    /**
     * Initialize blocked cpts
     */
    public function initialize_blocked_cpts() {
        $cpts = array(
            
        );
        $this->blocked_cpts = $cpts;
        $this->cpt_actions = ['create','update','delete','sync'];
    }

    public function get_custom_post_types() {
        $args = array(
            'public'    =>  true,
            '_builtin'  =>  false
        );
        $post_types = get_post_types( $args, 'OBJECT' );
        $post_types['post'] = get_post_type_object('post');
        $post_types['page'] = get_post_type_object('page');
        if( is_plugin_active('woocommerce/woocommerce.php' ) )
        $post_types['shop_order_placehold'] = get_post_type_object('shop_order_placehold');
        return $post_types;
    }

    public function get_user_entity( $generic = false ){
        $action_id = 'rao_user_';
        //$actions = ['create','update','delete','sync'];
        $actions = ['create'];
        $slug = 'user'; 
        $userActions = [];
        foreach( $actions as $action ) {
            if( !$generic ) 
            $userActions[$slug]["actions"][$action_id.$action] = ucwords($action).' User';
            else
            $userActions[$action_id.'_'.$action] = ucwords($action).' User';
        }
        return $userActions;
    }
    public function get_allowed_cpts($generic = false) {
        
        $post_types = $this->get_custom_post_types();
        
        
        $cptActions = [];
        foreach( $post_types as $slug => $post_type_object ) {
            $post_label = $post_type_object->label;
            if( $slug !== 'post' && $slug !== 'page' ) {
            $cpt_action_id = 'rao_cpt_'.$slug;
            $cpt_action_label = 'CPT '.$post_label;
            } else {
            $cpt_action_id = 'rao_'.$slug;
            $cpt_action_label = $post_label;
            }
            foreach( $this->cpt_actions as $key => $action ) {
                if( !$generic )
                $cptActions[$slug]["actions"][$cpt_action_id."_".$action] = $cpt_action_label . " " . ucwords( $action );
                else
                $cptActions[$cpt_action_id."_".$action] = $cpt_action_label . " " . ucwords( $action );
            }
        }
        return $cptActions;
    }

}