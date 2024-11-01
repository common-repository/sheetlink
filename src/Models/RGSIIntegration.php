<?php
namespace RAOGSI_COMPOSER\Models;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class RGSIIntegration extends RGSIModel {    
    protected $table = 'rgsi_integrations';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'ss_source',
        'wp_source',
        'mapped_data',
        'sync_column',
        'is_active'
    ];

}