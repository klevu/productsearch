define([
  'jquery',
  'Magento_Search/form-mini' 
], function($){
 
  $.widget('klevu.quickSearch', $.mage.quickSearch, {
		_init: function () { 	
			this.options.minSearchLength = 200; 
		},

        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            if ($.ui.keyCode.ENTER === keyCode) {
                this.searchForm.trigger('submit');
                return true;
            }

            return this._super(e);
        }
  });
 
  return $.klevu.quickSearch;
});