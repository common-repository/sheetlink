<?php
namespace RAOGSI_COMPOSER\Admin;

use RAOGSI_COMPOSER\Framework\Traits\RGSIHooker;

class RGSICore {
    use RGSIHooker;

    protected $domain;

    protected $credentials;

    protected $googlesheet;

    protected $customPostTypes;

    protected $integration_exist;

    public function __construct( $googlesheet ) {
        $this->googlesheet = $googlesheet;
        $this->action( 'admin_menu', 'render_menu_items' );
        $this->action( 'admin_post_rao_google_settings', 'save_rao_google_settings' );
        $this->action( 'admin_enqueue_scripts', 'render_scripts' );
        $this->action( 'save_post', 'perform_sheet_actions', 10, 3 );
        $this->action( 'after_delete_post', 'perform_delete_actions', 999, 2 );
        $this->action( 'user_register', 'perform_user_actions', 999, 2);
        //$this->action( 'rest_after_insert_user', 'perform_apiuser_actions', 999999,3);
    }

    public function render_menu_items() {
        add_menu_page(__('Gsheets Connector', 'gsheets-connector'), __('Gsheets Connector', 'gsheets-connector'),'manage_options','raogsi', array($this,'rao_gsi_dashboard'),'dashicons-media-spreadsheet');
        add_submenu_page("raogsi", "Settings", "Settings", "manage_options", "raogsi-settings",  array($this,'render_settings_page'));
    }

    /**
     * RAO GSI Dashboard
     */
    public function rao_gsi_dashboard() {
        //Get Suitable Action
        $action = isset( $_GET['action'] )  ? sanitize_text_field( $_GET['action'] ) :  ""; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $id = isset($_GET['id']) ? sanitize_text_field( $_GET['id']) : ""; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if( $action == "new" || $action == "edit" ) 
        $this->get_initial_sources();
        switch( $action ) {
            case 'new':
                $this->raogsi_new_integration();
                break;
            case 'edit':
                $this->raogsi_edit_integration( $id );
                break;
            default:
                $this->raogsi_connections( $action, $id );
                break;
        }

    }

    /**
     * Rao GSI New Integration
     */
    public function raogsi_new_integration() {
        $edit = false;
        $data = $this->render_edit_integration_data( false );
        include_once RAOGSI_PATH.'/partials/raogsi-action-integration.php';
    }

    /**
     * RAOGSI Edit Integration
     */
    public function raogsi_edit_integration( $id = 0 ) {
        $edit = true;
        $integration_data = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
        $integration_data = $integration_data->where( 'id', (int)$id )->get()->toArray();
        if( !empty($integration_data)) {
            $data = $this->render_edit_integration_data( $integration_data[0] );
        } else {
            $data = $this->render_edit_integration_data(0);
        }
        include_once RAOGSI_PATH.'/partials/raogsi-action-integration.php';
    }

    /**
     * Render single integration object
     */
    public function render_edit_integration_data( $integration_data = false ) {
        $title = $wp_source = $ss_source = '';
        $data = array(
            'title' =>  '',
            'wp_source'=>   '',
            'ss_source'=>   '',
            'mappings' => '',
            'integ_id' => '',
            'sync_column' => '',
            'is_active' =>  ''    
        );
        
        if( $integration_data ) {
            $data['title'] = isset( $integration_data['title'] ) ? sanitize_text_field( $integration_data['title'] ) : "";
            $data['wp_source'] = isset( $integration_data['wp_source'] ) ? sanitize_text_field( $integration_data['wp_source'] ) : "";
            $data['ss_source'] = isset( $integration_data['ss_source'] ) ? sanitize_text_field( $integration_data['ss_source'] ) : "";
            $data['mappings'] = isset( $integration_data['mapped_data'] ) ? map_deep( $integration_data['mapped_data'], 'sanitize_text_field' ) : "";
            $data['integ_id'] = $integration_data['id'];
            $data['sync_column'] = $integration_data['sync_column'];
            $data['is_active'] = $integration_data['is_active'];

        }
        
        return $data;
    }

