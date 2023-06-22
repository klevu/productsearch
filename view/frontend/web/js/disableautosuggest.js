define([
    'jquery',
    'Magento_Search/form-mini'
], function ($) {

    $.widget('klevu.quickSearch', $.mage.quickSearch, {
        _init: function () {
            if (typeof klevu_current_version !== "undefined") {
                this.options.minSearchLength = 200;
            }
        },

        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            if ($.ui.keyCode.ENTER === keyCode) {
                if (this.element.val().length >= 1) {
                    this.searchForm.trigger('submit');
                }
                return true;
            }

            return this._super(e);
        },

        _onPropertyChange: function () {
            this._super();

            if (this.element.val().length >= 1) {
                this.submitBtn.disabled = false;
            }
        }
    });

    return $.klevu.quickSearch;
});
