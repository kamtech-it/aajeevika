/**
 * IG_ES_Workflows Variables
 */

jQuery(function($) {

    IG_ES_Workflows.Variables = {

        $meta_box: $('#ig_es_workflow_variables'),


        init: function(){

           this.init_clipboard();

            $(document.body).on( 'change keyup', '.ig-es-workflow-variable-parameter', this.update_preview_field );
            $(document.body).on( 'keypress', 'input.ig-es-workflow-variable-parameter', this.restrict_parameter_chars );

            this.$meta_box.on( 'click', '.ig-es-workflow-variable', this.open_modal );
            this.$meta_box.on( 'click', '.ig-es-close-variable-info-popup', this.close_modal );

            $(document).keydown(function(e) {
                if ( 27 === e.keyCode ) {
                    IG_ES_Workflows.Variables.close_modal();
                }
            });
        },


        /**
         *
         */
        init_clipboard: function() {

            var clipboard = new ClipboardJS('.ig-es-clipboard-btn', {
                text: function(trigger) {
                    return $('#ig_es_workflow_variable_preview_field').text();
                }
            });

            clipboard.on('success', function(e) {

                $('.ig-es-clipboard-btn').html( ig_es_workflows_data.placeholder_copied_message );
                setTimeout(function(){
                    IG_ES_Workflows.Variables.close_modal();
                }, 500 );
            });

        },


        open_modal: function(){

            var ajax_data = {
                action: 'ig_es_modal_variable_info',
                variable: $(this).text(),
                security: ig_es_workflows_data.security,
            };

            $.post( ajaxurl, ajax_data, function( response ){
                $('#ig-es-variable-info-popup #ig-es-workflow-variable-info-body').html(response).show();
                IG_ES_Workflows.Variables.show_modal();
                IG_ES_Workflows.Variables.update_preview_field();
            });
        },

        show_modal: function() {
            jQuery('#ig-es-variable-info-popup').show();
        },

        close_modal: function() {
            $('#ig-es-variable-info-popup').hide('fast');
        },


        /**
         * Updates the variable preview text field
         */
        update_preview_field: function() {

            var $preview_field = $('#ig_es_workflow_variable_preview_field');
            var variable = $preview_field.data('variable');
            var parameters = [];

            $('.ig-es-workflow-variable-parameter').each(function(){

                var $param_row = $(this).parents('.ig-es-workflow-variables-parameter-row:first');

                // Check 'show' logic
                if ( $param_row.data('parameter-show') ) {

                    var show_logic = $param_row.data('parameter-show').split('=');

                    var $condition_field = $('.ig-es-workflow-variable-parameter[name="' + show_logic[0] + '"]');

                    if ( $condition_field.length && $condition_field.val() == show_logic[1] ) {
                        $param_row.show();
                    } else {
                        $param_row.hide();
                        return; // don't add parameter to preview
                    }
                }

                var param = {
                    name: $(this).attr('name'),
                    required: $param_row.data('is-required'),
                    value: $(this).val()
                };

                parameters.push( param );
            });

            var string = IG_ES_Workflows.Variables.generate_variable_string( variable, parameters );

            $preview_field.text( string );
        },


        /**
         *
         * @param variable
         * @param parameters
         */
        generate_variable_string: function( variable, parameters ) {

            var string = '{{ ' + variable;

            if ( parameters.length ) {
                var param_parts = [];

                $.each( parameters, function( i, param ) {

                    if ( param.value ) {
                        param_parts.push( param.name + ": '" + param.value + "'" );
                    }
                    else if ( param.required ) {
                        param_parts.push( param.name + ": '...'" );
                    }
                });


                if ( param_parts.length > 0 ) {
                    string += ' | ';
                    string += param_parts.join( ', ' );
                }
            }

            return string + ' }}';
        },


        /**
         *
         * @param e
         */
        restrict_parameter_chars: function(e) {

            var restricted = [ 39, 123, 124, 125 ];

            if ( $.inArray( e.which, restricted ) !== -1 ) {
                return false;
            }
        }

    };


    IG_ES_Workflows.Variables.init();

});