    /**
     * Get Initial Sources
     */
    public function get_initial_sources() {
        $integration_options = new RGSIIntegrationOptions();
        $this->customPostTypes = $integration_options->get_allowed_cpts();
        $user_entity = $integration_options->get_user_entity();
        $this->customPostTypes = array_merge($user_entity, $this->customPostTypes);
        $ss_data = $this->googlesheet->raogsi_spreadsheetsAndWorksheets();
        $this->spreadsheets_worksheets = [];
        if( !is_wp_error( $ss_data ))
        {
            $this->spreadsheets_worksheets = $ss_data[1];
        }
        
    }

    /**
     * Render Scripts
     */
    public function render_scripts( $hook ) {
        $ver = '1.0';
        
        if( $hook == "toplevel_page_raogsi" ) {
            wp_enqueue_style( 'raogsi-select2', RAOGSI_ASSETS."/css/select2.min.css", array(), $ver, false );
            wp_enqueue_script( 'raogsi-select2', RAOGSI_ASSETS."/js/select2.min.js", array(), $ver, false );
            wp_enqueue_style( 'raogsi-admin', RAOGSI_ASSETS."/css/raogsi-admin.css", array(), $ver, false );
            wp_enqueue_script( 'raogsi-blockui', RAOGSI_ASSETS."/thirdparty/blockUi.js", array(), $ver, false );
            wp_enqueue_script( 'raogsi-admin', RAOGSI_ASSETS."/js/raogsi-admin.js", array('raogsi-select2'), $ver, true);
            wp_localize_script( 'raogsi-admin', 'raogsi', array(
                'ajaxurl'   =>  admin_url('admin-ajax.php')
            ));

        }
    }

    /**
     * RAO GSI Connections
     */
    public function raogsi_connections( $action, $id ) {
        $process_delete = "no";
        if( $action == 'delete' && $id )
        {
            $process_delete = "yes";
            $deleted = false;
            $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
            $integration = $integration_model::find( $id );
            
            if( $integration ) {
                $destroy = $integration->delete();
                if( $destroy )
                {
                    $deleted = true;
                }
            }
        }
        include_once RAOGSI_PATH.'/partials/raogsi-integrations.php';
    }
    
    /**
     * Render Settings Page
     */
    public function render_settings_page() {
        $this->credentials = raogsi_get_google_service_account_credentials();
        $ret = $this->googlesheet->raogsi_validate_credentials();
        include_once RAOGSI_PATH.'/partials/raogsi-settings.php';
        
    }
    
    /**
     * Save Google Settings
     */
    public function save_rao_google_settings() {
        //Check set or not
        wp_verify_nonce('raogsi-google-nonce');
        $credential = false;
        /*if( isset( $_POST['raogsa-credential']) && !empty($_POST['raogsa-credential']) )
        {
            $credential= json_decode( map_deep($_POST['raogsa-credential'], 'sanitize_text_field'), true );
        }*/
        $credential = (isset( $_POST['raogsa-credential']) && !empty($_POST['raogsa-credential'])) ? json_decode(stripslashes($_POST['raogsa-credential']), true) : false ;
        
        if($credential  &&  wp_verify_nonce( sanitize_key($_POST['nonce'] ), 'raogsi-google-nonce')){
            update_option( 'raogsi_credentials', $credential );
            $token = $this->googlesheet->raogsi_generatingTokenByCredentials();
            if( isset($token[0]) && $token[0] ) {
                $token_data = $token[1];
                $token_data['expires_in'] = time() + 3540;
                update_option( 'raogsi_google_token', $token_data );
                
            }

        } else if(isset($_GET['deleteCredential']) && wp_verify_nonce(sanitize_key($_GET['nonce']), 'raogsa-google-nonce-delete')){
			delete_option( "raogsi_google_token");
			delete_option( "raogsi_credentials");
		} 
       
        wp_redirect( 'admin.php?page=raogsi-settings' );
    }

    public function get_wp_source_key( $post, $delete = false ) {
        $post_type = $post->post_type;
        $default_types = array( 'post', 'page' );
        $wp_source_key = false;
        
        if( !in_array( $post_type, $default_types ) ) {
            $wp_source_key = "rao_cpt_".$post_type."_";

        } else {
            $wp_source_key = "rao_".$post_type."_";
        } 

        
            
       
        return $wp_source_key;
    }

