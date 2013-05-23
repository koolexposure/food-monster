jQuery.noConflict();

gbs = {};

jQuery(document).ready(function($){
	gbs.ready($);
});

gbs.ready = function($) {

	/**
	 * Hide options if guest selection is checked on checkout
	 */
	var $guest_check = jQuery('#checkout_registration_form #gb_user_guest_purchase');
	$guest_check.on( 'change', function() {
		$('#checkout_login_register_wrap').slideToggle();
		$('#checkout_login_register_wrap').before( $('#checkout_registration_form [for="gb_user_guest_purchase"]') ); // Move the option out of the selection
	});

	/**
	 * copy billing to shipping
	 */
	var $copy_billing_option = $('#gb_shipping_copy_billing'); 
	var $shipping_option_cache = {}; // cache options
	$copy_billing_option.bind( 'change', function() {
		// Loop over all gb_shipping options, unknowingly what is actually set because of customizations.
		$('#gb-shipping [name^="gb_shipping_"]').each(function () {
			if ( $( $copy_billing_option ).is(':checked') ) {
				var $billing_name = this.name.replace('gb_shipping_', 'gb_billing_'); // Search for a matching field
				$shipping_option_cache[this.name] = $(this).val(); // Cache the original option so it can be used later.
				$( this ).val( $( '[name="' + $billing_name + '"]' ).val() ); // set the value
			}
			else {
				$( this ).val( $shipping_option_cache[this.name] );
			};
		});
	});
};


