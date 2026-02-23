/**
 * Shared utility functions for transform widget options to human-readable string
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

var stringifyOptions = function (widgetOptions) {
    if (widgetOptions == null || typeof widgetOptions !== 'object') {
        return String(widgetOptions);
    }

    if (Array.isArray(widgetOptions)) {
        return '[' + widgetOptions.map(stringifyOptions).join(', ') + ']';
    }

    var widgetOptionsArray = [];
    for (var property in widgetOptions) {
        if (widgetOptions.hasOwnProperty(property)) {
            widgetOptionsArray.push(property + ': ' + stringifyOptions(widgetOptions[property]));
        }
    }

    return widgetOptionsArray.join(', ');
};
