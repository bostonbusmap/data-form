/**
 * Created by george on 4/17/14.
 */

/**
 * Convenience functions to report errors in flash space
 */
var FlashHandler = (function() {
    "use strict";

    var my = {};
    my.jq = function ( myid ) {
        // Escape element ids for use in JQuery
        // From http://learn.jquery.com/using-jquery-core/faq/how-do-i-select-an-element-by-an-id-that-has-characters-used-in-css-notation/
        return "#" + myid.replace( /(:|\.|\[|\])/g, "\\$1" );
    };

    /**
     * Download and parse JSON and report errors if any. Returns parsed JSON object or null if error.
     *
     * @param flash_name string
     * @param params array This should match what you would pass to $.ajax(params)
     */
    my.json = function (flash_name, params) {
        var jq = my.jq;

        if (!flash_name) {
            throw "params.flash_name is undefined";
        }
        try {
            if (typeof(params) !== 'object') {
                throw "params must be an object";
            }

            // clone array so we can alter it
            params = $.extend({}, params);
            var oldSuccess = params.success;
            var oldError = params.error;
            params.success = function (data, textStatus, jqXHR) {
                var json = my.parseJSON(data, flash_name);
                if (json && oldSuccess) {
                    oldSuccess(json, textStatus, jqXHR);
                }
            };
            params.error = function(request, textStatus, errorThrown) {
                var json = my.parseJSON(request.responseText, flash_name);
                if (oldError) {
                    oldError(request, textStatus, errorThrown);
                }
            };

            return $.ajax(params);
        }
        catch (e) {
            $(jq(flash_name)).html("<span class='error'>" + e.message + "</span>");
            return null;
        }
    };

    /**
     * Parse a piece of JSON and return object. On error, write it to div named flash_name and return null.
     * @param data string. If array, this assumes it is already parsed and will return this value.
     * @param flash_name Name of div to send errors to
     * @returns array|null parsed JSON or null
     */
    my.parseJSON = function(data, flash_name) {
        if (!flash_name) {
            throw "Flash name not defined";
        }

        try
        {
            var json = null;
            if (typeof(data) !== "string") {
                // data was already parsed
                json = data;
            }
            else {
                try {
                    json = JSON.parse(data);
                }
                catch (e) {
                    // silence error, we handle this by checking return value
                }
            }

            var jq = my.jq;
            if (!json) {
                $(jq(flash_name)).html("<span class='error'>Parse error</span>");
            }
            else if (json.error) {
                $(jq(flash_name)).html("<span class='error'>" + json.error + "</span>");
            }
            else {
                return json;
            }
        }
        catch (e) {
            $(jq(flash_name)).html("<span class='error'>" + e.message + "</span>");
        }
        return null;
    };

    return my;
})();
