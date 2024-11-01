<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
$delete_html = "";
if( $process_delete == "yes" ) {
    if( !$deleted ) {
        $msg = "Some error while removing the Integration";
        $delete_html = '<span class="notice notice-error">'.$msg.'</span>';
    } else {
        $msg = "Integration removed successfully!";
        $delete_html = '<span class="notice notice-success">'.$msg.'</span>';
    }
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_attr_e('Integrations', 'gsheets-connector') ?>
    </h1>
    <input type="hidden" name="save_integration_nonce" id="save_integration_nonce" value="<?php echo esc_attr(wp_create_nonce('wp-integration-nonce'));?>" />
    <a href="<?php echo esc_url(admin_url('admin.php?page=raogsi&action=new'));?>" class='page-title-action'><?php esc_attr_e('Add New Integration','gsheets-connector');?></a>
    <div class="raogsi-integrations-list">
    <div class="ss-notice"><?php echo wp_kses_post($delete_html);?></div>
        <?php
             require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
         
             $integration = new \RAOGSI_COMPOSER\Admin\RGSIIntegrationListTable();
             $integration->prepare_items();
             $integration->views();
             $integration->display();
        ?>
    </div>
</div>