    public function perform_apiuser_actions( $user, $request, $is_create ) {
        $args = ['rao_user_create','rao_user_sync','rao_user_update'];
        
        $this->integration_exists($args);
        if( !empty($this->integration_exist) ) {
            //if( in_array('rao_user_create', $this->integration_exist) || in_array('rao_user_sync', $this->integration_exist)) {
            if($is_create)
                $this->process_user( $user->ID, $is_create);
            //}
        }
        
    }

    public function perform_user_actions( $user_id, $user_data ) {
        $args = ['rao_user_create','rao_user_sync'];
        
        $this->integration_exists($args);
        if( !empty($this->integration_exist) ) {
            //if( in_array('rao_user_create', $this->integration_exist) || in_array('rao_user_sync', $this->integration_exist)) {
            	
                $this->process_user( $user_id, false);
            //}
        }
        
        
    }


    public function integration_exists( $args){
        //$integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
        //$this->integration_exist = $integration_model->where('wp_source','IN',implode(",",$args))->get()->toArray();
        //old
        //temporary
    

        global $wpdb;

        $table = $wpdb->prefix.'rgsi_integrations';

        $where = ' where wp_source IN (';

        foreach( $args as $arg ) {

            $where .= '"'.$arg.'",';

        }

        $where = rtrim($where,',');

        $where .= ')';

        $query = 'Select * from '.$table.$where ;

        $this->integration_exist = $wpdb->get_results($query, 'ARRAY_A');

        
         
    }

    public function process_user($user_id, $update = false) {
        $user = get_user_by('ID', $user_id);
        foreach( $this->integration_exist as $key => $integration_data ){
            $parsed_data = [];
            if( !$integration_data['is_active'])
            continue;
            
            $this->process_data_into_sheets($integration_data, 'user', $user, $update);
        }

    }

    public function process_data_into_sheets($integration_data, $object_type, $object, $update) {
        if( $object == 'post')
        $wp_source_key = $this->get_wp_source_key( $post );
        else
        $wp_source_key = 'rao_user_';
        if(isset( $integration_data['mapped_data'])) {
            $mapped_data = maybe_unserialize( $integration_data['mapped_data'] );
            $sync_column = $integration_data['sync_column'];
            if( $object_type == 'post')
            $user = get_user_by('id', $object->post_author);
            else
            $user = $object;
            $basic = '';
            $custom_map_keys = [];
            if( is_array( $mapped_data ) ) {
                foreach( $mapped_data as $sheet_index=>$map_keys) {
                    $map_key_data = [];
                    foreach( $map_keys as $map_key) {
                        if( strpos( $map_key, 'basic_')) {
                            //basic
                            $original_key = str_replace( '_basic_','', $map_key );
                            if($original_key == 'id')
                            $original_key = strtoupper($original_key);
                            $map_key_data[] = $object->{$original_key};
                            if( $basic == '' && $sync_column !== '' && $sync_column == $sheet_index ) 
                            {
                                $basic = $original_key;
                            } 

                        }else if( strpos( $map_key, 'linked_' ) ) {
                            //linked
                            $original_key = str_replace( '_linked_', '', $map_key );
                            
                            $map_key_data[] = $user->{$original_key};
                        }else if ( strpos( $map_key, 'ustom' ) ) {
                            //get database table
                            //get column
                            $custom_map_keys[$sheet_index][] = $map_key;
                        } else {
                            $original_key = str_replace( '_meta_', '', $map_key );
                            if( $object == 'post')
                            $map_key_data[] = get_post_meta($object->ID, $original_key, true);
                            else
                            $map_key_data[] = get_user_meta($object->ID, $original_key, true);

                        }
                    }
                    $map_key_data = implode(" ",$map_key_data);
                    $parsed_data[$sheet_index] = $map_key_data;
                }
            }
        }
        $parsed_data = apply_filters('rgsi_mapped_data', $parsed_data, $object, $custom_map_keys );
        /* temporary
        $is_employee = in_array('employee',$object->roles);
        if( $is_employee ) {
            $parsed_data["G1"] = 'employee';
            $parsed_data["E1"] = get_user_meta($parsed_data["F1"],'company_name', true);
        }
        else {
            $parsed_data["G1"] = 'company';
        }
        $parsed_data["H1"] = current_time('Y-m-d h:i:s');
        */
        $ss_id_data = raogsi_get_decoded_ss_id( $integration_data['ss_source'] );
        if( !is_wp_error( $ss_id_data ) ) {
            $integration_type = raogsi_get_integration_type( $integration_data['wp_source'], $wp_source_key );
            $this->process_parsed_data($ss_id_data, $integration_type, $integration_data['sync_column'], $basic, $parsed_data, $update, $object );
        }
    }

