/* global AjaxBootstrapSelectList */
/**
* @module theme_boost/abswrapper
* @package
*/
define(['theme_boost/bswrapper', 'theme_boost/ajax-bootstrap-select'], ($) => {
    $.fn.ajaxSelectPicker.defaults = $.extend($.fn.ajaxSelectPicker.defaults, {
        preserveSelected: true,
        preserveSelectedPosition: 'before',
        emptyRequest: true,
        clearOnEmpty: false,
        ignoredKeys: {
            ...$.fn.ajaxSelectPicker.defaults.ignoredKeys,
            // eslint-disable-next-line quote-props
            13: 'enter'
        },
        ajax: {
            type: 'POST',
            dataType: 'json',
            processData: false,
            contentType: 'application/json',
            async: true,
            loginrequired: true,
            url: function() {
                let url = `${M.cfg.wwwroot}/lib/ajax/`;
                const {loginrequired} = this.options;
                const requests = this.options.data;
                const methodInfo = [];
                const ajaxRequestData = [];
                url += `${loginrequired ? `service.php?sesskey=${M.cfg.sesskey}&` : 'service-nologin.php?'}`;
                if (!requests.length) {
                    return url;
                }
                requests.forEach((request, index) => {
                    ajaxRequestData.push({
                        index,
                        ...request
                    });
                    methodInfo.push(request.methodname);
                });
                const requestInfo = methodInfo.length <= 5 ? methodInfo.sort().join() : methodInfo.length + '-method-calls';
                this.options.context = requests;
                url += `info=${requestInfo}`;
                this.options.data = JSON.stringify(ajaxRequestData);
                return url;
            },
        },
        log: M.cfg.developerdebug,
        langCode: document.documentElement.lang,
        noajax: false,
    });

    /**
     * Retrieve data from the cache.
     *
     * @param {string} key
     *   The identifier name of the data to retrieve.
     * @param {*} [defaultValue]
     *   The default value to return if no cache data is available.
     *
     * @return {*}
     *   The cached data or defaultValue.
     */
    AjaxBootstrapSelectList.prototype.cacheGet = function(key, defaultValue) {
        key = this.cacheKey(key);
        var value = this.cache[key] || defaultValue;
        if (!value && this.plugin.options.noajax) {
            const selectedValues = this.selected.map(opt => opt.value);
            this.cache['@@empty@@'].forEach(opt => {
                opt.preserved = opt.selected = selectedValues.includes(opt.value);
            });
            return this.cache['@@empty@@'].filter(option => option.text.toLowerCase().indexOf(key.toLowerCase()) > -1);
        }
        this.plugin.log(this.LOG_DEBUG, 'Retrieving cache:', key, value);
        return value;
    };

    /**
     * Save data to the cache.
     *
     * @param {string} key
     *   The identifier name of the data to store.
     * @param {*} value
     *   The value of the data to store.
     */
    AjaxBootstrapSelectList.prototype.cacheSet = function(key, value) {
        key = this.cacheKey(key);
        this.cache[key] = value;
        this.plugin.lastcachekey = key;
        this.plugin.log(this.LOG_DEBUG, 'Saving to cache:', key, value);
    };

    AjaxBootstrapSelectList.prototype.cacheKey = function(key) {
        if (!this.plugin.options.noajax && this.plugin.options.cacheKey && $.isFunction(this.plugin.options.cacheKey)) {
            return this.plugin.options.cacheKey.call(this.plugin, key);
        }
        return key;
    };

    $('[data-absselect="1"]').selectpicker().ajaxSelectPicker();
    $('[data-bsselect="1"]').selectpicker();

    return $;
});