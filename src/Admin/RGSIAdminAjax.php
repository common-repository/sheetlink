<?php
namespace RAOGSI_COMPOSER\Admin;

use RAOGSI_COMPOSER\Framework\Traits\RGSIHooker;
use RAOGSI_COMPOSER\Framework\Traits\RGSIAjax;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class RGSIAdminAjax {
    use RGSIHooker;
    use RGSIAjax;

    protected $domain;

    protected $googlesheet;

    protected $action_messages;


    public function __construct( $googlesheet ) {
        $this->googlesheet = $googlesheet;
        $this->action( 'wp_ajax_load_wp_source_data', 'load_wp_source_data' );
        $this->action( 'wp_ajax_load_ss_columns', 'load_ss_columns' );
        $this->action( 'wp_ajax_save_integration', 'save_integration' );
        $this->action( 'wp_ajax_ss_toggle_integration', 'ss_toggle_integration' );
        $this->action( 'wp_ajax_delete_integration', 'ss_delete_integration' );
        
    }

    /**
     * Messages
     */
    public function action_messages() {
        $messages['success'] = [
            'updated' => __( 'Integration Updated Successfully', 'gsheets-connector' ),
            'created' => __( 'Integration Created Successfully', 'gsheets-connector' ),
            'deleted' => __( 'Integration Deleted Successfully', 'gsheets-connector' )
        ];
        $messages['error'] = [
            'updated' => __( 'Integration ID already exist', 'gsheets-connector' ),
            'created' => __( 'Some error while creating the Integration', 'gsheets-connector' ),
            'deleted' => __( 'Some error while deleting the Integration', 'gsheets-connector' )
        ];
        return $messages;
    }

    /**
     * Load WP Source Data
     */
    public function load_wp_source_data() {
        check_ajax_referer( 'wp-source-nonce', 'nonce_data' );
        $post_obejct = sanitize_text_field(wp_unslash($_GET['post_object']));
        if( !isset( $post_obejct ) || ( $post_obejct == "" ) )
        $this->send_error("Post Object can not be empty");

        if( $post_obejct == 'user' ){

            $wp_source = new DataSets\GSI_USER( $post_obejct );

            $source_options = $wp_source->get_options();

        }else if( post_type_exists( $post_obejct ) ) {
              $wp_source = new DataSets\GSI_POST( $post_obejct );
              $source_options = $wp_source->get_options();
        }
        
        $this->send_success( $source_options );
    }

    /**
     * Load SS Colunbs
     */
    public function load_ss_columns() {
        check_ajax_referer( 'ss-source-nonce', 'nonce_data' );
        $ss_id = sanitize_text_field(wp_unslash($_GET['ss_id']));
        if( !isset( $ss_id ) || ( $ss_id == "" ) )
        $this->send_error("Spreadsheet ID can not be empty");
        
        $decoded_ss_id = raogsi_get_decoded_ss_id( $ss_id );
        
        if( is_wp_error( $decoded_ss_id ) ) {
            $this->send_error( 'Invalid SS ID' );
        }

        $spreadsheet_id = $decoded_ss_id[0];
        $worksheet_id = $decoded_ss_id[1];

        $spreadsheet_columns = $this->googlesheet->raogsi_columnTitle( $worksheet_id, $spreadsheet_id);
        
        if( is_wp_error( $spreadsheet_columns ) && empty( $spreadsheet_columns ) ) {
            $this->send_error( 'Invalid Columns' );
        }
        $ssColumns = [];
        //$wp_source = sanitize_text_field(wp_unslash($_GET['wp_source']));
        //temporary
        $wp_source = $_GET['wp_source'];
        $mappings = isset($_GET["mappings"]) ? maybe_unserialize(stripslashes($_GET['mappings'])) : "";
        //sanitizing array keys and values separately
        //temporary
        //if(is_array($mappings))
        //$mappings = \raogsi_sanitize_array( $mappings );
        $wp_source_key = sanitize_text_field( $_GET['wp_source_id' ] );
        
        if( strpos( $wp_source_key,"sync" ) )
        $is_sync = true;
        else 
        $is_sync = false;
        
        ob_start();
        include_once RAOGSI_PATH.'/partials/raogsi-integration-heading.php';
        foreach( $spreadsheet_columns as $column_key => $column_title ) {
            
            if(isset($mappings[$column_key]))
            $mapped_values = $mappings[$column_key];
            else
            $mapped_values = '';
            include RAOGSI_PATH.'/partials/raogsi-integration-single.php';
        }
        include_once RAOGSI_PATH.'/partials/raogsi-integration-heading.php';
        $spreadsheet_data = ob_get_clean();
        $sync_options = '';
        if( $is_sync ) {
            $sync_options .= '<option value="">'.__('Select key', 'gsheets-connector' ).'</option>';
            foreach( $spreadsheet_columns as $column_key => $column_title ) {
                $sync_options .= '<option value="'.esc_attr( $column_key ).'">'.esc_attr($column_title).'</option>';
            }
        }
        $send_data['spreadsheet_data'] = $spreadsheet_data;
        $send_data['sync'] = $sync_options;
        
        $this->send_success( wp_json_encode($send_data) );
    }

    /**
     * Save Integration
     */
    public function save_integration() {
        check_ajax_referer( 'wp-integration-nonce', 'save_integration_nonce' );
        $this->action_messages = $this->action_messages();
        //rao save integration
        //have eliminated the kets that are bit requiredall of the values in array are required for further processing so 
        //temporary starts
        unset($_POST['action']);

        unset($_POST['ss_source_nonce']);

        unset($_POST['wp_source_nonce']);

        unset($_POST['save_integration_nonce']);
        //temporary ends
        $posted = map_deep( wp_unslash( $_POST ), 'sanitize_text_field' );
        
        $integration = new RGSIIntegration();
        $result = $integration->process_integration( $posted );
        
        
        if( isset( $posted['integ_id'] ) && $posted['integ_id'] !== '' )
        $update = true;
        else
        $update = false;
        
        if( $update ) {
            if( $result )
            $this->send_success( $this->action_messages['success']['updated'] );
            else
            $this->send_error( $this->action_messages['error']['updated'] );
        } else {
            if( $result ) {
            $admin_url = admin_url('admin.php?page=raogsi&action=edit&id='.$result);
            $this->send_success( $admin_url );
            }
            else
            $this->send_error( $this->action_messages['error']['created'] );
        }
       
    }

    /**
     * Toggle Integratiom
     */
    public function ss_toggle_integration() {
        //temporary
        //check_ajax_referer( 'wp-integration-nonce', 'save_integration_nonce' );
       
        $id = sanitize_text_field( $_POST['id'] );
        $status = rest_sanitize_boolean( $_POST['toggle'] );

        $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
            
        $integration = $integration_model::firstOrNew( [ 'id' => $id ] );
        
        if( $integration ) {
            $integration->update(['is_active' => $status ]);
            $msg = __('Integration Updated Successfully', 'gsheets-connector' );
            $this->send_success( $msg );
        } else {
            $msg = __('Invalid Integration', 'gsheets-connector' );
            $this->send_error( $msg );
        }
    }

    /**
     * SS Delete Integration
     */
    public function ss_delete_integration() {
        check_ajax_referer( 'wp-integration-nonce', 'save_integration_nonce' );
        $this->action_messages = $this->action_messages();
        $id = sanitize_text_field( $_POST['id'] );
        $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
        $integration = $integration_model::find( $id );
        
        if( $integration ) {
            $destroy = $integration->delete();
            if( $destroy )
            {
                $this->send_success( $this->action_messages['success']['deleted'] );
            } else {
                $this->send_error( $this->action_messages['error']['deleted'] );
            }
        } else {
            $this->send_error( $this->action_messages['error']['global'] );
        }
    }
}