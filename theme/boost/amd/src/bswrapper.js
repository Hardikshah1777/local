/**
 * @module theme_boost/bswrapper
 * @package
 */
define(['jquery', 'theme_boost/bootstrap-select'], function($) {
    $.fn.selectpicker.Constructor.DEFAULTS.actionsBox = true;
    $.fn.selectpicker.Constructor.DEFAULTS.liveSearch = true;
    $.fn.selectpicker.Constructor.DEFAULTS.showTick = true;
    $.fn.selectpicker.Constructor.DEFAULTS.tickIcon = 'fa fa-check';
    $.fn.selectpicker.Constructor.DEFAULTS.selectedTextFormat = 'count > 1';
    $.fn.selectpicker.Constructor.DEFAULTS.size = 10;
    $.fn.selectpicker.Constructor.DEFAULTS.pluralForm = 'items';
    $.fn.selectpicker.Constructor.DEFAULTS.countSelectedText = function() {
        return M.util.get_string('itemsselcted', 'core', this.pluralForm);
    };
    return $;
});