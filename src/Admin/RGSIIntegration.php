<?php
namespace RAOGSI_COMPOSER\Admin;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class RGSIIntegration {
    /**
     * Integration Id
     * 
     * @var int
     */
    public $integration_id;

    /**
     * Integration object
     */
    protected $integration;

    protected $raogsi_integration_model = '\\RAOGSI_COMPOSER\\Admin\\Models\\Integration';

    /**
     * Integration Data
     * @var array
     */
    protected $data = [
        'title'     =>  '',
        'ss_source' => '',
        'wp_source' => '',
        'mapped_data'=> '',
        'sync_column'   =>  '',
        'is_active' =>  '',

    ];

    public function __construct() {

    }

    /***
     * Create/update Integration
     */
    public function create_integration( $args = [] ) {
        
        
        $posted = array_map( 'raogsi_strip_tags_deep', $args );
        $data   = array_map( 'raogsi_trim_deep', $posted );
        $data = wp_parse_args( $data, $this->data );
        $data['mapped_data'] = maybe_serialize($data['mapped_data']);
        $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
        $integration_obj = $integration_model->create( $data );
        return $data;
    }

    public function process_integration( $args = [] ) {
        
        $posted = array_map( 'raogsi_strip_tags_deep', $args );
        $data   = array_map( 'raogsi_trim_deep', $posted );
        $data = wp_parse_args( $data, $this->data );
        $update = false;
        
        $data['mapped_data'] = maybe_serialize($data['mapped_data']);
        $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
        if(isset($data['mappings']))
        unset($data['mappings']);
    if(isset($data['sync_key']))
        unset($data['sync_key']);

        if(isset($data['is_active']) && $data['is_active'] == 'yes')
        $data['is_active'] = true;
        else
        $data['is_active'] = false;
        
        if( isset($data['integ_id']) &&  $data['integ_id'] !== '' ) {
        
            $update = true;
            $integration_model = $this->get_integration( $integration_model, $data['integ_id'] );
            unset( $data['integ_id'] );
        
            if($integration_model)
            $integration_obj = $integration_model->update( $data );
            else
            return false;

        } else {
            unset( $data['integ_id'] );
            $integration_obj = $integration_model->insertGetId( $data );

        }
        return $integration_obj;
    }

    public function get_integration( $integration, $id ) {
            
        $single_integration = $integration::firstOrNew( [ 'id' => $id ] );
        return $single_integration;
    }
}