/**
 * jQuery Validation Plugin 1.8.1
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-validation/
 * http://docs.jquery.com/Plugins/Validation
 *
 * Copyright (c) 2006 - 2011 JÃ¶rn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
(function(c){c.extend(c.fn,{validate:function(a){if(this.length){var b=c.data(this[0],"validator");if(b)return b;b=new c.validator(a,this[0]);c.data(this[0],"validator",b);if(b.settings.onsubmit){this.find("input, button").filter(".cancel").click(function(){b.cancelSubmit=true});b.settings.submitHandler&&this.find("input, button").filter(":submit").click(function(){b.submitButton=this});this.submit(function(d){function e(){if(b.settings.submitHandler){if(b.submitButton)var f=c("<input type='hidden'/>").attr("name",
b.submitButton.name).val(b.submitButton.value).appendTo(b.currentForm);b.settings.submitHandler.call(b,b.currentForm);b.submitButton&&f.remove();return false}return true}b.settings.debug&&d.preventDefault();if(b.cancelSubmit){b.cancelSubmit=false;return e()}if(b.form()){if(b.pendingRequest){b.formSubmitted=true;return false}return e()}else{b.focusInvalid();return false}})}return b}else a&&a.debug&&window.console&&console.warn("nothing selected, can't validate, returning nothing")},valid:function(){if(c(this[0]).is("form"))return this.validate().form();
else{var a=true,b=c(this[0].form).validate();this.each(function(){a&=b.element(this)});return a}},removeAttrs:function(a){var b={},d=this;c.each(a.split(/\s/),function(e,f){b[f]=d.attr(f);d.removeAttr(f)});return b},rules:function(a,b){var d=this[0];if(a){var e=c.data(d.form,"validator").settings,f=e.rules,g=c.validator.staticRules(d);switch(a){case "add":c.extend(g,c.validator.normalizeRule(b));f[d.name]=g;if(b.messages)e.messages[d.name]=c.extend(e.messages[d.name],b.messages);break;case "remove":if(!b){delete f[d.name];
return g}var h={};c.each(b.split(/\s/),function(j,i){h[i]=g[i];delete g[i]});return h}}d=c.validator.normalizeRules(c.extend({},c.validator.metadataRules(d),c.validator.classRules(d),c.validator.attributeRules(d),c.validator.staticRules(d)),d);if(d.required){e=d.required;delete d.required;d=c.extend({required:e},d)}return d}});c.extend(c.expr[":"],{blank:function(a){return!c.trim(""+a.value)},filled:function(a){return!!c.trim(""+a.value)},unchecked:function(a){return!a.checked}});c.validator=function(a,
b){this.settings=c.extend(true,{},c.validator.defaults,a);this.currentForm=b;this.init()};c.validator.format=function(a,b){if(arguments.length==1)return function(){var d=c.makeArray(arguments);d.unshift(a);return c.validator.format.apply(this,d)};if(arguments.length>2&&b.constructor!=Array)b=c.makeArray(arguments).slice(1);if(b.constructor!=Array)b=[b];c.each(b,function(d,e){a=a.replace(RegExp("\\{"+d+"\\}","g"),e)});return a};c.extend(c.validator,{defaults:{messages:{},groups:{},rules:{},errorClass:"error",
validClass:"valid",errorElement:"label",focusInvalid:true,errorContainer:c([]),errorLabelContainer:c([]),onsubmit:true,ignore:[],ignoreTitle:false,onfocusin:function(a){this.lastActive=a;if(this.settings.focusCleanup&&!this.blockFocusCleanup){this.settings.unhighlight&&this.settings.unhighlight.call(this,a,this.settings.errorClass,this.settings.validClass);this.addWrapper(this.errorsFor(a)).hide()}},onfocusout:function(a){if(!this.checkable(a)&&(a.name in this.submitted||!this.optional(a)))this.element(a)},
onkeyup:function(a){if(a.name in this.submitted||a==this.lastElement)this.element(a)},onclick:function(a){if(a.name in this.submitted)this.element(a);else a.parentNode.name in this.submitted&&this.element(a.parentNode)},highlight:function(a,b,d){a.type==="radio"?this.findByName(a.name).addClass(b).removeClass(d):c(a).addClass(b).removeClass(d)},unhighlight:function(a,b,d){a.type==="radio"?this.findByName(a.name).removeClass(b).addClass(d):c(a).removeClass(b).addClass(d)}},setDefaults:function(a){c.extend(c.validator.defaults,
a)},messages:{required:"This field is required.",remote:"Please fix this field.",email:"Please enter a valid email address.",url:"Please enter a valid URL.",date:"Please enter a valid date.",dateISO:"Please enter a valid date (ISO).",number:"Please enter a valid number.",digits:"Please enter only digits.",creditcard:"Please enter a valid credit card number.",equalTo:"Please enter the same value again.",accept:"Please enter a value with a valid extension.",maxlength:c.validator.format("Please enter no more than {0} characters."),
minlength:c.validator.format("Please enter at least {0} characters."),rangelength:c.validator.format("Please enter a value between {0} and {1} characters long."),range:c.validator.format("Please enter a value between {0} and {1}."),max:c.validator.format("Please enter a value less than or equal to {0}."),min:c.validator.format("Please enter a value greater than or equal to {0}.")},autoCreateRanges:false,prototype:{init:function(){function a(e){var f=c.data(this[0].form,"validator");e="on"+e.type.replace(/^validate/,
"");f.settings[e]&&f.settings[e].call(f,this[0])}this.labelContainer=c(this.settings.errorLabelContainer);this.errorContext=this.labelContainer.length&&this.labelContainer||c(this.currentForm);this.containers=c(this.settings.errorContainer).add(this.settings.errorLabelContainer);this.submitted={};this.valueCache={};this.pendingRequest=0;this.pending={};this.invalid={};this.reset();var b=this.groups={};c.each(this.settings.groups,function(e,f){c.each(f.split(/\s/),function(g,h){b[h]=e})});var d=this.settings.rules;
c.each(d,function(e,f){d[e]=c.validator.normalizeRule(f)});c(this.currentForm).validateDelegate(":text, :password, :file, select, textarea","focusin focusout keyup",a).validateDelegate(":radio, :checkbox, select, option","click",a);this.settings.invalidHandler&&c(this.currentForm).bind("invalid-form.validate",this.settings.invalidHandler)},form:function(){this.checkForm();c.extend(this.submitted,this.errorMap);this.invalid=c.extend({},this.errorMap);this.valid()||c(this.currentForm).triggerHandler("invalid-form",
[this]);this.showErrors();return this.valid()},checkForm:function(){this.prepareForm();for(var a=0,b=this.currentElements=this.elements();b[a];a++)this.check(b[a]);return this.valid()},element:function(a){this.lastElement=a=this.clean(a);this.prepareElement(a);this.currentElements=c(a);var b=this.check(a);if(b)delete this.invalid[a.name];else this.invalid[a.name]=true;if(!this.numberOfInvalids())this.toHide=this.toHide.add(this.containers);this.showErrors();return b},showErrors:function(a){if(a){c.extend(this.errorMap,
a);this.errorList=[];for(var b in a)this.errorList.push({message:a[b],element:this.findByName(b)[0]});this.successList=c.grep(this.successList,function(d){return!(d.name in a)})}this.settings.showErrors?this.settings.showErrors.call(this,this.errorMap,this.errorList):this.defaultShowErrors()},resetForm:function(){c.fn.resetForm&&c(this.currentForm).resetForm();this.submitted={};this.prepareForm();this.hideErrors();this.elements().removeClass(this.settings.errorClass)},numberOfInvalids:function(){return this.objectLength(this.invalid)},
objectLength:function(a){var b=0,d;for(d in a)b++;return b},hideErrors:function(){this.addWrapper(this.toHide).hide()},valid:function(){return this.size()==0},size:function(){return this.errorList.length},focusInvalid:function(){if(this.settings.focusInvalid)try{c(this.findLastActive()||this.errorList.length&&this.errorList[0].element||[]).filter(":visible").focus().trigger("focusin")}catch(a){}},findLastActive:function(){var a=this.lastActive;return a&&c.grep(this.errorList,function(b){return b.element.name==
a.name}).length==1&&a},elements:function(){var a=this,b={};return c(this.currentForm).find("input, select, textarea").not(":submit, :reset, :image, [disabled]").not(this.settings.ignore).filter(function(){!this.name&&a.settings.debug&&window.console&&console.error("%o has no name assigned",this);if(this.name in b||!a.objectLength(c(this).rules()))return false;return b[this.name]=true})},clean:function(a){return c(a)[0]},errors:function(){return c(this.settings.errorElement+"."+this.settings.errorClass,
this.errorContext)},reset:function(){this.successList=[];this.errorList=[];this.errorMap={};this.toShow=c([]);this.toHide=c([]);this.currentElements=c([])},prepareForm:function(){this.reset();this.toHide=this.errors().add(this.containers)},prepareElement:function(a){this.reset();this.toHide=this.errorsFor(a)},check:function(a){a=this.clean(a);if(this.checkable(a))a=this.findByName(a.name).not(this.settings.ignore)[0];var b=c(a).rules(),d=false,e;for(e in b){var f={method:e,parameters:b[e]};try{var g=
c.validator.methods[e].call(this,a.value.replace(/\r/g,""),a,f.parameters);if(g=="dependency-mismatch")d=true;else{d=false;if(g=="pending"){this.toHide=this.toHide.not(this.errorsFor(a));return}if(!g){this.formatAndAdd(a,f);return false}}}catch(h){this.settings.debug&&window.console&&console.log("exception occured when checking element "+a.id+", check the '"+f.method+"' method",h);throw h;}}if(!d){this.objectLength(b)&&this.successList.push(a);return true}},customMetaMessage:function(a,b){if(c.metadata){var d=
this.settings.meta?c(a).metadata()[this.settings.meta]:c(a).metadata();return d&&d.messages&&d.messages[b]}},customMessage:function(a,b){var d=this.settings.messages[a];return d&&(d.constructor==String?d:d[b])},findDefined:function(){for(var a=0;a<arguments.length;a++)if(arguments[a]!==undefined)return arguments[a]},defaultMessage:function(a,b){return this.findDefined(this.customMessage(a.name,b),this.customMetaMessage(a,b),!this.settings.ignoreTitle&&a.title||undefined,c.validator.messages[b],"<strong>Warning: No message defined for "+
a.name+"</strong>")},formatAndAdd:function(a,b){var d=this.defaultMessage(a,b.method),e=/\$?\{(\d+)\}/g;if(typeof d=="function")d=d.call(this,b.parameters,a);else if(e.test(d))d=jQuery.format(d.replace(e,"{$1}"),b.parameters);this.errorList.push({message:d,element:a});this.errorMap[a.name]=d;this.submitted[a.name]=d},addWrapper:function(a){if(this.settings.wrapper)a=a.add(a.parent(this.settings.wrapper));return a},defaultShowErrors:function(){for(var a=0;this.errorList[a];a++){var b=this.errorList[a];
this.settings.highlight&&this.settings.highlight.call(this,b.element,this.settings.errorClass,this.settings.validClass);this.showLabel(b.element,b.message)}if(this.errorList.length)this.toShow=this.toShow.add(this.containers);if(this.settings.success)for(a=0;this.successList[a];a++)this.showLabel(this.successList[a]);if(this.settings.unhighlight){a=0;for(b=this.validElements();b[a];a++)this.settings.unhighlight.call(this,b[a],this.settings.errorClass,this.settings.validClass)}this.toHide=this.toHide.not(this.toShow);
this.hideErrors();this.addWrapper(this.toShow).show()},validElements:function(){return this.currentElements.not(this.invalidElements())},invalidElements:function(){return c(this.errorList).map(function(){return this.element})},showLabel:function(a,b){var d=this.errorsFor(a);if(d.length){d.removeClass().addClass(this.settings.errorClass);d.attr("generated")&&d.html(b)}else{d=c("<"+this.settings.errorElement+"/>").attr({"for":this.idOrName(a),generated:true}).addClass(this.settings.errorClass).html(b||
"");if(this.settings.wrapper)d=d.hide().show().wrap("<"+this.settings.wrapper+"/>").parent();this.labelContainer.append(d).length||(this.settings.errorPlacement?this.settings.errorPlacement(d,c(a)):d.insertAfter(a))}if(!b&&this.settings.success){d.text("");typeof this.settings.success=="string"?d.addClass(this.settings.success):this.settings.success(d)}this.toShow=this.toShow.add(d)},errorsFor:function(a){var b=this.idOrName(a);return this.errors().filter(function(){return c(this).attr("for")==b})},
idOrName:function(a){return this.groups[a.name]||(this.checkable(a)?a.name:a.id||a.name)},checkable:function(a){return/radio|checkbox/i.test(a.type)},findByName:function(a){var b=this.currentForm;return c(document.getElementsByName(a)).map(function(d,e){return e.form==b&&e.name==a&&e||null})},getLength:function(a,b){switch(b.nodeName.toLowerCase()){case "select":return c("option:selected",b).length;case "input":if(this.checkable(b))return this.findByName(b.name).filter(":checked").length}return a.length},
depend:function(a,b){return this.dependTypes[typeof a]?this.dependTypes[typeof a](a,b):true},dependTypes:{"boolean":function(a){return a},string:function(a,b){return!!c(a,b.form).length},"function":function(a,b){return a(b)}},optional:function(a){return!c.validator.methods.required.call(this,c.trim(a.value),a)&&"dependency-mismatch"},startRequest:function(a){if(!this.pending[a.name]){this.pendingRequest++;this.pending[a.name]=true}},stopRequest:function(a,b){this.pendingRequest--;if(this.pendingRequest<
0)this.pendingRequest=0;delete this.pending[a.name];if(b&&this.pendingRequest==0&&this.formSubmitted&&this.form()){c(this.currentForm).submit();this.formSubmitted=false}else if(!b&&this.pendingRequest==0&&this.formSubmitted){c(this.currentForm).triggerHandler("invalid-form",[this]);this.formSubmitted=false}},previousValue:function(a){return c.data(a,"previousValue")||c.data(a,"previousValue",{old:null,valid:true,message:this.defaultMessage(a,"remote")})}},classRuleSettings:{required:{required:true},
email:{email:true},url:{url:true},date:{date:true},dateISO:{dateISO:true},dateDE:{dateDE:true},number:{number:true},numberDE:{numberDE:true},digits:{digits:true},creditcard:{creditcard:true}},addClassRules:function(a,b){a.constructor==String?this.classRuleSettings[a]=b:c.extend(this.classRuleSettings,a)},classRules:function(a){var b={};(a=c(a).attr("class"))&&c.each(a.split(" "),function(){this in c.validator.classRuleSettings&&c.extend(b,c.validator.classRuleSettings[this])});return b},attributeRules:function(a){var b=
{};a=c(a);for(var d in c.validator.methods){var e=a.attr(d);if(e)b[d]=e}b.maxlength&&/-1|2147483647|524288/.test(b.maxlength)&&delete b.maxlength;return b},metadataRules:function(a){if(!c.metadata)return{};var b=c.data(a.form,"validator").settings.meta;return b?c(a).metadata()[b]:c(a).metadata()},staticRules:function(a){var b={},d=c.data(a.form,"validator");if(d.settings.rules)b=c.validator.normalizeRule(d.settings.rules[a.name])||{};return b},normalizeRules:function(a,b){c.each(a,function(d,e){if(e===
false)delete a[d];else if(e.param||e.depends){var f=true;switch(typeof e.depends){case "string":f=!!c(e.depends,b.form).length;break;case "function":f=e.depends.call(b,b)}if(f)a[d]=e.param!==undefined?e.param:true;else delete a[d]}});c.each(a,function(d,e){a[d]=c.isFunction(e)?e(b):e});c.each(["minlength","maxlength","min","max"],function(){if(a[this])a[this]=Number(a[this])});c.each(["rangelength","range"],function(){if(a[this])a[this]=[Number(a[this][0]),Number(a[this][1])]});if(c.validator.autoCreateRanges){if(a.min&&
a.max){a.range=[a.min,a.max];delete a.min;delete a.max}if(a.minlength&&a.maxlength){a.rangelength=[a.minlength,a.maxlength];delete a.minlength;delete a.maxlength}}a.messages&&delete a.messages;return a},normalizeRule:function(a){if(typeof a=="string"){var b={};c.each(a.split(/\s/),function(){b[this]=true});a=b}return a},addMethod:function(a,b,d){c.validator.methods[a]=b;c.validator.messages[a]=d!=undefined?d:c.validator.messages[a];b.length<3&&c.validator.addClassRules(a,c.validator.normalizeRule(a))},
methods:{required:function(a,b,d){if(!this.depend(d,b))return"dependency-mismatch";switch(b.nodeName.toLowerCase()){case "select":return(a=c(b).val())&&a.length>0;case "input":if(this.checkable(b))return this.getLength(a,b)>0;default:return c.trim(a).length>0}},remote:function(a,b,d){if(this.optional(b))return"dependency-mismatch";var e=this.previousValue(b);this.settings.messages[b.name]||(this.settings.messages[b.name]={});e.originalMessage=this.settings.messages[b.name].remote;this.settings.messages[b.name].remote=
e.message;d=typeof d=="string"&&{url:d}||d;if(this.pending[b.name])return"pending";if(e.old===a)return e.valid;e.old=a;var f=this;this.startRequest(b);var g={};g[b.name]=a;c.ajax(c.extend(true,{url:d,mode:"abort",port:"validate"+b.name,dataType:"json",data:g,success:function(h){f.settings.messages[b.name].remote=e.originalMessage;var j=h===true;if(j){var i=f.formSubmitted;f.prepareElement(b);f.formSubmitted=i;f.successList.push(b);f.showErrors()}else{i={};h=h||f.defaultMessage(b,"remote");i[b.name]=
e.message=c.isFunction(h)?h(a):h;f.showErrors(i)}e.valid=j;f.stopRequest(b,j)}},d));return"pending"},minlength:function(a,b,d){return this.optional(b)||this.getLength(c.trim(a),b)>=d},maxlength:function(a,b,d){return this.optional(b)||this.getLength(c.trim(a),b)<=d},rangelength:function(a,b,d){a=this.getLength(c.trim(a),b);return this.optional(b)||a>=d[0]&&a<=d[1]},min:function(a,b,d){return this.optional(b)||a>=d},max:function(a,b,d){return this.optional(b)||a<=d},range:function(a,b,d){return this.optional(b)||
a>=d[0]&&a<=d[1]},email:function(a,b){return this.optional(b)||/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(a)},
url:function(a,b){return this.optional(b)||/^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(a)},
date:function(a,b){return this.optional(b)||!/Invalid|NaN/.test(new Date(a))},dateISO:function(a,b){return this.optional(b)||/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(a)},number:function(a,b){return this.optional(b)||/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(a)},digits:function(a,b){return this.optional(b)||/^\d+$/.test(a)},creditcard:function(a,b){if(this.optional(b))return"dependency-mismatch";if(/[^0-9-]+/.test(a))return false;var d=0,e=0,f=false;a=a.replace(/\D/g,"");for(var g=a.length-1;g>=
0;g--){e=a.charAt(g);e=parseInt(e,10);if(f)if((e*=2)>9)e-=9;d+=e;f=!f}return d%10==0},accept:function(a,b,d){d=typeof d=="string"?d.replace(/,/g,"|"):"png|jpe?g|gif";return this.optional(b)||a.match(RegExp(".("+d+")$","i"))},equalTo:function(a,b,d){d=c(d).unbind(".validate-equalTo").bind("blur.validate-equalTo",function(){c(b).valid()});return a==d.val()}}});c.format=c.validator.format})(jQuery);
(function(c){var a={};if(c.ajaxPrefilter)c.ajaxPrefilter(function(d,e,f){e=d.port;if(d.mode=="abort"){a[e]&&a[e].abort();a[e]=f}});else{var b=c.ajax;c.ajax=function(d){var e=("port"in d?d:c.ajaxSettings).port;if(("mode"in d?d:c.ajaxSettings).mode=="abort"){a[e]&&a[e].abort();return a[e]=b.apply(this,arguments)}return b.apply(this,arguments)}}})(jQuery);
(function(c){!jQuery.event.special.focusin&&!jQuery.event.special.focusout&&document.addEventListener&&c.each({focus:"focusin",blur:"focusout"},function(a,b){function d(e){e=c.event.fix(e);e.type=b;return c.event.handle.call(this,e)}c.event.special[b]={setup:function(){this.addEventListener(a,d,true)},teardown:function(){this.removeEventListener(a,d,true)},handler:function(e){arguments[0]=c.event.fix(e);arguments[0].type=b;return c.event.handle.apply(this,arguments)}}});c.extend(c.fn,{validateDelegate:function(a,
b,d){return this.bind(b,function(e){var f=c(e.target);if(f.is(a))return d.apply(f,arguments)})}})})(jQuery);

/*!
 * Card Check
 * 
 * A credit card validator and type guesser 
 * 
 */
