/**
 * Plugin: One Page Checkout for VirtueMart byPV
 * Copyright (C) 2014 byPV.org <info@bypv.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var VirtueMartCart_byPV = {
		
	/*** Public (you can override externally according to your needs) ***/
		
	/**
	 * Style of Loader Overlay
	 * - value can be simple value ( TRANSPARENCY ) or complex object with SHOW and HIDE parameters ( { SHOW: 'STANDARD', HIDE: 'TRANSPARENCY' } ).
	 * 
	 * Predefined styles:
	 * - null = nothing - not recomended
	 * - STANDARD = show and hide simple overlay
	 * - TRANSPARENCY = transparency effect during simple overlay is appear and disapear
	 * - CENTER = overlay is scaling up "from center" and scaling down "to center" 
	 * - function with own style
	 * 		SHOW: function ($el_loader, $el_block) {}
	 * 		HIDE: function ($el_loader) {}
	 *  	- for example see LOADING_OVERLAY_DEFINITION below in code
	 */
	LOADING_OVERLAY_STYLE: {
		SHOW: 'CENTER',
		HIDE: 'TRANSPARENCY'
	},
	
	/**
	 * Selectors of blocks where loader will be showed
	 * - key is preddefined ID of action
	 * - value can be simple value with selector or javascript array of selectors 
	 */
	LOADING_OVERLAY_BLOCK_SELECTORS: {
		CART: '#bypv_cart',
		PRODUCTS_AND_SUMMARY: [ '#bypv_cart_product_list', '#bypv_cart_order_summary', '#bypv_cart_shipments', '#bypv_cart_payments' ],
		COUPON_CODE: '#bypv_cart_coupon_code',
		SHIPMENT: [ '#bypv_cart_product_list', '#bypv_cart_order_summary', '#bypv_cart_shipments', '#bypv_cart_payments' ],
		PAYMENT: [ '#bypv_cart_product_list', '#bypv_cart_order_summary', '#bypv_cart_payments' ],
		CUSTOMER_TYPE: [ '#bypv_cart_customer_type_select', '#cart_customer' ],
		SHIPPING_ADDRESS: [ '#bypv_cart_shipping_address' ],
		ADDRESS_FIELD: [ '#bypv_cart_product_list', '#bypv_cart_order_summary', '#bypv_cart_shipments', '#bypv_cart_payments' ],
		LOGIN: '#cart_customer',
		LOGOUT: '#bypv_cart'
	},

	LOADING_OVERLAY_HTML: '<div class="bypv_loader"><div class="bypv_background"></div><div class="bypv_image"></div></div>',
	LOADING_OVERLAY_SELECTOR: 'div.bypv_loader',
	
	SYSTEM_MESSAGE_CONTAINER_SELECTOR: '#system-message-container',
	SYSTEM_MESSAGE_CONTAINER_HTML_FALLBACK: '<div id="system-message-container"></div>',
	SYSTEM_MESSAGE_CONTAINER_SCROLL_OFFSET: 50,

	REMEMBER_FORM_FIELDS: true,
	ALLOW_VALIDATION_IN_BROWSER: true,
	
	CHECKED_USER_FIELDS: [ 'zip', 'virtuemart_country_id' ],
	VALIDATED_JOOMLA_FIELDS: [ 'email', 'username' ],
	SHOPPER_FIELDS_DISPLAY_CONDITIONS: {},

	/*** Private (don't change) ***/

	CART_SELECTOR: '#bypv_cart',
	PRODUCT_LIST_SELECTOR: '#bypv_cart_product_list',
	ORDER_SUMMARY_SELECTOR: '#bypv_cart_order_summary',
	SUMMARY_TABLE_SELECTOR: 'table.summary_table',
	PRODUCT_QUANTITY_SELECTOR: '.bypv_product_quantity',
	COUPON_CODE_SELECTOR: '#bypv_cart_coupon_code',
	SHIPMENT_SELECTOR: '#bypv_cart_shipments',
	PAYMENT_SELECTOR: '#bypv_cart_payments',
	CUSTOMER_TYPE_SELECT_SELECTOR: '#bypv_cart_customer_type_select',
	LOGIN_SELECTOR: '#bypv_cart_login',
	BILLING_ADDRESS_SELECTOR: '#bypv_cart_billing_address',
	SHIPPING_ADDRESS_SELECTOR: '#bypv_cart_shipping_address',
	SHIPPING_ADDRESS_SELECT_SELECTOR: '#bypv_cart_shipping_address_select',
	CART_FIELDS_SELECTOR: '#bypv_cart_fields',

	json_requests: [],
	json_request_progress: null,

	base_uri: '',
	cart_lang: '',

	form_initialized: false,
	form_submitted: false,
	form_event: null,
	
	shipments_incompatible_with_ajax: [],
	payments_incompatible_with_ajax: [],
	cached_methods: {},
	
	show_error_data: false,
	
	init_methods: {
		form: 'initFormEvents',
		product_list: 'initProductListEvents',
		order_summary: 'initOrderSummaryEvents',
		coupon_code: 'initCouponEvents',
		shipments: 'initShipmentsEvents',
		payments: 'initPaymentsEvents',
		customer_type_select: 'initCustomerTypeSelectEvents',
		login: 'initLoginEvents',
		billing_address: 'initBillingAddressEvents',
		shipping_address: 'initShippingAddressEvents',
		cart_fields: 'initCartFieldsEvents'
	},

	/*** Init Methods ***/
	
	initialize : function ()
	{
		var $ = jQuery;
		
		$.each(this.init_methods, function (id, method)
		{
			VirtueMartCart_byPV[method]();
		});
		
		this.form_initialized = true;
	},
	
	initFormEvents : function (params)
	{
		var $ = jQuery;
		if (!$.isPlainObject(params)) params = {};
		
		$('input[name=bypv_submit_back_to_checkout]', this.CART_SELECTOR)
			.click(function() {
				$('input[name=task]:hidden', VirtueMartCart_byPV.CART_SELECTOR).val('backToCheckout');
				this.form.submit();
			});

		$('input.bypv_empty_cart_button', this.CART_SELECTOR)
			.click(function() {
				VirtueMartCart_byPV.emptyCart();
			});
	
		$(this.CART_SELECTOR)
			.submit(function (event) {
				if (VirtueMartCart_byPV.form_submitted === true) return false;
				
				VirtueMartCart_byPV.form_submitted = true;
				VirtueMartCart_byPV.form_event = event;
				
				window.setTimeout(function ()
				{
					if (VirtueMartCart_byPV.form_event.result !== true)
					{
						VirtueMartCart_byPV.form_submitted = false;
						$('input[type=submit]', VirtueMartCart_byPV.CART_SELECTOR).fadeTo(500, 1);
					}
					else
					{
						VirtueMartCart_byPV.clearFieldsLocal();
					}
				}, 500);
				
				$('input[type=submit]', VirtueMartCart_byPV.CART_SELECTOR).fadeTo(500, 0.5);
				
				if (VirtueMartCart_byPV.ALLOW_VALIDATION_IN_BROWSER === true)
				{
					return VirtueMartCart_byPV.validateVMAddressFields();
				}
			})
			.keypress(function (event) {
				if (event.which == 13) {
					var $el_target = $(event.target);
					
					if (!$el_target.is("textarea") && !$el_target.is(":button,:submit")) {
						var focusNext = false;
						
						$(this).find(":input:visible:not([disabled],[readonly]), a").each(function () {
							if (this === event.target) {
								focusNext = true;
							}
							else if (focusNext){
								$(this).focus();
								return false;
							}
						});
						
						return false;
					}
				}
			});
		
		if (this.form_initialized && !params['from_vm_product_update'])
		{
			VirtueMartCart_byPV.vmProductUpdate(null, false);
		}
	},
	
	initProductListEvents : function (params)
	{
		var $ = jQuery;
		var $el_product_list_form = $(this.PRODUCT_LIST_SELECTOR);
		
		if (!$.isPlainObject(params)) params = {};
		
		var $el_quantity_input = $el_product_list_form.find('.bypv_product_quantity input.bypv_quantity');
		
		$el_quantity_input
			.change( function(event) {
				VirtueMartCart_byPV.checkProductQuantity(event.target);
			});
		
		$el_product_list_form.find('.bypv_product_quantity span.bypv_quantity_controls > input')
			.click( function(event) {
				VirtueMartCart_byPV.setProductQuantity(event.target);
			});
		
		var $el_quantity_update_buttons = $el_product_list_form.find('.bypv_product_quantity input.bypv_product_update');
		
		if ($el_quantity_update_buttons.length > 0)
		{
			$el_quantity_update_buttons
				.click( function(event) {
					VirtueMartCart_byPV.updateProductQuantity(event.target);
				});
		}
		else
		{
			$el_quantity_input
				.change( function(event) {
					VirtueMartCart_byPV.updateProductQuantity(event.target);
				});
		}
	
		$el_product_list_form.find('input.bypv_product_remove')
			.click( function(event) {
				VirtueMartCart_byPV.dropProduct(event.target);
			});

		this.initCouponEvents(this.PRODUCT_LIST_SELECTOR);
		
		if (this.form_initialized && !params['from_vm_product_update'])
		{
			VirtueMartCart_byPV.vmProductUpdate(null, false);
		}
	},

	initOrderSummaryEvents : function (SELECTOR)
	{
		this.initCouponEvents(this.ORDER_SUMMARY_SELECTOR);
	},

	initCouponEvents : function (SELECTOR)
	{
		var $ = jQuery;

		SELECTOR = (SELECTOR ? SELECTOR : this.COUPON_CODE_SELECTOR);
		
		$('input[name=bypv_coupon_code]', SELECTOR)
			.keypress(function(event) {
				if (event.which == 13) {
					event.preventDefault();
					VirtueMartCart_byPV.updateCouponCode(SELECTOR);
				}
			});
		
		$('.bypv_coupon_code_remove_button', SELECTOR)
			.click( function(event) {
				VirtueMartCart_byPV.dropCouponCode();
			});
		
		$('.bypv_coupon_code_button', SELECTOR)
		.click( function(event) {
			VirtueMartCart_byPV.updateCouponCode(SELECTOR);
		});
	},
	
	initShipmentsEvents : function ()
	{
		var $ = jQuery;

		$('*[name=virtuemart_shipmentmethod_id]', this.SHIPMENT_SELECTOR)
			.change( function(event) {
				VirtueMartCart_byPV.updateShipment();
			});
	},
	
	initPaymentsEvents : function ()
	{
		var $ = jQuery;

		$('*[name=virtuemart_paymentmethod_id]', this.PAYMENT_SELECTOR)
			.change( function(event) {
				VirtueMartCart_byPV.updatePayment();
			});
	},
	
	initCustomerTypeSelectEvents : function ()
	{
		var $ = jQuery;
		
		$('*[name=bypv_customer_type]', this.CUSTOMER_TYPE_SELECT_SELECTOR)
			.change( function(event) {
				VirtueMartCart_byPV.updateCustomerForm();
			});
	},

	initLoginEvents : function ()
	{
		var $ = jQuery;

		$('input:text, input:password', this.LOGIN_SELECTOR)
			.keypress(function(event) {
				if (event.which == 13) {
					event.preventDefault();
					VirtueMartCart_byPV.login();
				}
			});
		
		$('input#bypv_login', this.LOGIN_SELECTOR)
			.click( function(event) {
				VirtueMartCart_byPV.login();
			});

		$('input#bypv_logout', this.LOGIN_SELECTOR)
			.click( function(event) {
				VirtueMartCart_byPV.logout();
			});
	},
	
	fixForStateField : function (field_name_prefix, block_selector)
	{
		var $ = jQuery;
		
		var $countryField = $('[name=' + field_name_prefix + 'virtuemart_country_id]', block_selector);
		var $stateField = $('[name=' + field_name_prefix + 'virtuemart_state_id]', block_selector);

		if ($countryField.length > 0 && $stateField.length > 0)
		{
			$countryField.change(function (event)
			{
				$stateField
					.children('optgroup')
					.not("[id='" + field_name_prefix + "group" + $(this).val() + "']")
					.remove();
				
				$stateField.trigger("liszt:updated");
			});
		}
	},

	initFieldsDisplayConditions : function (field_name_prefix, block_selector)
	{
		var $ = jQuery;
		
    	$.each(this.SHOPPER_FIELDS_DISPLAY_CONDITIONS, function ()
    	{
    		var fieldCondition = this;
    		
    		$('*[name="' + field_name_prefix + fieldCondition.FIELD + '"]', block_selector)
	    		.change(function (event)
	    		{
	    			var $field = $('*[name="' + this.name + '"]');
	    			var value = $field.val();
	    			
	    			if ($field.attr('type') == 'radio' || $field.attr('type') == 'checkbox')
	    			{
	    				value = $field.filter(':checked').val();
	    			}
	    			
	    			$.each(fieldCondition.SHOW, function ()
	    			{
	    				var $groupToShow = $('div.' + this + '_group', block_selector)
	    				var show = ($.inArray(value, fieldCondition.VALUE) > -1);

	    				if ($groupToShow.length > 0)
	    				{
	    					if (show)
	    					{
	    						$groupToShow.slideDown(400);
	    					}
	    					else
	    					{
	    						$groupToShow.slideUp(400);
	    					}
	    				}
	    				else
	    				{
		    				$fieldToShow = $('tr.' + this + '_field', block_selector);
		    				
		    				if (show)
		    				{
		    					$fieldToShow.fadeIn(400);
		    				}
		    				else
		    				{
		    					$fieldToShow.fadeOut(400);
		    				}
	    				}
	    			});
	    		});
    	});
	},

	initBillingAddressEvents : function ()
	{
		var $ = jQuery;

		this.fixForStateField('bypv_billing_address_', VirtueMartCart_byPV.BILLING_ADDRESS_SELECTOR);
		
		this.initFieldsDisplayConditions('bypv_billing_address_', VirtueMartCart_byPV.BILLING_ADDRESS_SELECTOR);
		
        if ($('input:radio[name=bypv_customer_type]:checked', this.CUSTOMER_TYPE_SELECT_SELECTOR).val() === 'registration')
        {
        	$.each(this.VALIDATED_JOOMLA_FIELDS, function () {
        		var fieldName = this;
        		
        		$('*[name="bypv_billing_address_' + fieldName + '"]', VirtueMartCart_byPV.BILLING_ADDRESS_SELECTOR)
	        		.change(function (event) {
	        			VirtueMartCart_byPV.validateJoomlaFields(fieldName, this.value);
	        		});
        	});
        }

		$.each(this.CHECKED_USER_FIELDS, function () {
			var fieldName = this;
			
			$('*[name="bypv_billing_address_' + fieldName + '"]', VirtueMartCart_byPV.BILLING_ADDRESS_SELECTOR)
				.change(function (event) {
					VirtueMartCart_byPV.updateShipmentAndPaymentForm(VirtueMartCart_byPV.BILLING_ADDRESS_SELECTOR);
				});
		});
		
		if (this.ALLOW_VALIDATION_IN_BROWSER === true && $(this.CART_SELECTOR).hasClass('checkout'))
		{
			$('input, textarea, select', this.BILLING_ADDRESS_SELECTOR).not('input:hidden')
				.change(function(event) {
					return VirtueMartCart_byPV.validateVMAddressField(this);
				})
				.blur(function(event) {
					return VirtueMartCart_byPV.validateVMAddressField(this);
				});
		}
		
		// Check if browser support Storage (HTML5)
		if (this.REMEMBER_FORM_FIELDS === true && typeof(Storage) !== "undefined" && typeof(JSON) !== "undefined")
		{
			VirtueMartCart_byPV.loadFieldsLocal('bt');
			
			var $el_address_block = $(this.BILLING_ADDRESS_SELECTOR, this.CART_SELECTOR);
			var $el_inputs = $el_address_block.find('input, select, textarea');
			
			$el_inputs
				.change(function (event) {
					VirtueMartCart_byPV.saveFieldLocal('bt', this);
				});
		}
	},
	
	initShippingAddressEvents : function ()
	{
		var $ = jQuery;

		this.fixForStateField('bypv_shipping_address_', VirtueMartCart_byPV.SHIPPING_ADDRESS_SELECTOR);

		this.initFieldsDisplayConditions('bypv_shipping_address_', VirtueMartCart_byPV.SHIPPING_ADDRESS_SELECTOR);

		$.each(this.CHECKED_USER_FIELDS, function () {
			var fieldName = this;
			
			$('*[name="bypv_shipping_address_' + fieldName + '"]', VirtueMartCart_byPV.SHIPPING_ADDRESS_SELECTOR)
				.change(function (event) {
					VirtueMartCart_byPV.updateShipmentAndPaymentForm(VirtueMartCart_byPV.SHIPPING_ADDRESS_SELECTOR);
				});
		});

		if (this.ALLOW_VALIDATION_IN_BROWSER === true && $(this.CART_SELECTOR).hasClass('checkout'))
		{
			$('input, select, textarea', this.SHIPPING_ADDRESS_SELECTOR).not('input:hidden')
				.change(function(event) {
					return VirtueMartCart_byPV.validateVMAddressField(this);
				})
				.blur(function(event) {
					return VirtueMartCart_byPV.validateVMAddressField(this);
				});
		}

        // Check if browser support Storage (HTML5)
        if (this.REMEMBER_FORM_FIELDS === true && typeof(Storage) !== "undefined" && typeof(JSON) !== "undefined")
        {
			VirtueMartCart_byPV.loadFieldsLocal('st');

			var $el_address_block = $(this.SHIPPING_ADDRESS_SELECTOR, this.CART_SELECTOR);
			var $el_inputs = $el_address_block.find('input, select, textarea');
			
			$el_inputs
				.change(function (event) {
					VirtueMartCart_byPV.saveFieldLocal('st', this);
				});
        }
		
		this.initShippingAddressSelectEvents();
	},
	
	initShippingAddressSelectEvents : function ()
	{
		var $ = jQuery;
		
		$('*[name=shipto]', this.SHIPPING_ADDRESS_SELECT_SELECTOR)
			.change(function (event) {
				VirtueMartCart_byPV.updateShippingAddress();
			});

		$('input.bypv_remove_address_button', this.SHIPPING_ADDRESS_SELECT_SELECTOR)
			.click(function (event) {
				VirtueMartCart_byPV.deleteShippingAddress(event.target);
			});
	},

	
	initCartFieldsEvents : function ()
	{
		var $ = jQuery;

		this.initFieldsDisplayConditions('bypv_cart_', VirtueMartCart_byPV.CART_FIELDS_SELECTOR);

		$.each(this.CHECKED_USER_FIELDS, function () {
			var fieldName = this;
			
			$('*[name="bypv_cart_' + fieldName + '"]', VirtueMartCart_byPV.CART_FIELDS_SELECTOR)
				.change(function (event) {
					VirtueMartCart_byPV.updateShipmentAndPaymentForm(VirtueMartCart_byPV.CART_FIELDS_SELECTOR);
				});
		});

		if (this.ALLOW_VALIDATION_IN_BROWSER === true && $(this.CART_SELECTOR).hasClass('checkout'))
		{
			$('input, select, textarea', this.CART_FIELDS_SELECTOR).not('input:hidden')
				.change(function(event) {
					return VirtueMartCart_byPV.validateVMAddressField(this);
				})
				.blur(function(event) {
					return VirtueMartCart_byPV.validateVMAddressField(this);
				});
		}

        // Check if browser support Storage (HTML5)
        if (this.REMEMBER_FORM_FIELDS === true && typeof(Storage) !== "undefined" && typeof(JSON) !== "undefined")
        {
			VirtueMartCart_byPV.loadFieldsLocal('cart');

			var $el_address_block = $(this.CART_FIELDS_SELECTOR, this.CART_SELECTOR);
			var $el_inputs = $el_address_block.find('input, select, textarea');
			
			$el_inputs
				.change(function (event) {
					VirtueMartCart_byPV.saveFieldLocal('cart', this);
				});
        }
	},

	initVirtueMartHooks : function ()
	{
		var $ = jQuery;

		if (VirtueMartCart_byPV.getVirtueMartVersion() == 3)
		{
			$(document).on('updateVirtueMartCartModule', 'body', VirtueMartCart_byPV.vmProductUpdate3);
		}
		else if (typeof(Virtuemart) === 'object' && typeof(Virtuemart.productUpdate) === 'function')
		{
			VirtueMartCart_byPV.vmProductUpdateCache = Virtuemart.productUpdate;
			Virtuemart.productUpdate = VirtueMartCart_byPV.vmProductUpdate;
		}
	},
	
	/*** VirtueMart Overrided Methods ***/

	vmProductUpdateCache: null,
	
	vmProductUpdate3: function(event, options)
	{
		var $ = jQuery;

		if (!$.isPlainObject(options) || options['no_update_opc_bypv'] !== true)
		{
			VirtueMartCart_byPV.refreshCartData(null, true);
		}
	},
	
	vmProductUpdate: function(mod, refresh_cart_data)
	{
		var $ = jQuery;

		if (VirtueMartCart_byPV.getVirtueMartVersion() == 3)
		{
			$('body').trigger('updateVirtueMartCartModule', { no_update_opc_bypv: true });
		}
		else
		{
			// Default Value = TRUE
			refresh_cart_data = (refresh_cart_data == null ? true : refresh_cart_data);
			
			if (refresh_cart_data === true)
			{
				VirtueMartCart_byPV.refreshCartData(null, true);
			}
			
			if (this.vmProductUpdateCache !== null)
			{
				if (mod == null)
				{
					mod = $('.vmCartModule');
				}
		
				VirtueMartCart_byPV.vmProductUpdateCache(mod);
			}
		}
	},
	
	/*** Refresh Methods ***/
	
	refreshCartData : function (url, from_vm_product_update)
	{
		var $ = jQuery;
		
		from_vm_product_update = (from_vm_product_update === null ? false : (from_vm_product_update === true));
		
		if (url || $(VirtueMartCart_byPV.CART_SELECTOR).length > 0)
		{
			if (
				url == null
					&& $(VirtueMartCart_byPV.PRODUCT_LIST_SELECTOR, VirtueMartCart_byPV.CART_SELECTOR).length > 0
					&& $('input:hidden[name=task][value=checkout]', VirtueMartCart_byPV.CART_SELECTOR).length > 0
			)
			{
				return this.sendJSONRequest('refreshCartBlocksJS_byPV', null, this.LOADING_OVERLAY_BLOCK_SELECTORS['PRODUCTS_AND_SUMMARY'], { 'from_vm_product_update' : from_vm_product_update });
			}
			else
			{
				VirtueMartCart_byPV.showLoaders('#bypv_cart');
				
				if (url)
				{
					window.document.location.assign(url);
					return false;
				}
				else
				{
					window.document.location.assign(window.document.location.href);
					return false;
				}
			}
		}
		
		return true;
	},
	
	/*** Product Methods ***/
	
	checkProductQuantity : function (el_quantity)
	{
		var $ = jQuery;

		var $el_product_block = $(el_quantity, this.PRODUCT_LIST_SELECTOR).parents(this.PRODUCT_QUANTITY_SELECTOR);
		var $el_step_order_level = $el_product_block.find('input.bypv_step_order_level');

		if (!$(el_quantity).hasClass('bypv_quantity') || $el_product_block.length == 0 || $el_step_order_level.length == 0) {
			return false;
		}

		var step_order_level_array = $el_step_order_level[0].value.split(':');
		
		if (step_order_level_array.length != 3) {
			return false;
		}

		var quantity = parseInt(el_quantity.value);
		var min_order_level = parseInt(step_order_level_array[0]);
		var step_order_level = parseInt(step_order_level_array[1]);
		var max_order_level = parseInt(step_order_level_array[2]);
		
		if (isNaN(quantity)) quantity = 0;
		if (isNaN(min_order_level)) min_order_level = 1;
		if (isNaN(step_order_level)) step_order_level = 1;
		if (isNaN(max_order_level)) max_order_level = 0;
		
		var remainder = quantity % step_order_level;

		if (remainder > 0) {
			quantity = quantity - remainder;
		}
		
		if (quantity < min_order_level) {
			quantity = min_order_level;
		}

		if (max_order_level > 0 && quantity > max_order_level) {
			quantity = max_order_level;
		}

		el_quantity.value = quantity;
		
		return true;
	},
	
	setProductQuantity : function (el_control)
	{
		var $ = jQuery;

		var $el_control = $(el_control, this.PRODUCT_LIST_SELECTOR);
		var $el_product_block = $el_control.parents(this.PRODUCT_QUANTITY_SELECTOR);
		var $el_quantity = $el_product_block.find('input.bypv_quantity');
		var $el_step_order_level = $el_product_block.find('input.bypv_step_order_level');
		
		if ($el_product_block.length == 0 || $el_quantity.length == 0 || $el_step_order_level.length == 0) {
			return false;
		}

		var step_order_level_array = $el_step_order_level[0].value.split(':');
		
		if (step_order_level_array.length != 3) {
			return false;
		}

		var el_quantity = $el_quantity[0];
		var quantity = parseInt(el_quantity.value);
		var step_order_level = parseInt(step_order_level_array[1]);
		
		if (isNaN(quantity) || isNaN(step_order_level)) {
			return false;
		}

		if ($el_control.hasClass('bypv_quantity_plus')) {
			el_quantity.value = quantity + step_order_level; 
		}
		else if ($el_control.hasClass('bypv_quantity_minus')) {
			el_quantity.value = quantity - step_order_level;
		}
		else {
			return false;
		}
		
		return $(el_quantity).change();
	},

	dropProduct : function (el_remove)
	{
		var $ = jQuery;

		var $el_row = $(el_remove, this.PRODUCT_LIST_SELECTOR).parents('tr');
		var $el_quantity = $el_row.find(this.PRODUCT_QUANTITY_SELECTOR + ' input.bypv_quantity');

		if ($el_quantity.length == 0)
		{
			var productId = $el_row.data('bypvOpcForVmProductId');
			
			if (productId)
			{
				$(el_remove).after('<input type="hidden" name="bypv_quantity[' + productId + ']" class="bypv_quantity" value="0" />');
			}
			else return false;
		}
		else
		{
			$el_quantity[0].value = 0;
		}

		
		return VirtueMartCart_byPV.updateProductQuantity(el_remove);
	},

	emptyCart : function ()
	{
		if (confirm(Joomla.JText._('PLG_SYSTEM_OPC_FOR_VM_BYPV_EMPTY_CART_CONFIRM_MESSAGE')))
		{
			return this.sendJSONRequest('emptyCartJS_byPV', null, this.LOADING_OVERLAY_BLOCK_SELECTORS['CART']);
		}
	},
	
	updateProductQuantity: function (el)
	{
		var $ = jQuery;

		var $el_row = $(el, this.PRODUCT_LIST_SELECTOR).parents('tr');
		var $el_quantity = $el_row.find('input.bypv_quantity');

		if ($el_row.length == 0 || $el_quantity.length == 0) {
			return false;
		}

		return this.sendJSONRequest('setCartDataJS_byPV', $el_quantity.serialize(), this.LOADING_OVERLAY_BLOCK_SELECTORS['PRODUCTS_AND_SUMMARY']);
	},

	/*** Coupon Methods ***/
	
	updateCouponCode: function (parent_selector)
	{
		var $ = jQuery;

		var $el_coupon_code = $('input[name=bypv_coupon_code]', parent_selector);

		if ($el_coupon_code.length == 0) {
			return false;
		}
		
		overlay_block_selector = (parent_selector == this.COUPON_CODE_SELECTOR ? 'COUPON_CODE' : 'PRODUCTS_AND_SUMMARY');

		if ($.trim($el_coupon_code[0].value) != '') {
			if (this.sendJSONRequest('setCartDataJS_byPV', $el_coupon_code.serialize(), this.LOADING_OVERLAY_BLOCK_SELECTORS[overlay_block_selector])) {
				$el_coupon_code.val(null);
				return true;
			}
		}
		return false;
	},
	
	dropCouponCode: function ()
	{
		var $ = jQuery;
		
		if (this.sendJSONRequest('setCartDataJS_byPV', 'bypv_coupon_code=', this.LOADING_OVERLAY_BLOCK_SELECTORS['PRODUCTS_AND_SUMMARY'])) {
			return true;
		}

		return false;
	},
	
	/*** Shipment and Payment Methods ***/

	updateShipment: function ()
	{
		var $ = jQuery;

		var $el_shipment = $('input:radio[name=virtuemart_shipmentmethod_id]', this.SHIPMENT_SELECTOR);

		if ($el_shipment.length == 0) {
			return false;
		}

		return this.sendJSONRequest('setCartDataJS_byPV', $el_shipment.serialize(), this.LOADING_OVERLAY_BLOCK_SELECTORS['SHIPMENT']);
	},

	updatePayment: function ()
	{
		var $ = jQuery;
		
		var $el_payment = $('input:radio[name=virtuemart_paymentmethod_id]', this.PAYMENT_SELECTOR);
		
		if ($el_payment.length == 0) {
			return false;
		}
		
		return this.sendJSONRequest('setCartDataJS_byPV', $el_payment.serialize(), this.LOADING_OVERLAY_BLOCK_SELECTORS['PAYMENT']);
	},

	/*** Customer Form Methods ***/

	updateCustomerForm: function ()
	{
		var $ = jQuery;
		
		var $el_customer_type = $('input:radio[name=bypv_customer_type]', this.CUSTOMER_TYPE_SELECT_SELECTOR);
		
		if ($el_customer_type.length == 0) {
			return false;
		}

		var fields_data_bt = this.getFieldsLocal('bt');
		var fields_data_st = this.getFieldsLocal('st');
		var address_data = '';
		
		if (fields_data_bt) address_data += '&address_bt=' + JSON.stringify(fields_data_bt);
		if (fields_data_st) address_data += '&address_st=' + JSON.stringify(fields_data_st);
		
		return this.sendJSONRequest('updateCustomerFormJS_byPV', $el_customer_type.serialize() + address_data, this.LOADING_OVERLAY_BLOCK_SELECTORS['CUSTOMER_TYPE']);
	},

	/*** Shipping Address Methods ***/

	updateShippingAddress: function ()
	{
		var $ = jQuery;

		var $el_shipto = $('input:radio[name=shipto]', this.SHIPPING_ADDRESS_SELECT_SELECTOR);

		if ($el_shipto.length == 0) {
			return false;
		}

		var fields_data_st = this.getFieldsLocal('st');
		var address_data = '';
		
		if (fields_data_st) address_data += '&address_st=' + JSON.stringify(fields_data_st);

		return this.sendJSONRequest('updateShippingAddressJS_byPV', $el_shipto.serialize() + address_data, this.LOADING_OVERLAY_BLOCK_SELECTORS['SHIPPING_ADDRESS']);
	},

	deleteShippingAddress: function (button)
	{
		var $ = jQuery;
		var $elButton = $(button);
		
		var $elShipTo = $elButton.prevAll('input:radio[name=shipto]');
		
		if ($elShipTo.length == 0 || $elShipTo.val() <= 0) {
			return false;
		}

		if (confirm(Joomla.JText._('PLG_SYSTEM_OPC_FOR_VM_BYPV_DELETE_SHIPPING_ADDRESS_CONFIRM_MESSAGE').replace('%s', $elShipTo.next('label').text().trim())))
		{
			return this.sendJSONRequest('deleteShippingAddressJS_byPV', 'shipto=' + $elShipTo.val(), this.LOADING_OVERLAY_BLOCK_SELECTORS['SHIPPING_ADDRESS']);
		}
	},

	/*** Address Methods ***/
	
	updateShipmentAndPaymentForm: function (fields_selector)
	{
		var $ = jQuery;
		
		var $el_address_blocks = $(fields_selector, this.CART_SELECTOR);

		var $el_inputs = $el_address_blocks.find('input, select, textarea').not('input[type=password]');
		
		if ($el_inputs.length == 0) {
			return false;
		}
		
		return this.sendJSONRequest('setCartDataJS_byPV', $el_inputs.serialize(), this.LOADING_OVERLAY_BLOCK_SELECTORS['ADDRESS_FIELD']);
	},
	
	validateJoomlaFields: function (field_name, field_value)
	{
		var $ = jQuery;
		
		field_name = field_name.trim();
		field_value = field_value.trim();
		
		if (field_name !== '' && field_value !== '')
		{
			return this.sendJSONRequest('validateJoomlaFieldsJS_byPV', 'field_name=' + field_name + '&field_value=' + field_value);
		}
	},

	validateVMAddressFields: function ()
	{
		var $ = jQuery;
		
		if (this.ALLOW_VALIDATION_IN_BROWSER !== true || !$(this.CART_SELECTOR).hasClass('checkout'))
			return true;

		var messages = { 'warning' : [] };

		$([ this.BILLING_ADDRESS_SELECTOR, this.SHIPPING_ADDRESS_SELECTOR, this.CART_FIELDS_SELECTOR ]).each(function()
		{
			var $rows = $('table.bypv_fields > tbody > tr', VirtueMartCart_byPV.CART_SELECTOR + ' ' + this);

			if ($rows.length == 0) {
				return true;
			}
		
			$rows.each(function ()
			{
				var $row = $(this);
				
				if (!VirtueMartCart_byPV.validateVMAddressField($row))
				{
					var $label = $('td.label > label', $row);
					messages.warning.push(Joomla.JText._('COM_VIRTUEMART_MISSING_VALUE_FOR_FIELD').replace('%s', $label.text().trim()));
				}
			});
		});

		if (messages.warning.length == 0)
		{
			return true;
		}
		else
		{
			messages.warning.reverse();
			this.renderMessages(messages);
			return false;
		}
	},

	validateVMAddressField: function (el)
	{
		var $ = jQuery;
		
		if (this.ALLOW_VALIDATION_IN_BROWSER !== true || !$(this.CART_SELECTOR).hasClass('checkout'))
			return true;

		var $el = $(el);
		
		var $row = $el.is('table.bypv_fields > tbody > tr') ? $el : $(el).closest('table.bypv_fields > tbody > tr');

		if ($row.length == 0) return true;

		$row.removeClass('bypv_invalid');
		
		var result = false;
		
		if ($row.hasClass('bypv_required'))
		{
			var $values = $('input, select, textarea', $row).not('input:hidden').serializeArray();

			jQuery.each($values, function ()
			{
				if (this.value.trim() !== '')
				{
					result = true;
					return false;
				}
			});

			if (result === false && $row.hasClass('virtuemart_state_id_field') && $('select option[value]', $row).not('option[value=""]').length == 0)
			{
				result = true;
			}
		}
		else
		{
			result = true;
		}
		
		if (result === false)
		{
			$row.addClass('bypv_invalid');
		}

		if ($row.hasClass('virtuemart_country_id_field'))
		{
			window.setTimeout(function () {
				VirtueMartCart_byPV.validateVMAddressField($row.siblings('tr.virtuemart_state_id_field'));
			}, 1000);
		}
		
		return result;
	},
		
	// Not used
	overrideFieldsData: function (text)
	{
		var overrideValue = (window.confirm(text) ? 1 : 0);
		
		return this.sendJSONRequest('overrideFieldsDataJS_byPV', 'override=' + overrideValue, this.LOADING_OVERLAY_BLOCK_SELECTORS['CART']);
	},

	/*** Login / Logout Methods ***/

	login: function ()
	{
		var $ = jQuery;

		var $el_inputs = $(this.LOGIN_SELECTOR, this.CART_SELECTOR).find('input, select, textarea');

		if ($el_inputs.length == 0) {
			return false;
		}
		
		this.clearFieldsLocal();
		return this.sendJSONRequest('loginJS_byPV', $el_inputs.serialize(), this.LOADING_OVERLAY_BLOCK_SELECTORS['LOGIN']);
	},
	
	logout: function ()
	{
		var $ = jQuery;

		this.clearFieldsLocal();
		
		var $elFormLogout = $('<form method="post" style="display: none;"></form>')
			.append('<input type="hidden" name="option" value="com_users" />')
			.append('<input type="hidden" name="task" value="user.logout" />')
			.append($('#bypv_logout_params'))
			.insertAfter(this.CART_SELECTOR)
			.submit()
		;

		// return this.sendJSONRequest('logoutJS_byPV', null, this.LOADING_OVERLAY_BLOCK_SELECTORS['LOGOUT']);
	},

	/*** JSON Request ***/

	sendJSONRequest: function (task, request_data, loading_overlay_block_selectors, params)
	{
		var $ = jQuery;

		if (task) {
			this.json_requests.push({
				'task': task,
				'request_data': (request_data ? request_data.replace("'", "%27") : null), // Encode char ' to %27
				'loading_overlay_block_selectors': loading_overlay_block_selectors,
				'params': (params ? params : null),
			});
		}

		if (this.json_request_progress == null) {
			if (this.json_requests.length > 0) {
				this.json_request_progress = this.json_requests.shift();
			}
			else {
				return false;
			}
		}
		else {
			if (loading_overlay_block_selectors) {
				VirtueMartCart_byPV.showLoaders(loading_overlay_block_selectors);
			}

			return true;
		}
		
		if (this.json_request_progress['loading_overlay_block_selectors']) {
			VirtueMartCart_byPV.showLoaders(this.json_request_progress['loading_overlay_block_selectors']);
		}
		
		$.post(
			$(this.CART_SELECTOR).attr('action')
			, $.param({
				option: 'com_virtuemart',
				view:	'cart',
				task:	this.json_request_progress['task'],
				format:	'json',
				cart_lang: this.cart_lang
			})
				+ '&' + $('input[name=bypv_form_checksum]', this.CART_SELECTOR).serialize()
				+ '&' + this.json_request_progress['request_data']
			
			, function(data, textStatus)
			{
				if ($.isPlainObject(data.replaceHTML))
				{
					$.each(data.replaceHTML, function (block_id, html)
					{
						var el_selector = (block_id == 'form' ? '#bypv_cart' : '#bypv_cart_' + block_id);
						
						var finalizeRequest = function ()
						{
							VirtueMartCart_byPV.replaceBlock(el_selector, html);

							var initMethod = VirtueMartCart_byPV.init_methods[block_id];
							
							if (initMethod)
							{
								var initMethodParams = VirtueMartCart_byPV.json_request_progress['params'];
								
								try {
									if ($.isPlainObject(initMethodParams))
									{
										VirtueMartCart_byPV[initMethod](initMethodParams);
									}
									else if ($.isArray(initMethodParams))
									{
										VirtueMartCart_byPV[initMethod].apply(null, initMethodParams);
									}
									else
									{
										VirtueMartCart_byPV[initMethod]();
									}
								}
								catch (e) {
									e.data = VirtueMartCart_byPV[initMethod];
									VirtueMartCart_byPV.logError(e);
								}
							}

						};

						VirtueMartCart_byPV.showLoaders(el_selector);
						finalizeRequest();
						VirtueMartCart_byPV.hideLoaders(el_selector);
					});
				}

				if (data.systemMessage && !$.isEmptyObject(data.systemMessage))
				{
					VirtueMartCart_byPV.renderMessages(data.systemMessage);
				}
				
				if (data.evalOtherJS && data.evalOtherJS.length > 0)
				{
					$(data.evalOtherJS).each(function (i, code)
					{
						try {
							$.globalEval(code);
						}
						catch (e) {
							e.data = code;
							VirtueMartCart_byPV.logError(e);
						}
					});
				}

				if (data.formChecksum) {
					$('input[name=bypv_form_checksum]', VirtueMartCart_byPV.CART_SELECTOR).val(data.formChecksum);
				}
			}
		)
		.always(function() {
			VirtueMartCart_byPV.hideLoaders(VirtueMartCart_byPV.json_request_progress['loading_overlay_block_selectors']);

			VirtueMartCart_byPV.json_request_progress = null;
			VirtueMartCart_byPV.sendJSONRequest();
		});;
		
		return true;
	},
	
	replaceBlock: function (block_selector, html)
	{
		var $ = jQuery;
		
		var methodIdsIncompatibleWithAjax = $([]);
			
		if (block_selector === VirtueMartCart_byPV.CART_SELECTOR || block_selector === VirtueMartCart_byPV.SHIPMENT_SELECTOR)
		{
			if (VirtueMartCart_byPV.shipments_incompatible_with_ajax.length > 0)
			{
				$(VirtueMartCart_byPV.shipments_incompatible_with_ajax).each(function (i, methodId)
				{
					methodIdsIncompatibleWithAjax = methodIdsIncompatibleWithAjax.add(['#bypv_cart_shipment_' + methodId]);
				});
			}
		}
		
		if (block_selector === VirtueMartCart_byPV.CART_SELECTOR || block_selector === VirtueMartCart_byPV.PAYMENT_SELECTOR)
		{
			if (VirtueMartCart_byPV.payments_incompatible_with_ajax.length > 0)
			{
				$(VirtueMartCart_byPV.payments_incompatible_with_ajax).each(function (i, methodId)
				{
					methodIdsIncompatibleWithAjax = methodIdsIncompatibleWithAjax.add(['#bypv_cart_payment_' + methodId]);
				});
			}
		}

		var $elementsToMove = $();
		var reloadCart = false;
		
		if (methodIdsIncompatibleWithAjax.length > 0)
		{
			$(methodIdsIncompatibleWithAjax).each(function (i, methodId)
			{
				var $elementToMove = $(methodId, block_selector);
				var $elementToReplace = $(methodId, html);
				
				if ($elementToReplace.length > 0)
				{
					if ($elementToMove.length == 0 && VirtueMartCart_byPV.cached_methods[methodId])
					{
						$elementToMove = $(VirtueMartCart_byPV.cached_methods[methodId]);
					}
					
					if ($elementToMove.length == 0)
					{
						reloadCart = true;
						return false;
					}
					else
					{
						$elementsToMove = $elementsToMove.add($elementToMove);
					}
				}
				else if ($elementToMove.length > 0)
				{
					$elementToMove.find('link').appendTo('head');
					VirtueMartCart_byPV.cached_methods[methodId] = $elementToMove[0];
				}
			});
		}
		
		if (reloadCart)
		{
			VirtueMartCart_byPV.showLoaders('#bypv_cart');
			window.document.location.reload();
			return false;
		}

		var $loader = $(VirtueMartCart_byPV.LOADING_OVERLAY_SELECTOR, block_selector);
		var $loaderQueue = ($loader.length === 1 ? $loader.queue() : []);

		$(block_selector).replaceWith(html);
		
		$loader.queue($loaderQueue);
		
		if ($elementsToMove.length > 0)
		{
			$elementsToMove.each(function (i, elementToMove)
			{
				var $elementToReplace = $('#' + elementToMove.id, block_selector);

				$elementLabelToMove = $('input[type=radio][name]:first + label', $elementToReplace);
				
				$elementToReplace[0].parentNode.replaceChild(elementToMove, $elementToReplace[0]);
				
				if ($elementLabelToMove.length > 0)
				{
					$('input[type=radio][name]:first + label', '#' + elementToMove.id)
						.replaceWith($elementLabelToMove);
				}
			});
		}
		
		if ($loader.length > 0)
		{
			var $elForLoader = $(block_selector).children('fieldset');
			if ($elForLoader.length === 0) $elForLoader = $(block_selector);
			
			$elForLoader.css('position', 'relative');
			$elForLoader.append($loader);
		}
	},
	
	/*** Loader Message ***/
	
	LOADING_OVERLAY_DEFINITION: {
		'STANDARD': {
			SHOW: function ($el_loader, $el_block)
			{
				$el_loader.css({
					'top': '0%',
					'left': '0%',
					'width': '100%',
					'height': '100%'
				});
			}
		},
		'TRANSPARENCY': {
			SHOW: function ($el_loader, $el_block)
			{
				// Initital position, size and style
				$el_loader.css({
					'top': '0%',
					'left': '0%',
					'width': '100%',
					'height': '100%',
					'opacity': 0
				});
				
				// Target style
				$el_loader.animate({
					'opacity': 1
				}, 400);
			},
			HIDE: function ($el_loader)
			{
				$el_loader.animate({
					'opacity': 0
				}, 400);
			}
		},
		'CENTER': {
			SHOW: function ($el_loader, $el_block)
			{
				// Initital position and size
				$el_loader.css({
					'top': '50%',
					'left': '50%',
					'width': '0%',
					'height': '0%'
				});

				// Target position and size
				$el_loader.animate({
					'top': '0%',
					'left': '0%',
					'width': '100%',
					'height': '100%'
				}, 400);
			},
			HIDE: function ($el_loader)
			{
				$el_loader.animate({
					'top': '50%',
					'left': '50%',
					'width': '0%',
					'height': '0%'
				}, 400);
			}
		}
	},
	
	isLoader: function (element_selector)
	{
		var $ = jQuery;

		var result = false;
		var $el_loaders = null;
		var is_loader_func = function () {
			return $el_loaders.length > 0 && !$el_loaders.hasClass('bypv_hide');
		};

		$el_loaders =
			$(element_selector + ' > ' + VirtueMartCart_byPV.LOADING_OVERLAY_SELECTOR)
			.add(element_selector + ' > fieldset > ' + VirtueMartCart_byPV.LOADING_OVERLAY_SELECTOR)
		;

		result = is_loader_func();
		
		if (result == false) {
			$el_loaders = $(element_selector).parents().children(VirtueMartCart_byPV.LOADING_OVERLAY_SELECTOR);
			result = is_loader_func();
		}
			
		return result;
	},
	
	showLoaders: function (element_selectors)
	{
		var $ = jQuery;
		
		var loaderFunction = this.LOADING_OVERLAY_STYLE;
		
		if ($.isPlainObject(loaderFunction)) {
			loaderFunction = loaderFunction['SHOW'];
		}

		if (!$.isFunction(loaderFunction)) {
			if (this.LOADING_OVERLAY_DEFINITION[loaderFunction]) {
				loaderFunction = this.LOADING_OVERLAY_DEFINITION[loaderFunction]['SHOW'];
			}
		}

		if (!$.isFunction(loaderFunction)) {
			return false;
		}
		
		if (!$.isArray(element_selectors)) {
			element_selectors = [ element_selectors ];
		}

		$.each(element_selectors, function (i) {
			var $el_block = $(element_selectors[i]);
			
			if ($el_block.length == 0) {
				return true;
			}

			if (VirtueMartCart_byPV.isLoader(element_selectors[i])) {
				return true;
			}

			// Hide loaders of children's blocks
			VirtueMartCart_byPV.hideLoaders(element_selectors[i]);

			var $el_loader = $(VirtueMartCart_byPV.LOADING_OVERLAY_HTML); 

			var $el_loader_block = $el_block.children('fieldset');
			if ($el_loader_block.length == 0) $el_loader_block = $el_block;

			$el_loader_block.css('position', 'relative');
			$el_loader_block.append($el_loader);
			
			loaderFunction($el_loader, $el_loader_block);
		});
	},
	
	hideLoaders: function (element_selectors)
	{
		var $ = jQuery;

		var loaderFunction = this.LOADING_OVERLAY_STYLE;
		
		if ($.isPlainObject(loaderFunction)) {
			loaderFunction = loaderFunction['HIDE'];
		}

		if (!$.isFunction(loaderFunction)) {
			if (this.LOADING_OVERLAY_DEFINITION[loaderFunction]) {
				loaderFunction = this.LOADING_OVERLAY_DEFINITION[loaderFunction]['HIDE'];
			}
		}

		if (element_selectors == null) {
			element_selectors = this.CART_SELECTOR;
		}
		
		if (!$.isArray(element_selectors)) {
			element_selectors = [ element_selectors ];
		}

		$.each(element_selectors, function (i) {
			$(VirtueMartCart_byPV.LOADING_OVERLAY_SELECTOR, element_selectors[i]).each(function () {
				var $el_loader = $(this);
				
				$el_loader.clearQueue().queue(function ()
				{
					var $el_loader = $(this);

					$el_loader.addClass('bypv_hide');
	
					if ($.isFunction(loaderFunction)) {
						loaderFunction($el_loader);
					}

					$el_loader.queue(function ()
					{
						$(this).remove();
						$(this).dequeue();
					});
					
					$el_loader.dequeue();
				})
			});
		});
	},
	
	/*** Messages ***/
	
	renderMessages: function (messages)
	{
		var $ = jQuery;
		
		var $el_system_message_container = $(VirtueMartCart_byPV.SYSTEM_MESSAGE_CONTAINER_SELECTOR);

		if ($el_system_message_container.length == 0)
		{
			$el_system_message_container = $(VirtueMartCart_byPV.SYSTEM_MESSAGE_CONTAINER_HTML_FALLBACK)
				.insertBefore(VirtueMartCart_byPV.CART_SELECTOR);
		}

		if ($el_system_message_container.length > 0)
		{
			Joomla.renderMessages(messages);
			
			var target_scrollTop = $el_system_message_container.offset().top;

			if ($(document).scrollTop() > target_scrollTop) {
				$('html, body').animate({
					'scrollTop': (
						target_scrollTop < VirtueMartCart_byPV.SYSTEM_MESSAGE_CONTAINER_SCROLL_OFFSET
						? 0
						: target_scrollTop - VirtueMartCart_byPV.SYSTEM_MESSAGE_CONTAINER_SCROLL_OFFSET
					)
				}, 200, 'swing');
			}
		}
	},
	
	/*** Fields Storage ***/
	
	saveFieldLocal : function (type, el)
	{
		var $ = jQuery;
		var $el = $(el);

		if ($el.attr('type') == 'password' || $el.attr('name') == 'shipto') return;
		
		var shipto = null;
		
		if (type == 'st')
		{
			shipto = $('*[name=shipto]:checked', this.SHIPPING_ADDRESS_SELECT_SELECTOR).val();
			
			if (shipto === '-1') return;
		}
		
		var data = sessionStorage.getItem('bypv.virtuemartcart.' + type);
		
		try {
			data = JSON.parse(data);
		}
		catch (e) {
			e.data = data;
			VirtueMartCart_byPV.logError(e);
		}
		
		if (data === null) data = {};

		var value = $(el).val();
		
		if ($el.attr('type') == 'radio' || $el.attr('type') == 'checkbox')
		{
			value = [];
			
			$('*[name="' + $el.attr('name') + '"]:checked').each(function () {
				value.push(this.value);
			});
			
			if (value.length == 0) value = null;
			else if (value.length == 1) value = value.pop();
		}
		
		if (type == 'st') {
			if (typeof(data[shipto]) === "undefined") data[shipto] = {};
			data[shipto][$el.attr('name')] = value;
		}
		else data[$el.attr('name')] = value;

		sessionStorage.setItem('bypv.virtuemartcart.' + type, JSON.stringify(data));
	},

	getFieldsLocal : function (type)
	{
		// Check if browser support Storage (HTML5)
        if (this.REMEMBER_FORM_FIELDS !== true || typeof(Storage) === "undefined" || typeof(JSON) === "undefined") return;

		var $ = jQuery;
		var shipto = null;
		
		if (type == 'st')
		{
			shipto = $('*[name=shipto]:checked', this.SHIPPING_ADDRESS_SELECT_SELECTOR).val();
			
			if (shipto === '-1') return;
		}

		var data = sessionStorage.getItem('bypv.virtuemartcart.' + type);
		
		try {
			data = JSON.parse(data);
			if (data !== null && type == 'st') data = data[shipto];
		}
		catch (e) {
			e.data = data;
			VirtueMartCart_byPV.logError(e);
		}

		return data;
	},
	
	loadFieldsLocal : function (type)
	{
		var $ = jQuery;
		var shipto = null;
		
		if (type == 'st')
		{
			shipto = $('*[name=shipto]:checked', this.SHIPPING_ADDRESS_SELECT_SELECTOR).val();
			
			if (shipto === '-1') return;
		}

		var data = sessionStorage.getItem('bypv.virtuemartcart.' + type);
		
		try {
			data = JSON.parse(data);
			if (data !== null && type == 'st') data = data[shipto];
		}
		catch (e) {
			e.data = data;
			VirtueMartCart_byPV.logError(e);
		}

		if (data === null) return;

		var $el = null;
		var value = null;
		var valueOld = null;
		
		for (var el_name in data)
		{
			$el = $('*[name="' + el_name + '"]', this.CART_SELECTOR).not('input:hidden');

			valueOld = $el.val();
			value = data[el_name];
			
			if ($el.attr('type') == 'radio' || $el.attr('type') == 'checkbox')
			{
				valueOld = [];
				
				$('*[name="' + $el.attr('name') + '"]:checked').each(function () {
					valueOld.push(this.value);
				});
				
				if (valueOld.length == 0) valueOld = null;
				else if (valueOld.length == 1) valueOld = valueOld.pop();
			}
			
			if (valueOld != value)
			{
				if (!$.isArray(value)) value = [ value ];
				
				$el.val(value);
				
				if ($el.attr('type') == 'radio' || $el.attr('type') == 'checkbox')
				{
					$el = $el.filter(':checked');
				}
				else if ($el.prop('tagName') == 'SELECT')
				{
					$el.trigger('liszt:updated').trigger('chosen:updated');
				}
	
				$el.change();
			}
		}
	},
	
	clearFieldsLocal : function (bt, st)
	{
		// Check if browser support Storage (HTML5)
        if (this.REMEMBER_FORM_FIELDS !== true || typeof(Storage) === "undefined") return;

		bt = (bt == null ? true : bt);
		st = (st == null ? true : st);

		if (bt === true) sessionStorage.setItem('bypv.virtuemartcart.bt', null);
		if (st === true) sessionStorage.setItem('bypv.virtuemartcart.st', null);
	},
	
	getVirtueMartVersion: function ()
	{
		if (typeof(Virtuemart) === 'object')
		{
			return (
				typeof(Virtuemart.updateContent) === 'function'
				||
				typeof(Virtuemart.quantityErrorAlert) === 'function'
				? 3 : 2
			);
		}
		
		return null;
	},
	
	logError : function (error)
	{
		window.setTimeout(function ()
		{
			if (VirtueMartCart_byPV.show_error_data === true && error.data)
			{
				console.log(error.data);
			}
			
			throw error;
		}, 0);
	}

};

/*** Document OnLoad ***/

jQuery( function($) {
	VirtueMartCart_byPV.initialize();
	VirtueMartCart_byPV.initVirtueMartHooks();
});
