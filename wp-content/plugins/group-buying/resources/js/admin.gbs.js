jQuery.noConflict();

gbs_admin = {};

jQuery(document).ready(function($){
	gbs_admin.ready($);
});

gbs_admin.ready = function($) {
	$('.select2').select2();
	$('#gb_signup_redirect, #gb_signup_not_found, #gb_nodeal_content, #gb_pp_page, #gb_tos_page, #gb_added_deal_id').select2({
		width: 'element'
	});
};

/*
 * jQuery replaceText - v1.1 - 11/21/2009
 * http://benalman.com/projects/jquery-replacetext-plugin/
 * 
 * Copyright (c) 2009 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function($){$.fn.replaceText=function(b,a,c){return this.each(function(){var f=this.firstChild,g,e,d=[];if(f){do{if(f.nodeType===3){g=f.nodeValue;e=g.replace(b,a);if(e!==g){if(!c&&/</.test(e)){$(f).before(e);d.push(f)}else{f.nodeValue=e}}}}while(f=f.nextSibling)}d.length&&$(d).remove()})}})(jQuery);

/**
 * ScrollTo with a modification to the top css
 */
(function(b){if(b.ScrollTo)window.console.warn("$.ScrollTo has already been defined...");else{b.ScrollTo={config:{duration:400,easing:"swing",callback:undefined,durationMode:"each"},configure:function(c){b.extend(b.ScrollTo.config,c||{});return this},scroll:function(c,d){var f=b.ScrollTo,a=c.pop(),e=a.$container,g=a.$target;a=b("<span/>").css({position:"absolute",top:"80px",left:"0px"});var h=e.css("position");e.css("position","relative");a.appendTo(e);var i=a.offset().top;g=g.offset().top-i;a.remove();
e.css("position",h);e.animate({scrollTop:g+"px"},d.duration,d.easing,function(j){if(c.length===0)typeof d.callback==="function"&&d.callback.apply(this,[j]);else f.scroll(c,d);return true});return true},fn:function(c){var d=b.ScrollTo,f=b(this);if(f.length===0)return this;var a=f.parent(),e=[];for(config=b.extend({},d.config,c);a.length===1&&!a.is("body")&&a.get(0)!==document;){c=a.get(0);if(a.css("overflow-y")!=="visible"&&c.scrollHeight!==c.clientHeight){e.push({$container:a,$target:f});f=a}a=a.parent()}e.push({$container:b(b.browser.msie?
"html":"body"),$target:f});if(config.durationMode==="all")config.duration/=e.length;d.scroll(e,config);return this},construct:function(c){var d=b.ScrollTo;b.fn.ScrollTo=d.fn;d.config=b.extend(d.config,c);return this}};b.ScrollTo.construct()}})(jQuery);