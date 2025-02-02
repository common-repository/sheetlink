<?php
namespace RAOGSI_COMPOSER\Framework\Traits;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
/**
 * Class Hooker
 */
trait RGSIHooker {
    /**
     * Hooks a function on to a specific action.
     *
     * @param     $tag
     * @param     $function
     * @param int $priority
     * @param int $accepted_args
     */
    public function action( $tag, $function, $priority = 10, $accepted_args = 1 ) {
        add_action( $tag, [ $this, $function ], $priority, $accepted_args );
    }

    /**
     * Hooks a function on to a specific filter.
     *
     * @param     $tag
     * @param     $function
     * @param int $priority
     * @param int $accepted_args
     */
    public function filter( $tag, $function, $priority = 10, $accepted_args = 1 ) {
        add_filter( $tag, [ $this, $function ], $priority, $accepted_args );
    }
}