    public function perform_sheet_actions( $post_id, $post, $update ) {
        $wp_source_key = $this->get_wp_source_key( $post );
        
        if( $wp_source_key ) {
            $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
            
            $integration_exist = $integration_model->where('wp_source','LIKE','%'.$wp_source_key.'%')->get()->toArray();
            
            if( !empty( $integration_exist ) ) {
                foreach( $integration_exist as $key => $integration_data ) {
                    $parsed_data = [];
                    if( !$integration_data['is_active' ] )
                    continue;
                    if( isset( $integration_data['mapped_data'])) {
                        $mapped_data = maybe_unserialize( $integration_data['mapped_data'] );
                        $sync_column = $integration_data['sync_column'];
                        $user = get_user_by('id', $post->post_author);
                        $basic = '';
                        $custom_map_keys = [];
                        if( is_array( $mapped_data ) ) {
                            foreach( $mapped_data as $sheet_index => $map_keys ) {
                                $map_key_data = [];
                                foreach( $map_keys as $map_key ) {
                                
                                    if( strpos( $map_key, 'basic_' ) ) {
                                        //basic
                                        $original_key = str_replace( '_basic_','', $map_key );
                                        if($original_key == 'id')
                                        $original_key = strtoupper($original_key);
                                        $map_key_data[] = $post->{$original_key};
                                        if( $basic == '' && $sync_column !== '' && $sync_column == $sheet_index ) 
                                        {
                                            $basic = $original_key;
                                        }

                                    } else if( strpos( $map_key, 'linked_' ) ) {
                                        //linked
                                        $original_key = str_replace( '_linked_', '', $map_key );
                                        
                                        $map_key_data[] = $user->{$original_key};
                                    } else if ( strpos( $map_key, 'ustom' ) ) {
                                        //get database table
                                        //get column
                                        $custom_map_keys[$sheet_index][] = $map_key;
                                        
                                        
                                        
                                    } else {
                                        //meta
                                        $original_key = str_replace( '_meta_', '', $map_key );
                                    
                                        $map_key_data[] = get_post_meta($post->ID, $original_key, true);
                                        
                                    }
                                    
                                }
                                $map_key_data = implode(" ",$map_key_data);
                                $parsed_data[$sheet_index] = $map_key_data;
                            }

                        }
                    }
                    
                    $parsed_data = apply_filters('rgsi_mapped_data', $parsed_data, $post, $custom_map_keys );
                    
                    $ss_id_data = raogsi_get_decoded_ss_id( $integration_data['ss_source'] );
                    if( !is_wp_error( $ss_id_data ) ) {
                        $integration_type = raogsi_get_integration_type( $integration_data['wp_source'], $wp_source_key );
                        $this->process_parsed_data($ss_id_data, $integration_type, $integration_data['sync_column'], $basic, $parsed_data, $update, $post );
                    }
                }
            }
        }
        
    }

