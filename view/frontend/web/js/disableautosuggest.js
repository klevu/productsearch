define([
  'jquery',
  'jquery/ui',
  'Magento_Search/form-mini' 
], function($){
 
  $.widget('klevu.quickSearch', $.mage.quickSearch, {
		options: {
            minSearchLength: 200,
        },
  });
 
  return $.klevu.quickSearch;
});