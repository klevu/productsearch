define([
  'jquery',
  'Magento_Search/form-mini' 
], function($){
 
  $.widget('klevu.quickSearch', $.mage.quickSearch, {
		_init: function () { 	
			this.options.minSearchLength = 200; 
		}
  });
 
  return $.klevu.quickSearch;
});