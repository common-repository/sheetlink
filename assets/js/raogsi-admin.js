;(function($) {
    'use strict';
    let wp_source_obj = '', ss_source_obj = '', sync_val = '';
    var loader = $('.gsiLoader');
    var connectionSection = $('.raogsi-integration-body');
    let switch_id = $('.raogsi-integration-toggle');
    var initialLoad = true;
    let currentWPSource =  [];
    let sync_key = $('#sync_key');
    let action = '';
    let searchParams = new URLSearchParams(window.location.search);
    
    if( searchParams.has( 'action' ) )
    {
        action = searchParams.get( 'action' );   
    }
    var RAOGSI_Integration = {
        /**
         * Initialize the events
         */
        initialize: function() {
            self = this;
            
            $('.wpEventDataSelect').select2({
                'placeholder' : 'Select an option',
                'multiple'  :   'multiple',
                'width'     :   'resolve'
            });
            wp_source_obj = $('.raogsi-wp-source');
            ss_source_obj = $('.raogsi-ss-source');
            RAOGSI_Integration.wpSource.reload();
            
            wp_source_obj.on( 'change', this.wpSource.reload );
            ss_source_obj.on( 'change', this.ssSource.reload );
            if(action == '')
            switch_id.on('change', this.integration.toggle );
            
            $('#raogsi-save-integration').on('submit', this.integration.create );
        },

        select2 : function( evt ){
            var element = evt.params.data.element;
            var $element = $(element);
            $element.detach();
            $(this).append($element);
            $(this).trigger('change');

        },

        block: function(e) {
            $('.integration-list-table').block({ 
                message: '<h1>Processing</h1>', 
                css: { border: '3px solid #a00' } 
            }); 
        },

        unblock: function(e) {
            $('.integration-list-table').unblock();
        },

        notices: {
            show: function(type,msg) {
                console.log(type);
                console.log(msg);
                var html;
                if(type == 'error') {
                    html = '<span class="notice notice-error">'+msg+'</span>';
                } else {
                    html = '<span class="notice notice-success">'+msg+'</span>';
                }
                $('.ss-notice').html(html).fadeIn();
            },
            hide: function() {
                $('.ss-notice').html('').fadeOut();
            }
        },  

        wpSource : {
            reload: function() {
                
                var data_source = wp_source_obj.val();
                
                if( !data_source )
                return;
                var post_object = wp_source_obj.find("option:selected").attr('data-post-type');
                $.ajax({
                    'type'  :   'GET',
                    'url'   :   ajaxurl,
                    data: {
                        'post_object': post_object,
                        'action' : 'load_wp_source_data',
                        'nonce_data': $('#wp_source_nonce').val(),
                        'wp_sourcee' : data_source,
                        
                    },
                    success: function(res) {
                        currentWPSource = res.data;
                        if(initialLoad)
                        {
                            RAOGSI_Integration.ssSource.reload();
                            initialLoad = false;
                        }
                    }
                });
            }
        },

        ssSource : {
            showloader: function() {
                loader.fadeIn();
                connectionSection.fadeOut();
            },
            hideloader: function() {
                loader.fadeOut();
                connectionSection.fadeIn();
            },
            reload: function() {
                console.log("ge");  
                console.log(currentWPSource);
                var ss_id = ss_source_obj.val();
                RAOGSI_Integration.ssSource.showloader();
                $.ajax({
                    'type'  :   'GET',
                    'url'   :   ajaxurl,
                    data: {
                        'action'    : 'load_ss_columns',
                        'ss_id'     :   ss_id,
                        'wp_source' : currentWPSource,
                        'nonce_data': $('#ss_source_nonce').val(),
                        'mappings' : $('#mappings').val(),
                        'wp_source_id' : wp_source_obj.val()
                    },
                    success: function(res) {
                        if(res.success) {
                        var html_data = JSON.parse(res.data);
                        console.log(html_data.sync);
                        $('.raogsi-integration-body').html( html_data.spreadsheet_data );
                        $('.wpEventDataSelect').select2();
                        
                        

                        if(html_data.sync) {
                            $('#sync-column').html(html_data.sync);
                        $('#sync-column').val(sync_key.val()).trigger('change');
                            $('.raogsi-sync-div').fadeIn();
                        } else {
                          
                            $('.raogsi-sync-div').fadeOut();
                        }
                        $('select').on("select2:select", RAOGSI_Integration.select2 );
                        }
                        RAOGSI_Integration.ssSource.hideloader();
                        
                    }
                });
            }
        },

        integration : {
            create: function(e) {
                e.preventDefault();
                RAOGSI_Integration.notices.hide();
                $.ajax({
                    'type'  :   'POST',
                    'url'   :   ajaxurl,
                    data    :   $(this).serialize(),
                    success: function(res) {
                        if(res.success) {
                        var sync_column_val = $('#sync-column').val();
                        sync_key.val(sync_column_val);
                        
                        if(action == 'edit')
                            RAOGSI_Integration.notices.show('success', res.data);
                            else {
                               
                            window.location.replace(res.data);
                            }
                        } else {
                            RAOGSI_Integration.notices.show('error', res.data);
                        }
                    }
                });
            },
            toggle: function(e) {
                e.preventDefault();
                RAOGSI_Integration.notices.hide();
                RAOGSI_Integration.block();
                var checked = $(this).is(":checked");
                var intg_id = $(this).data('intg_id');
                alert(checked);
                alert(intg_id);
                $.ajax({
                    'type'  :   'POST',
                    'url'   :   ajaxurl,
                     data   :   {
                        'action' : 'ss_toggle_integration',
                        'toggle' :  checked,
                        'nonce_data': $('#save_integration_nonce').val(),
                        'id'    :   intg_id
                     },
                     success: function(res) {
                         console.log("he;;");
                        console.log(res);
                        RAOGSI_Integration.unblock();
                        if( res.success ) {
                            RAOGSI_Integration.notices.show('success', res.data);
                            
                        } else {
                            RAOGSI_Integration.notices.show('error', res.data);
                        }
                     }
                });
            }
        }

    }
    $(function() {
        RAOGSI_Integration.initialize();

    });
})(jQuery);