<?php
namespace RAOGSI_COMPOSER\Admin;
use WP_List_Table;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class RGSIIntegrationListTable extends \WP_List_Table {
    public function __construct() {
        global $status, $page;

        parent::__construct( [
            'singular' => 'shift',
            'plural'   => 'shifts',
            'ajax'     => false,
        ] );
    }

    public function get_table_classes() {
        return [ 'widefat', 'fixed', 'striped', 'integration-list-table', $this->_args['plural'] ];
    }

    public function get_columns() {
        $columns = [
            /*'cb'              => '<input type="checkbox" />',*/
            'title'            => __( 'Title', 'gsheets-connector' ),
            'wp_source'        => __( 'Wordpress Source', 'gsheets-connector' ),
            'ss_source'        => __( 'Spreadsheet & Worksheet', 'gsheets-connector' ),
            'mappings'         =>  __('Mappings', 'gsheets-connector'),
            'actions'          =>   __('Actions', 'gsheets-connector')
        ];

        return $columns;
    }

    /**
     * Set the bulk actions
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
           // 'delete_integration'  => __( 'Delete Integrations', 'gsheets-connector' ),
        ];

        return $actions;
    }

    /**
     * Prepare the class items
     *
     * @return void
     */
    public function prepare_items() {
        $columns               = $this->get_columns();
        $hidden                = [ ];
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $items_per_page        = 20;
        $current_page          = $this->get_pagenum();
        $offset                = ( $current_page - 1 ) * $items_per_page;
        $this->page_status     = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '2'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        // only ncessary because we have sample data
        $args = [
            'offset' => $offset,
            'number' => $items_per_page,
        ];

        $integration_model = new \RAOGSI_COMPOSER\Models\RGSIIntegration();
        $integration_count = $integration_model->get()->count();
        
        $integrations = $integration_model->skip(0)->take(20)->get()->toArray();
        $this->items =  $integrations;

        $this->set_pagination_args( [
            'total_items' => $integration_count,
            'per_page'    => $items_per_page,
        ] );



    }

    public function column_default( $item, $column_name ) {
        
       switch( $column_name ) {
            case 'title' :
                    $actions           = [];
                    $edit_url        = admin_url('admin.php?page=raogsi&action=edit&id='.$item['id']);
                    $delete_url        = admin_url('admin.php?page=raogsi&action=delete&id='.$item['id']);
                    $actions['edit']   = sprintf( '<a href="%s" class="submitedit" data-id="%d" title="%s">%s</a>', $edit_url, $item['id'], __( 'Edit this item', 'gsheets-connector' ), __( 'Edit', 'gsheets-connector' ) );
                    $actions['delete'] = sprintf( '<a href="%s" class="submitdelete" data-id="%d" title="%s">%s</a>', $delete_url, $item['id'], __( 'Delete this item', 'gsheets-connector' ), __( 'Delete', 'gsheets-connector' ) );

                    printf( '<strong>%1$s</strong> %2$s',  esc_html( $item[$column_name] ), wp_kses_post( $this->row_actions( $actions ) ) );
                    break;
            case 'wp_source' :
                 $integration_options = new RGSIIntegrationOptions();
                 $cptOptions = $integration_options->get_allowed_cpts(true);
                 
                 if(isset( $cptOptions[$item['wp_source']]))
                 echo esc_attr($cptOptions[$item['wp_source']]);
                 else
                 echo '<span style="color:red;">Invalid</span>';
                break;
            case 'ss_source': 
                $decoded_ss_id = raogsi_get_decoded_ss_id( $item['ss_source']);
                if( !is_wp_error($decoded_ss_id))
                {
                    $spreadsheet_title = false;
                    $worksheet_list = raogsi_get_worksheets();
                    
                    if( !is_wp_error( $worksheet_list ) ) {
                        if(is_array($worksheet_list) && array_key_exists($decoded_ss_id[0], $worksheet_list)) {
                            $spreadsheet_title = $worksheet_list[$decoded_ss_id[0]][0];
                        } else {
                            $spreadsheet_title = $decoded_ss_id[0];
                        }
                    }
                    echo esc_attr($spreadsheet_title) . " => ".esc_attr($decoded_ss_id[1]);
                } else {
                    echo '<span style="color:red;">Invalid</span>';
                }
                break;
                case 'mappings':
                $integration_options = new RGSIIntegrationOptions();
                $post_types = $integration_options->get_custom_post_types();
                $mapped_data = maybe_unserialize( $item['mapped_data']);
                
                $post_types = array_keys( $post_types );
                $current_post_type = false;
                foreach( $post_types as $post_type ) {
                    $post_type_custom = "_".$post_type."_";
                    if( strpos( $item['wp_source'], $post_type_custom ) )
                    $current_post_type = $post_type;
                }
                
                $data_sets = new \RAOGSI_COMPOSER\Admin\DataSets\GSI_POST( $current_post_type );
                $dataOptions = $data_sets->get_options();
                if( is_array( $mapped_data ) ) {
                    foreach( $mapped_data as $sheet_index => $map_keys ) {
                        
                        $map_key_data = [];
                        if(is_array( $map_keys ) ) {
                            foreach( $map_keys as $key => $map_value ) {
                                if(isset($dataOptions[$map_value])) {
                                    $map_key_data[] = $dataOptions[$map_value];
                                } else {
                                    $map_key_data[] = $map_value . " ( NOT EXIST )";
                                }
                            }
                        }
                        echo "<b>".esc_attr( $sheet_index ). "</b> <=  " .esc_attr(implode(", ",$map_keys));
                        echo "<br/>";   
                    }
                }
                break;
                case 'actions':
                raogsi_render_checkbox('raogsi-integration-toggle',$item['id'], $item['is_active']);
                default :
                echo "-";
        } 
       
    }
}