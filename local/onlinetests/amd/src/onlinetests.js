define(['jquery'],function($) {
	return onlinetests = {
		hide_element_icon: function(selector){
			$(selector).remove();
		},
		change_element_attribute: function(selector, attribute, value){
			$(selector).attr(attribute, value);
		},
		change_element_html: function(selector, value){
			$(selector).html(value);
		}
	};        	
});