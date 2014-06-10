/**
 * Created by george on 12/11/13.
 */
var DataForm = (function() {
    'use strict';

    var my = {
        submit: function (element, event, options) {
            var params = options.params;
            var form_params = options.form_params;
            var $form = $(element).parents("form");
            var jq = this.jq;
            var form_key, key;
            if (form_params) {
                for (form_key in form_params) {
                    if (form_params.hasOwnProperty(form_key)) {
                        var form_value = form_params[form_key];
                        $form.attr(form_key, form_value);
                    }
                }
            }
            if (params) {
                for (key in params) {
                    if (params.hasOwnProperty(key)) {
                        var value = params[key];
                        $(jq(key)).attr("value", value);
                    }
                }
            }
            if ($(element).attr("type") !== "submit") {
                // if it is submit, just let it go
                $form.submit();
                event.preventDefault();
            }
        },
        validateThenSubmit: function(element, event, options) {
            var $form = $(element).parents("form");
            var jq = this.jq;
            var data = $form.serialize();
            var params = options.params;
            if (params) {
                data += "&" + $.param(params);
            }
            FlashHandler.json(options.flash_name, {
                url: options.validation_url,
                type: options.form_method.toUpperCase(),
                data: data,
                success: function(data, textStatus, jqXHR) {
                    $form.attr("action", options.form_action);
                    $form.submit();
                }
            });
            event.preventDefault();
        },
        refresh : function(element, event, options) {
            // to submit the form as AJAX we need to serialize the form to json and put it in the parameter string
            // the second part of this takes that result and puts it in the div with the same name as the form
            // ie, replace the form with a refreshed copy

            var $form = $(element).parents("form");
            var jq = this.jq;
            var data = $form.serialize();
            var params = options.params;
            if (params) {
                data += "&" + $.param(params);
            }
            FlashHandler.json(options.flash_name, {
                url: options.form_action,
                type: options.form_method.toUpperCase(),
                data: data,
                success: function(json, textStatus, jqXHR) {
                    $(jq(options.form_name)).html(json.html);
                }
            });
            event.preventDefault();
        },
        refreshSaveAs : function(element, event, options) {
            // NOTE: you need to require Blob.js and FileSaver.js before using this

            // to submit the form as AJAX we need to serialize the form to json and put it in the parameter string
            // the second part of this takes that result and puts it in the div with the same name as the form
            // ie, replace the form with a refreshed copy

            var $form = $(element).parents("form");
            var jq = this.jq;
            var data = $form.serialize();
            var params = options.params;
            if (params) {
                data += "&" + $.param(params);
            }
            FlashHandler.json(options.flash_name, {
                url: options.form_action,
                type: options.form_method.toUpperCase(),
                data: data,
                success: function(json, textStatus, jqXHR) {
                    var type;
                    if (options.mime_type) {
                        type = options.mime_type + ";charset=utf-8";
                    }
                    else
                    {
                        type = "application/octet-stream;charset=utf-8";
                    }
                    var blob = new Blob([data], {type: type});
                    saveAs(blob, options.output_filename);
                }
            });
            event.preventDefault();
        },
        refreshImage : function(element, event, options) {
            // spinning gif is in a separate overlay div which is displayed while data is refreshing

            // form_action, method, div_name, div_overlay_name, height_name, width_name, params
            var $form = $(element).parents("form");
            var jq = this.jq;
            var data = $form.serialize();
            var params = options.params;
            if (!params) {
                params = {};
            }
            var div = $(jq(options.div_name));
            params[options.height_name] = div.height();
            params[options.width_name] = div.width();

            data += "&" + $.param(params);

            // spinning gif is in a separate overlay div which is displayed while data is refreshing
            if (options.div_overlay_name) {
                $(document).ajaxStart(function () {
                    $(jq(options.div_overlay_name)).show();
                }).ajaxStop(function () {
                    $(jq(options.div_overlay_name)).hide();
                });
            }

            $.ajax({
                url: options.form_action,
                type: options.form_method.toUpperCase(),
                data: data,
                success: function(data, textStatus, jqXHR) {
                    // TODO: make users of this function accept JSON
                    // then better error handling here
                    div.html(data);
                }
            });
            event.preventDefault();
        },
        /**
         * This is primarily used when a person clicks the column names
         * to first clear other sorting metadata before setting new metadata
         */
        clearSortThenRefresh : function(element, event, options) {
            $(element).parents("table").find(".hidden_sorting").attr("value", "");
            return this.refresh(element, event, options);
        }
    };

    my.jq = FlashHandler.jq;

    return my;
}());