    public function process_parsed_data( $ss_id_data, $integration_type, $sync_column, $basic, $parsed_data, $update, $post ) {
        
        if( $integration_type == 'create' || $integration_type == 'update' || ( $integration_type == 'sync' && !$update ) ) {
        
            $this->googlesheet->raogsi_append_row( $ss_id_data[0], $ss_id_data[1], $parsed_data);
        }else if( $integration_type == 'sync' && $update !== 'delete') {
        
            $sync_index = str_replace( "1", "", $sync_column );
            $column_data = $this->googlesheet->raogsi_fetch_data( $ss_id_data[0], $ss_id_data[1], $parsed_data, $sync_index);
            
            if(isset($column_data->values)) {
                if(isset( $column_data->values[0]) && is_array($column_data->values[0])) {
                    
                    $found_key =  array_search( $post->{$basic}, $column_data->values[0]);
                    if( $found_key === 0 || $found_key )
                    $found_key = "A".++$found_key;
                    else
                    $found_key = false;
                    if( !$found_key )
                    $this->googlesheet->raogsi_append_row( $ss_id_data[0], $ss_id_data[1], $parsed_data);
                    else
                    $this->googlesheet->raogsi_update_row( $ss_id_data[0], $ss_id_data[1], $parsed_data, $found_key);
                }
            }
        } else if( $update == 'delete' ) {
            
            if( $integration_type == 'sync' ) {
                //remove row
                
                $sync_index = str_replace( "1", "", $sync_column );
                $column_data = $this->googlesheet->raogsi_fetch_data( $ss_id_data[0], $ss_id_data[1], $parsed_data, $sync_index);
                
                if(isset($column_data->values)) {
                    if(isset( $column_data->values[0]) && is_array($column_data->values[0])) {
                      
                        $found_key =  array_search( $post->{$basic}, $column_data->values[0]);
                        if( $found_key === 0 || $found_key )
                        $found_key = ++$found_key;
                        else
                        $found_key = false;
                        
                        $this->googlesheet->raogsi_delete_row( $ss_id_data[0], $ss_id_data[1], $parsed_data, $found_key);
                    }
                }
            } else {
                //append row
               
                $this->googlesheet->raogsi_append_row( $ss_id_data[0], $ss_id_data[1], $parsed_data);
            }
        }
    }

    /**
     * Perform delete actions
     */
    public function perform_delete_actions( $post_id, $post ) {
       
        //if it is sync we would delete a row otherwise append a row
        $wp_source_key = $this->get_wp_source_key( $post, true );
        if( $wp_source_key ) {
            $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
            $integration_exist = $integration_model->where('wp_source','LIKE','%'.$wp_source_key.'%')->get()->toArray();

            if( !empty( $integration_exist ) ) {
                foreach( $integration_exist as $key => $integration_data ) {
                    $parsed_data = [];
                    
                    if( !$integration_data['is_active' ] || strpos( $integration_data['wp_source'],'create') || strpos( $integration_data['wp_source'],'update') )
                    continue;
                    
                    if( isset( $integration_data['mapped_data'])) {
                        $mapped_data = maybe_unserialize( $integration_data['mapped_data'] );
                        $sync_column = $integration_data['sync_column'];
                        $user = get_user_by('id', $post->post_author);
                        $basic = '';
                        if( is_array( $mapped_data ) ) {
                            foreach( $mapped_data as $sheet_index => $map_keys ) {
                                $map_key_data = [];
                                foreach( $map_keys as $map_key ) {
                                    
                                    if( strpos( $map_key, 'basic_' ) ) {
                                        //basic
                                        $original_key = str_replace( '_basic_','', $map_key );
                                        if($original_key == 'id')
                                        $original_key = strtoupper($original_key);
                                        $map_key_data[] = $post->{$original_key};
                                        if( $basic == '' && $sync_column !== '' && $sync_column == $sheet_index ) 
                                        {
                                            $basic = $original_key;
                                        }

                                    } else if( strpos( $map_key, 'linked_' ) ) {
                                        //linked
                                        $original_key = str_replace( '_linked_', '', $map_key );
                                        $map_key_data[] = $user->{$original_key};
                                    } else {
                                        //meta
                                        $original_key = str_replace( '_meta_', '', $map_key );
                                        $map_key_data[] = get_post_meta($post->id, $original_key, true);
                                    }
                                    
                                }
                                $map_key_data = implode(" ",$map_key_data);
                                $parsed_data[$sheet_index] = $map_key_data;
                            }
                        }
                    }
                    $ss_id_data = raogsi_get_decoded_ss_id( $integration_data['ss_source'] );
                    if( !is_wp_error( $ss_id_data ) ) {
                        
                        $integration_type = raogsi_get_integration_type( $integration_data['wp_source'], $wp_source_key );
                        $this->process_parsed_data($ss_id_data, $integration_type, $integration_data['sync_column'], $basic, $parsed_data, 'delete', $post );
                    }
                }
            }
        }
        
    }

}