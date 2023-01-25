define([
    'jquery',
    'underscore',
    'mageUtils',
    'rjsResolver',
    'uiLayout',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'uiElement',
    'Magento_Ui/js/grid/provider',
    'Magento_Ui/js/grid/data-storage',
], function ($, _, utils, resolver, layout, alert, $t, Element, provider) {
    'use strict';

    return provider.extend({

        /**
         * Reloads data with current parameters.
         *
         * @returns {Promise} Reload promise object.
         */
        reload: function (options) {
            var storage = this.storage();
            storage.updateUrl = this.storageConfig.updateUrl;
            storage.requestConfig.url = this.storageConfig.updateUrl;

            var request = storage.getData(this.params, options);

            this.trigger('reload');

            request
                .done(this.onReload)
                .fail(this.onError.bind(this));

            return request;
        }
    });
});
