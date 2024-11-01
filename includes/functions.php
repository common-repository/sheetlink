<?php
if( !function_exists('raogsi_get_google_service_account_credentials') ) {
    function raogsi_get_google_service_account_credentials() {
        $credentials = get_option( 'raogsi_credentials', false );
        return $credentials;
    }
}
if( !function_exists('raogsi_get_decoded_ss_id') ) {
    function raogsi_get_decoded_ss_id( $ss_id ) {
        $decoded_ss_id = base64_decode( wp_unslash( $ss_id ) );
        $ss_id = explode( ",", $decoded_ss_id );
        if( count( $ss_id ) == 2 ) {
            return $ss_id;
        } else {
            return new WP_Error( 'raogsi_invalid', 'Invalid Spreadsheet ID' );
        }
    }
}
if( !function_exists('raogsi_get_worksheets') ) {
function raogsi_get_worksheets() {
    
    $spreadsheets_worksheets = false;
    $spreadsheets_worksheets = get_transient('raogsi_worksheet_list');
    if(false == $spreadsheets_worksheets ) {
        $ss_data = new RAOGSI_COMPOSER\Admin\RGSIGoogleSheet();
        $ss_data = $ss_data->raogsi_spreadsheetsAndWorksheets();
        if( !is_wp_error( $ss_data ))
        {
            $spreadsheets_worksheets = $ss_data[1];
            set_transient('raogsi_worksheet_list', $spreadsheets_worksheets, 6000);
            
        } else {
            $spreadsheets_worksheets = $ss_data;
        }
    }
    return $spreadsheets_worksheets;
}
}
if( !function_exists('raogsi_render_checkbox') ) {
function raogsi_render_checkbox($id, $data_attr, $is_active) {

    $is_checked = $is_active ? 'checked="checked"' : "";

    ob_start();
    ?>
    <label class="switch">
        <input type="checkbox" id="<?php echo esc_attr($id);?>" class="<?php echo esc_attr($id);?>" <?php echo esc_attr($is_checked) ?> name="<?php echo esc_attr($id);?>" data-intg_id=<?php echo esc_attr($data_attr); ?>>
        <span class="slider round"></span>
    </label>
    <?php
    $html = ob_get_clean();
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
}
if( !function_exists('raogsi_get_integration_type') ) {
function raogsi_get_integration_type( $wp_source, $key ) {
    $type = str_replace($key,"",$wp_source);
    return $type;
}
}
if ( ! function_exists( 'raogsi_strip_tags_deep' ) ) {

    /**
     * Strip tags from string or array
     *
     * @param  mixed  array or string to strip
     *
     * @return mixed stripped value
     */
    function raogsi_strip_tags_deep( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $val ) {
                $value[ $key ] = raogsi_strip_tags_deep( $val );
            }
        } elseif ( is_string( $value ) ) {
            $value = wp_strip_all_tags( $value );
        }

        return $value;
    }
}

if ( ! function_exists( 'raogsi_trim_deep' ) ) {

    /**
     * Trim from string or array
     *
     * @param  mixed  array or string to trim
     *
     * @return mixed timmed value
     */
    function raogsi_trim_deep( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $val ) {
                $value[ $key ] = raogsi_trim_deep( $val );
            }
        } elseif ( is_string( $value ) ) {
            $value = trim( $value );
        }

        return $value;
    }
}
if( !function_exists('raogsi_sanitize_array') ) {
function raogsi_sanitize_array( $array_values ) {
    $keys = array_keys($array_values);
    $keys = array_map('sanitize_key', $keys);

    $values = array_values($array_values);
    $values = array_map('sanitize_text_field', $values);

    $array_values = array_combine($keys, $values);
    return $array_values;
}
}