(function($) {

	// Public Function $.cardcheck('#card-input', { onValidation: function() { ... } });
    $.cardcheck = function(element, options) {
		
		// Check if options defined by element and remap
		// Allows initialization like $.cardcheck({ input: '#card-input' });
		if ( typeof element === 'object' && 'input' in element ) {
			options = element;
			element = options.input;			
		}
			
		// Default Configuration
        var config = {
			
			// Callback Methods
			onReset: function() {},
			onError: function(type) {},
			onValidation: function(type, niceName) {},
            onTypeUpdate: function(type, niceName) {},
			
			// Basic Initialization Options
			input: false,
			allowSpaces: true,
			acceptedCards:  [
				'visa',
				'mastercard',
				'amex',
				'diners',
				'discover',
				'jcb',
				'maestro'
			],
			
			// Icon Configuration
			enableIcons: true,
			iconLocation: '',
			iconDir: 'images/',
			iconExt: 'png',
			iconClass: 'card-icons',
			
			// Advanced Initialization Options
			niceNames: {
				visa: "Visa", 
				mastercard: "Mastercard", 
				amex: "American Express", 
				discover: "Discover", 
				diners: "Diners Club", 
				jcb: "JCB",
				maestro: "Maestro"
			},
			regExpNumCheck: "^[0-9]+$",
			// Allows for type guessing
			regExpApprox: {
				visa: "^4", 
				mastercard: "^5[1-5]", 
				amex: "^3[47]", 
				discover: "^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)", 
				diners: "^(30|36|38|39)",			
				jcb: "^35(2[89]|[3-8][0-9])",
				maestro: "^(5018|5020|5038|6304|6759|676[1-3])",
				laser: "^(5018|5020|5038|6304|6759|676[1-3])"
			},
			// Begin guessing type at one character. All arrays to maintain similar type
			startNum: {
				visa: ['4'], 
				mastercard: ['5'], 
				amex: ['3'],
				discover: ['6'], 
				diners: ['3'],				
				jcb: ['3', '2', '1'],
				maestro: ['5', '6'],
				laser: ['5', '6']
			},
			// Determine when to use validation
			cardLength: {
				visa: [13, 16], 
				mastercard: [16], 
				amex: [15],
				discover: [16], 
				diners: [14],				
				jcb: [15, 16],
				maestro: [12, 13, 14, 15, 16, 17, 18, 19],
				laser: [16]
			}
        },
		
		// --------------------------------------------------------------------------------
		
			uniqueClass = +new Date(), // Allows us to create uniqueClass classes so that multiple instances can exist
			cardcheck = this, // Make it easier to read the things associated with this instance
			isValidated = false,
			inputIsNaN = false,
			$element = $(element);		
		
		// --------------------------------------------------------------------------------
		
		// Object Properties
		cardcheck.settings = {};
		cardcheck.lastType = '';	
			
		// --------------------------------------------------------------------------------
		
		/**
		 * Min
		 * 
		 * @param   array
		 * @return  int
		 */
		var min = function(array) {
			return Math.min.apply( Math, array );
		};
		
		// --------------------------------------------------------------------------------
		
		/**
		 * Max
		 * 
		 * @param   array
		 * @return  int
		 */
		var max = function(array) {
			return Math.max.apply( Math, array );
		};
		
		// --------------------------------------------------------------------------------
		
		/**
		 * Get Type of Credit Card
		 * 
		 * This function also controls and integrates the validation aspects of cardcheck
		 * 
		 * @param   string
		 * @return  boolean
		 */
		var getType = function(number) {
		
			// Lets see if we're ignoring any spaces
			// This allows end users to type CC nums like 4111 1111 1111
			if ( cardcheck.settings.allowSpaces === true ) {
				number = number.replace(/\s/g, '');
			}			
			
			// Can't validate anything with one or fewer digits
			if ( number.length < 1 ) {
				return false;
			}			
			
			var isNumber = new RegExp( cardcheck.settings.regExpNumCheck );
			
			// Make sure that we only have numbers here
			// TODO: Should we ignore and allow spaces?
			if ( ! number.match( isNumber ) ) {
				inputIsNaN = true;
				cardcheck.settings.onError( 'NaN' );
				return false;
			}
			
			// If they typed invalid characters before, lets reset 
			if ( inputIsNaN === true ) {
				inputIsNaN = false;
				cardcheck.settings.onReset();
			}
			
			var type = false;
			
			// Get the Type
			$.each( cardcheck.settings.acceptedCards, function(k, re) {
				
				// Check if it can be known from one character, otherwise fallback on regex
				if ( number.length === 1 && re in cardcheck.settings.startNum ) {
					// Continue because this could match three separate cards
					if ( number === '3' ) {
						return true;
					} else if ( $.inArray(number, cardcheck.settings.startNum[re]) !== -1 ) {
						type = re;
						return false; // we found it!
					}
				}
				
				// Stores the regular expression
				var exp, 
					fullValidation = false,
					highestValue = false,
					beyondLength = false;
				
				// Check if we should require full validation based on whether the max card length is reached
				// TODO: could produce false positives for multiple card lengths.
				if ( re in cardcheck.settings.cardLength && $.inArray(number.length, cardcheck.settings.cardLength[re]) !== -1 ) {
					fullValidation = true;
				} else if ( number.length >= max(cardcheck.settings.cardLength[re] ) ) {
					fullValidation = true;
					highestValue = true;
					beyondLength = ( number.length > max(cardcheck.settings.cardLength[re] ) );
				// Whether the isValidated is true OR if the current number is less than minimum card length
				// We can rely on the fact that we need to reset here if isValidated is TRUE because of the 
				//  fact that the cardlength is not the current length of the string so it's impossible its still valid 
				} else if ( isValidated === true ) {
					isValidated = false;
					cardcheck.settings.onReset(); // TODO: check if there are other fringe scenarios that require the reset
				}
				
					
				// Continue approximation with simple regex to test beginning of string
				if ( re in cardcheck.settings.regExpApprox ) {
					exp = new RegExp( cardcheck.settings.regExpApprox[re] );
					
					// Check the Type - Only validate if type is found
					if ( number.match(exp) ) {
						
						// This is where the type is found
						type = re;
						
						// Check to see if we require full validation yet
						if ( fullValidation === false ) {
							return false;
						}
						
						// Check the LuhnCheck at this point to make sure that it validates
						// OUTSTANDING ISSUE: The luhn check can pass even if the card is not complete
						//  Example would be visa being able to have longer cards. 
						if ( luhnCheck( number ) === true && ! beyondLength ) {
							isValidated = true; // So that if it invalidates in the future, we can test it
							cardcheck.settings.onValidation(re, getTypeNiceName(re)); // We've found a valid credit card number.
						// Soft Failure - there is another possibility with more chars
						} else if ( highestValue ) {
							isValidated = false;
							cardcheck.settings.onError(re);
						// Lower than min card length TODO: make sure the card type hasn't changed
						} else if ( number.length < min(cardcheck.settings.cardLength[re]) ) {
							cardcheck.settings.onReset();
						}
												
						return false; // Same as break in for loop						
					}
				}				
			});
			
			return type;
		};
		
		// --------------------------------------------------------------------------------
		
		/**
		 * Get Type Nice Name - For Display Purposes
		 * 
		 * @param   string
		 * @return  string
		 */
		var getTypeNiceName = function(slug) {
			return cardcheck.settings.niceNames[slug];
		};

		// --------------------------------------------------------------------------------
		
		/**
		 * Luhn Check of the Credit Card Number
		 * 
		 * @param   string
		 * @return  boolean
		 */
		var luhnCheck = function(number) {
			var luhnArr = [[0,2,4,6,8,1,3,5,7,9],[0,1,2,3,4,5,6,7,8,9]],
				sum = 0;
			
			number.replace(/\D+/g,"").replace(/[\d]/g, function(c, p, o){
				sum += luhnArr[ (o.length - p) & 1 ][ parseInt(c, 10) ];
			});
			return ( sum % 10 === 0 ) && ( sum > 0 );
		};		
		
		// --------------------------------------------------------------------------------
		
		/**
		 * Create Card Icons
		 * 
		 * NOTE: Icons must be named EXACTLY the same as the acceptedCards
		 */
		var createCardIcons = function() {
			
			// Icons are disabled... SORRY!
			if ( cardcheck.settings.enableIcons === false ) {
				return false;
			} 
			
			var iconLoc = (cardcheck.settings.iconLocation) ? $(cardcheck.settings.iconLocation): $element.parent();
			
			// Loop over all Accepted Cards and create the icons for them.
			$.each( cardcheck.settings.acceptedCards, function( k, icon ) {
				iconLoc.append( $('<img>').attr( 'id', 'card-' + icon + uniqueClass )
					.attr( 'src', cardcheck.settings.iconDir + icon + '.' + cardcheck.settings.iconExt )
					.addClass( cardcheck.settings.iconClass + uniqueClass ) );
			});
		};
		
		// --------------------------------------------------------------------------------
		
		/**
		 * Initialization
		 * 
		 * Automatically binds the events to the element defined and merges additional
		 * options onto the settings.
		 */
		var init = function() {			
			cardcheck.settings = $.extend({}, config, options);
			
			// Create Images
			createCardIcons();
			
			// Bind Event (we could use $element.on() here, but want to preserve compatability)
			$element.bind("keyup.cardcheck change.cardcheck", function() {
				
				var number = $element.val(),
					type = getType(number);					
				
				// NOTE: The iconClass is not cached in a var because it doesn't exist yet
				
				// Prevent Cards from flashing as you type
				if ( cardcheck.lastType !== type && type !== false ) {
					// Fade only the card type that is not selected
					$( '.' + cardcheck.settings.iconClass + uniqueClass ).not('#card-' + type + uniqueClass).fadeTo('fast', 0.2);
					$('#card-' + type + uniqueClass).css('opacity', 1);

					// Call on Reset whenever the type changes, even if its the first time.
					// This is because the escape key is not registered properly when held
					//  down via the keypress event.
					cardcheck.settings.onReset();
					
					// Call Method onTypeUpdate
					cardcheck.settings.onTypeUpdate( type, getTypeNiceName(type) );
				}
				
				// Check if empty
				if ( ! number ) {
					cardcheck.lastType = '';
					$( '.' + cardcheck.settings.iconClass + uniqueClass ).css('opacity', 1);	
					
					// Call Type Update because the number has changed. Type will be false.
					cardcheck.settings.onTypeUpdate( type, '');
				}
				
				// Set Current Type
				cardcheck.lastType = type;
			});			
		};

		// --------------------------------------------------------------------------------
		
		// Let's get it ROLLING!
        init();
				
		// Make this thing chainable in case we want to chuck on a class or something
		return $element;
    }

	/**
	 * Allows you to do this: $('#credit-card').cardcheck();
	 */
    $.fn.cardcheck = function(options) {
        return this.each(function() {
            if (undefined == $(this).data('cardcheck')) {
				return $.cardcheck(this, options).data('cardcheck', 'true');
            }
        });
    }

})(jQuery);