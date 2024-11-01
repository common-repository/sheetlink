<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
extract($data);
$button_text = $title ? __('Update Integration', 'gsheets-connector' ) : __('Save Integration', 'gsheets-connector');
$is_checked = $is_active ? 'checked="checked"' : "";
?>
<div class="wrap">
    <input type="hidden" id="gsi_page_redirect" value="<?php echo esc_url(admin_url('admin.php?page=raogsi'));?>" />
    <h1><?php $title ? esc_attr_e( 'Edit Integration', 'gsheets-connector' ) : esc_attr_e( 'New Integration', 'gsheets-connector' );?></h1>
    <div class="ss-notice"></div>
    <div class="raogsi-integration-section raogsi-new-integration">
        <form id="raogsi-save-integration" action="<?php echo esc_url(admin_url('admin-post.php'));?>" method="POST">
            <input type="hidden" name="wp_source_nonce" id="wp_source_nonce" value="<?php echo esc_attr(wp_create_nonce('wp-source-nonce'));?>" />
            <input type="hidden" name="ss_source_nonce" id="ss_source_nonce" value="<?php echo esc_attr(wp_create_nonce('ss-source-nonce'));?>" />
            <input type="hidden" name="save_integration_nonce" id="save_integration_nonce" value="<?php echo esc_attr(wp_create_nonce('wp-integration-nonce'));?>" />
            <input type="hidden" name="action" value="save_integration" />
            <input type="hidden" name="mappings" id="mappings" value="<?php echo esc_attr($mappings); ?>" />
            <input type="hidden" name="integ_id" value="<?php echo esc_attr($integ_id); ?>" />
            <input type="hidden" id="sync_key" name="sync_key" value="<?php echo esc_attr($sync_column); ?>" />
            <!--<input type="hidden" name="status" value="new" />-->
            <table class="widefat">
                <tbody>
                    <tr>
                        <th scope="row" class="row-title">
                            <label for="raogsi-enable"><?php esc_attr_e('Enable Integration', 'gsheets-connector');?></label>
                        </th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" value="yes" class="raogsi-integration-toggle" <?php echo esc_attr($is_checked, '') ?> name="is_active" />
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="row-title">
                            <label for="raogsi-name"><?php esc_attr_e('Integration Title', 'gsheets-connector');?></label>
                        </th>
                        <td>
                            <input type="text" required name="title" id="raogsi-title" value="<?php echo esc_attr($title) ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="row-title">
                            <label for="raogsi-wp-source"><?php esc_attr_e('Wordpress Source', 'gsheets-connector');?></label>
                        </th>
                        <td>
                            <select name="wp_source" class="raogsi-wp-source" id="raogsi_wp_source" required>
                                <?php
                                    echo '<option value="">Select Sources</option>';
                                    ?>
                                    <!--
                                    <optgroup label="Post">
                                        <option data-post-type="post" value="rao_post_create" <?php selected( 'rao_post_create',$wp_source); ?>>Post Create</option>
                                        <option data-post-type="post" value="rao_post_update" <?php selected( 'rao_post_update',$wp_source); ?>>Post Update</option>
                                        <option data-post-type="post" value="rao_post_delete" <?php selected( 'rao_post_delete',$wp_source); ?>>Post Delete</option>
                                        <option data-post-type="post" value="rao_post_sync" <?php selected( 'rao_post_sync',$wp_source); ?>>Post Sync</option>
                                    </optgroup>
                                    <optgroup label="Page">
                                        <option data-post-type="page" value="rao_page_create" <?php selected( 'rao_page_create',$wp_source); ?>>Page Create</option>
                                        <option data-post-type="page" value="rao_page_update" <?php selected( 'rao_page_update',$wp_source); ?>>Page Update</option>
                                        <option data-post-type="page" value="rao_page_delete" <?php selected( 'rao_page_delete',$wp_source); ?>>Page Delete</option>
                                        <option data-post-type="page" value="rao_page_sync" <?php selected( 'rao_page_sync',$wp_source); ?>>Page Sync</option>
                                    </optgroup>
                                    -->
                                    <?php
                                    if( is_array( $this->customPostTypes ) ):
                                        
                                        foreach( $this->customPostTypes as $custom_post_type => $actions ) {
                                            echo '<optgroup label="'.esc_attr(ucwords($custom_post_type)).'">';
                                            foreach( $actions as $aid => $action_data) {
                                                foreach( $action_data as $id => $value) {
                                                    echo '<option data-post-type="'.esc_attr($custom_post_type).'" value="'.esc_attr($id).'" '.selected($id,$wp_source).'>'.esc_attr($value).'</option>';
                                                }
                                            }
                                            echo '</optgroup>';
                                        }
                                    endif;
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="row-title">
                            <label for="raogsi-wp-source"><?php esc_attr_e('Spreadsheets & Worksheets', 'gsheets-connector');?></label>
                        </th>
                        <td>
                            <select name="ss_source" class="raogsi-ss-source" id="raogsi-ss-source" required>
                                <option value=""><?php esc_attr_e("Select Sheet", 'gsheets-connector') ?></option>
                                <?php
                                    if( is_array( $this->spreadsheets_worksheets ) ) :
                                        foreach( $this->spreadsheets_worksheets as $spreadsheet_key => $spreadsheet_data ) {
                                            if( isset( $spreadsheet_data[0] ) && isset( $spreadsheet_data[1] ) ) {
                                                $sheet_title = $spreadsheet_data[0];
                                                if(is_array($spreadsheet_data[1])) {
                                                    foreach($spreadsheet_data[1] as $sheet_index => $sheet_name ) {
                                                        $value = base64_encode($spreadsheet_key.",".$sheet_name);
                                                        $name = $sheet_title ." ⯈ " .$sheet_name;
                                                        echo '<option value="'.esc_attr($value).'" '.selected($value, $ss_source).'>'.esc_attr($name).'</option>';
                                                    }
                                                }
                                            }

                                        }
                                    endif;
                                ?>
                            </select>
                            <img src=<?php echo esc_url(RAOGSI_ASSETS."/images/loader.gif"); ?> alt="loading" class="gsiLoader" style="display:none;" />
                        </td>
                    </tr>
                </tody>
                <tbody>
                    <tr style="background-color: rgb(241, 241, 241);">
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
                <tbody class="raogsi-integration-body" style="display:none;">
                </tbody>
            </table>
            <div class="raogsi-sync-div" style="display:none;">
                <label><h5><?php esc_attr_e('Primary Column', 'gsheets-connector'); ?></h5></label>
                <select id="sync-column" name="sync_column">
                    <option value="">Select Sync column</option>
                </select>
            </div>
            <div class="raogsi-buttons">
                <input type="submit" class="button button-primary" value="<?php echo esc_attr($button_text); ?>" />
                <input type="submit" class="button button-secondary" value="<?php echo esc_attr('Cancel', 'gsheets-connector'); ?>" />
            </div>
        </form>
    </div>
</div>