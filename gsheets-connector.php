<?php
/**
 * GSheets Connector
 *
 * @package           RaoSpreadsheet
 * @author            Rao Information Technology
 * @copyright         2023 Rao Information Technology
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Gsheets Connector
 * Plugin URI:        https://wordpress.org/plugins/sheetlink/
 * Description:       Auto Sync Posts, Pages & Custom post type data into CSV
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Rao Information Technology
 * Author URI:        https://raoinformationtechnology.com
 * Text Domain:       gsheets-connectors
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
// require all of our src files
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
define( 'RAOGSI_FILE', __FILE__ );
define( 'RAOGSI_PATH', dirname( RAOGSI_FILE ) );
define( 'RAOGSI_INCLUDES', RAOGSI_PATH . '/includes' );
define( 'RAOGSI_URL', plugins_url( '', RAOGSI_FILE ) );
define( 'RAOGSI_ASSETS', RAOGSI_URL . '/assets' );
define( 'RAOGSI_VIEWS', RAOGSI_PATH . '/partials' );
require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');
register_activation_hook( __FILE__,  'raogsi_load_tables' ); 
function raogsi_load_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
 
    $table = "CREATE TABLE {$wpdb->prefix}rgsi_integrations (
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(1000) NOT NULL,
                ss_source varchar(2500) NOT NULL,
                wp_source varchar(2500) NOT NULL,
                mapped_data longtext NOT NULL,
                sync_column varchar(25) NOT NULL,
                is_active tinyint(1) NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id)
            )";
 
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $table );
}

//if( is_admin() ) {
require_once(plugin_dir_path(__FILE__) . '/includes/functions.php');
if( !class_exists( 'Core') ) {
$spreadheet = new RAOGSI_COMPOSER\Admin\RGSIGoogleSheet();
new RAOGSI_COMPOSER\Admin\RGSICore( $spreadheet );
new RAOGSI_COMPOSER\Admin\RGSIAdminAjax( $spreadheet );
}
//}