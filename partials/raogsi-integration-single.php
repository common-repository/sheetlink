<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<tr>
    <td><?php echo esc_attr( $column_key );?> : <?php echo esc_attr( $column_title); ?></td>
    <td>
        <select class="wpEventDataSelect" name="mapped_data[<?php echo esc_attr($column_key); ?>][]" multiple="multiple" style="width:75%;">
            <?php 
                if( !empty( $wp_source ) ) {
                    foreach( $wp_source as $source_key => $source_title ) {
                        $selected = "";
                        if(is_array($mapped_values) && in_array($source_key,$mapped_values))
                        $selected = 'selected="selected"';
                        echo '<option value="'.esc_attr($source_key).'" '.esc_attr($selected).'>'.esc_attr($source_title).'</option>';
                    }
                }
            ?>
        </select>
    </td>
</tr>