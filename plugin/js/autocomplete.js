/**
 * <pre>
 * Autocompletes the form using the Pro6pp webService
 * In jQuery, '$' is just an alias for jQuery, so all
 * functionality is available without using $.
 * ---------------------------------------------------------------------------
 * In this script the '$' is assigned as a non conflicting alias to jQuery.
 * This is needed because of Joomla's default framework (Mootools)
 * that uses '$' as well.
 * </pre>
 */
$ = jQuery.noConflict();
/**
 * <pre>
 * var PRO6PP_COUNTRY:
 * Is an indexed object. Every country object is indexed according to the id 
 * that is assigned to it in the VirtueMart database.
 * Consists of 3 properties. 'name', 'id' and 'provinces'.
 * The 'provinces' property contains an array of province objects with 2
 * properties 'name' and 'id'.
 * </pre>
 */
(function($) {
    $.fn.applyAutocomplete = function(options) {
        // Valid for '5408xb' and '5408 XB'

        NL_SIXPP_REGEX = /[0-9]{4,4}\s?[a-zA-Z]{2,2}/;

        NL_STREETNUMBER_REGEX = /[0-9]+/;

        var instance = this;

        function getConfig(field) {

            if (typeof options === 'undefined' ||
                    typeof options[field] === 'undefined') {
                // Use default field class name
                return instance.find('#' + field);
            } else {
                // Developer chose to specify form field manually.
                return $(options[field]);
            }
        }

        instance.postcode = getConfig('postcode');
        instance.streetNr = getConfig('streetnumber');
        instance.street = getConfig('street');
        instance.city = getConfig('city');
        instance.country = getConfig('country');
        instance.province = getConfig('province');
        instance.provinceLbl = getConfig('provinceLbl');
        instance.chosenCountry = null;
        var img = 'media/plg_pro6pp/ajax-loader.gif';
        instance.postcode.after('<img id="pro6pp-spinner" src="' +
                PRO6PP_BASE.replace(/index.php/g, img) + '" alt="wait" />');

        // Store the spinner image.
        instance.spinner = $('#pro6pp-spinner');
        instance.spinner.hide();

        // Needed when user refreshes the page
        resetFields(this);

        // Enable the script only for the supported countries
        instance.country.change(function() {
            var selected = instance.country.val();

            if (PRO6PP_COUNTRY[selected]) {
                instance.chosenCountry = selected;
                addPro6ppHandling(instance);
            } else {
                enableFields(instance);
                removePro6ppHandling(instance);
            }
        });

    };

    /**
     * Adds the form handlers and prepares the form for autocompletion
     * 
     * @param {object}
     *            the object holding the handlers of the form
     * @returns {void}
     */
    function addPro6ppHandling(obj) {
        resetFields(obj);
        obj.postcode.attr('autocomplete', 'off');
        obj.postcode.attr('maxlength', '7');
        obj.street.attr('readonly', 'readonly');
        obj.city.attr('readonly', 'readonly');

        // Wire postcode validation and autocompletion
        obj.postcode.bind("keyup", function() {
            interactiveValidation(obj.postcode, 'postal');
        });

        obj.postcode.bind("blur", function() {
            if (valid(obj)) {
                autocomplete(obj);
            }
        });

        obj.streetNr.bind('keyup', function(){
            if(NL_STREETNUMBER_REGEX.test(obj.streetNr.val())){
                autocomplete(obj);
            }
        });

    }

    /**
     * Remove the handlers and reset the form to the initial state
     * 
     * @param {object}
     *            the object holding the handlers of the form
     * @returns {void}
     */
    function removePro6ppHandling(obj) {
        // Detach events
        obj.postcode.unbind('keyup');
        obj.postcode.unbind('blur');
        obj.streetNr.unbind('keyup');

        // Get fields to normal state again
        obj.postcode.attr('autocomplete', 'on');
        obj.postcode.removeAttr('maxlength');
        obj.street.removeAttr('readonly');
        obj.city.removeAttr('readonly');
        resetFields(obj);
    }

    /**
     * Resets the values of the form fields the script is using
     * 
     * @param {object}
     *            the object holding the handlers of the form
     * @returns {void}
     */
    function resetFields(obj) {
        obj.postcode.val('');
        obj.streetNr.val('');
        obj.street.val('');
        obj.city.val('');
        obj.postcode.next('p').remove();
        obj.provinceLbl = $(obj.provinceLbl.selector);
        obj.provinceLbl.find("span").text('-- Select --');
        obj.province.find('option:selected').removeAttr('selected');
    }

    /**
     * Function to check whether the postcode and streetnumber are valid for
     * submission to the service
     * 
     * @param {object}
     *            the object holding the handlers of the form
     * @returns {boolean} true if valid false otherwise
     */
    function valid(obj) {
        var postcode = obj.postcode.val();

        if (postcode.length >= 6) {
            if (NL_SIXPP_REGEX.test(postcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates the user input while it is typed.
     * 
     * @param selector
     *            The selector that is being used.
     * @param type
     *            The type of validation to perform. postal | numeric
     */
    function interactiveValidation(selector, type) {
        var postregex = new RegExp("^[1-9][\d]{4}\s?(?!([sS][adsADS]))" +
                "([a-eghj-opr-tv-xzA-EGHJ-OPR-TV-XZ]{2})?", "gi");
        var streetregex = /\D/gi;

        var regex = (type == 'postal') ? postregex : streetregex;
        var log = selector.val();
        selector.val(log.replace(regex, ""));
    }

    // Request geo-data from nl_sixpp
    function autocomplete(obj) {
        var postcode = obj.postcode.val();
        // Streetnumber is only required when there's an input field defined

        // There may be use-cases where the streetnumber is not required.
        if (NL_SIXPP_REGEX.test(postcode)) {
            var url = PRO6PP_BASE;
            var params = {};
            params.format = 'json';
            params.option = 'com_pro6pp';
            params.postcode = postcode;

            // Streetnumber field is not required
            if (typeof obj.streetNr !== 'undefined' && obj.streetNr.val() !== '') {
                params.streetnumber = obj.streetNr.val();
            }
            pro6ppCachedGet(obj, url, params, fillin);
        } else {
            errorOccured('die', PRO6PP_ERROR, obj);
        }
    }

    function escapeHtml(unsafe) {
        // Some characters that are received from the webservice should be
        // escaped when used in HTML
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(
                />/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    /**
     * On success: Fills the values to the appropriate handlers On error:
     * Empties the fields of city and address and shows the error
     * 
     * @param {object}
     *            The object holding the form handlers
     * @param {json}
     *            The data returned from the webService
     * @returns {void}
     */
    function fillin(obj, json) {
        // remove any previous error messages
        obj.postcode.next('p').remove();
        obj.spinner.hide();

        if (json.status === 'ok') {

            obj.street.val(json.results[0].street);
            obj.city.val(json.results[0].city);
            showProvince(json.results[0].province, obj);
        } else {
            errorOccured(json.error.severity, json.error.message, obj);
        }
    }

    /**
     * Shows the province in the VM dropdown area.
     * 
     * @param {provinceName}
     *            The name of the province
     * @param {object}
     *            The object holding the form handlers
     * @returns {void}
     */
    function showProvince(prov, obj) {
        var provinces = PRO6PP_COUNTRY[obj.chosenCountry].provinces;
        var index;
        for (index = 0; index < provinces.length; index++) {
            if (provinces[index].name === prov) {
                break;
            }
        }
        obj.province.find('option:selected').removeAttr('selected');
        obj.province.find('option[value=' + provinces[index].id + ']').attr(
                'selected', 'selected');
        obj.province.attr('disabled');

        // Look for the id selector again, now it's available in the DOM
        obj.provinceLbl = $(obj.provinceLbl.selector);
        obj.provinceLbl.find("span").text(escapeHtml(prov));
        // TODO: Text is changed, but the dropdown is clickable
    }

    /**
     * Is called when an error occures
     * 
     * @param string
     *            severity The action to execute.
     * @param string
     *            message The message to display to the user
     * @param object
     *            obj The object that handles DOM
     */
    function errorOccured(severity, message, obj) {
        if (obj === null) {
            return;
        }
        obj.spinner.hide();
        switch (severity) {
            case 'die':
                enableFields(obj);
                emptyFields(obj);
                removePro6ppHandling(obj);
                break;
            case 'release':
                enableFields(obj);
                emptyFields(obj);
                break;
            case 'reset':
                emptyFields(obj);
                break;
            case 'reset+release':
                emptyFields(obj);
                removePro6ppHandling(obj);
                break;
                default:
                    emptyFields(obj);
        }
        obj.postcode.next('p').remove();
        obj.postcode.after('<p style="color:red">' + message + '</p>');
    }
    /**
     * executes when a user causes an error clears the autocompleted fields
     */
    function emptyFields(obj) {
        obj.city.val('');
        obj.street.val('');
        obj.provinceLbl = $(obj.provinceLbl.selector);
        obj.provinceLbl.find("span").text('-- Select --');
        obj.province.removeAttr('selected');
    }
    /**
     * is called when a service error occurs clears the autocompleted fields
     * releases the form handlers and user input
     */
    function enableFields(obj) {
        obj.postcode.attr('autocomplete', 'on');
        obj.city.removeAttr('readonly');
        obj.street.removeAttr('readonly');
    }

    var pro6ppCache = {};

    function pro6ppCachedGet(obj, url, params, callback) {
        var key = url + $.param(params);
        if (pro6ppCache.hasOwnProperty(key)) {
            if (typeof callback !== 'undefined') {
                callback(obj, pro6ppCache[key]);
            }
        } else {
            obj.spinner.show();
            $.ajax({
                crossDomain : true,
                type : 'get',
                dataType : 'jsonp',
                timeout : obj.timeout,
                url : url,
                data : params,

                success : function(data, textStatus, jqXHR) {
                    
                    if (jqXHR.status !== 200) {
                        errorOccured('die', PRO6PP, ERROR, obj);
                        return;
                    }
                    pro6ppCache[key] = data;
                    if (typeof callback !== 'undefined') {
                        callback(obj, data);
                    }
                },
                error : function(jqXHR, textStatus, errorThrown) {
                    // Silence a json parse error from jQuery.
                    if (jqXHR.status !== 200){
                        var message = PRO6PP_ERROR;
                        errorOccured('die', message, obj);
                    }
                },
                complete : function(jqXHR, textStatus) {
                }
            });
        }
    }

})(jQuery);