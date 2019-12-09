<?php defined('_JEXEC') or die('Restricted access');

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

if(!class_exists('VmView')) require(JPATH_VM_SITE . DS . 'helpers' . DS . 'vmview.php');

class VirtueMartViewCart_byPV extends VmView
{
	/*** VM ***/
	
	public $html = FALSE;
	
	/*** Template Cover constants ***/

	const FORM_COVER = '#FORM_BYPV-BEGIN#%1$s#FORM_BYPV-END#';
	const BLOCK_COVER = '#BLOCK_BYPV-%1$s-BEGIN#%2$s#BLOCK_BYPV-%1$s-END#';
	
	/*** Form Template constants ***/
	
	const TPL_CHANGE_SHOPPER			= 'change_shopper';
	const TPL_CHANGE_SHOPPER_GROUP		= 'change_shopper_group';
	const TPL_PRODUCT_LIST				= 'product_list';
	const TPL_ORDER_SUMMARY				= 'order_summary';
	const TPL_COUPON_CODE				= 'coupon_code';
	const TPL_SHIPMENTS					= 'shipments';
	const TPL_PAYMENTS					= 'payments';
	const TPL_CUSTOMER_TYPE_SELECT		= 'customer_type_select';
	const TPL_LOGIN						= 'login';
	const TPL_BILLING_ADDRESS			= 'billing_address';
	const TPL_SHIPPING_ADDRESS			= 'shipping_address';
	// TODO: Deprecated
	const TPL_SHIPPING_ADDRESS_SELECT	= 'shipping_address_select';
	const TPL_ADVERTISEMENTS			= 'advertisements';
	const TPL_CART_FIELDS				= 'cart_fields';
	const TPL_COMMENT					= 'comment';
	const TPL_TOS						= 'tos';
	const TPL_EXTERNAL_MODULES			= 'external_modules';
	
	public static $FORM_TEMPLATES = array(
		self::TPL_CHANGE_SHOPPER			=> '',
		self::TPL_CHANGE_SHOPPER_GROUP		=> '',
		self::TPL_PRODUCT_LIST				=> '',
		self::TPL_ORDER_SUMMARY				=> '',
		self::TPL_COUPON_CODE				=> '',
		self::TPL_SHIPMENTS					=> '',
		self::TPL_PAYMENTS					=> '',
		self::TPL_CUSTOMER_TYPE_SELECT		=> '',
		self::TPL_LOGIN						=> '',
		self::TPL_BILLING_ADDRESS			=> '',
		self::TPL_SHIPPING_ADDRESS			=> '',
		self::TPL_SHIPPING_ADDRESS_SELECT	=> '',
		self::TPL_ADVERTISEMENTS			=> '',
		self::TPL_CART_FIELDS				=> '',
		self::TPL_COMMENT					=> '',
		self::TPL_TOS						=> '',
		self::TPL_EXTERNAL_MODULES			=> '',
	);

	private static $AUTOCOMPLETE_FIELD_NAMES = array(
		'username'    => 'username',
		'password'    => 'new-password',
		'password2'   => 'new-password',
		
		'company'     => 'organization',
		'title'       => 'honorific-prefix',
		'name'        => 'name',
		'first_name'  => 'given-name',
		'middle_name' => 'additional-name',
		'last_name'   => 'family-name',
		'address_1'   => 'address-line1',
		'address_2'   => 'address-line2',
		'city'        => 'address-level2',
		'zip'         => 'postal-code',
		// 'virtuemart_country_id'     => 'country', // country-name
		
		'email'       => 'email',
		'phone_1'     => 'tel',
	);
	
	private $bypv_form_checksum = array();
	private $form_template_initialization_js = array();
	private $form_template_cache = array();
	private $loaded_form_templates = array();
	
	private $layout_html = NULL;
	private $layout_css = NULL;
	
	/*** OVERRIDE ***/
	
	public function __construct($config = array())
	{
		$config['base_path'] = OPC_FOR_VM_BYPV_PLUGIN_PATH;

		parent::__construct($config);
		
		// Preserve VirtueMart Cart path for non-checkout templates.
		
		$app = JFactory::getApplication();
		$this->_path['template'][] = JPATH_THEMES . DS . $app->getTemplate() . DS . 'html' . DS . 'com_virtuemart' . DS . 'cart';
		$this->_path['template'][] = JPATH_VM_SITE . DS . 'views' . DS . 'cart' . DS . 'tmpl';
				
		// Initialize Form Checksum
		
		$input = JFactory::getApplication()->input;
		$form_checksum_old = $input->getString('bypv_form_checksum');
		
		if (!empty($form_checksum_old)) {
			$this->bypv_form_checksum = json_decode(base64_decode($form_checksum_old), TRUE);
			
			if (!is_array($this->bypv_form_checksum)) {
				$this->bypv_form_checksum = array();
			}
		}
		
		// Layout
		
		$plugin_layout = plgSystemOPC_for_VM_byPV::getPluginParam('plugin_layout');
		
		if (strpos($plugin_layout, '::') === FALSE) $this->layout_html = $plugin_layout;
		else list($this->layout_html, $this->layout_css) = explode('::', $plugin_layout);
	}
	
	public function loadTemplate($tpl = null)
	{
		$layout = $this->getLayout();
		
		if (in_array($layout, array('default', $this->layout_html))) {
			$layout = $this->layout_html;
		}
		
		$previousLayout = $this->setLayout($layout);
		$html = parent::loadTemplate($tpl);
		$this->setLayout($previousLayout);

		// Patch for the plugin "VMprivacy (GiBiLogic)"
		
		if ($tpl === 'buttons' && plgSystemOPC_for_VM_byPV::detectExtension_byPV('vm_privacy'))
		{
			$html = '<!-- {vmprivacy} -->' . $html;
		}

		return (
			$this->getLayout() === 'default' && empty($tpl)
				? sprintf(self::FORM_COVER, $html)
				: $html
			);
	}
	
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();

		$pathway = $app->getPathway();
		
		$layoutName = $this->getLayout();
		if (!$layoutName) $layoutName = JRequest::getWord('layout', 'default');
		
		$this->layoutName = $layoutName;
		
		vmLanguage::loadJLang('com_virtuemart_shoppers', TRUE);

		if ($layoutName == 'orderdone')
		{
			// We don't want validate cart when show checkout
			$cart = VirtueMartCart::getCart();

			$this->cart = $cart;
			
			// This is not used in VM (deprecated variables in View)... in orderdone is used vmRequest directly...
// 			$this->display_title = !isset($this->display_title) ? vRequest::getBool('display_title', TRUE) : $this->display_title;
// 			$this->display_loginform = !isset($this->display_loginform) ? vRequest::getBool('display_loginform', TRUE) : $this->display_loginform;
			$this->html = empty($this->html) ? vRequest::get('html', $this->cart->orderdoneHtml) : $this->html;

			$this->cart->orderdoneHtml = FALSE;
			$this->cart->setCartIntoSession(TRUE, TRUE);

			$pathway->addItem(JText::_('COM_VIRTUEMART_CART_THANKYOU'));
			$document->setTitle(JText::_('COM_VIRTUEMART_CART_THANKYOU'));
		}
		else
		{
			$cart_bypv = VirtueMartCart_byPV::getCart();
			$cart_bypv->setCheckoutDisplayed(TRUE);
			$cart_bypv->setCartIntoSession();
				
			$cart_bypv->detectChangesInUserFieldsData();
			
			// We don't want validate cart when show checkout
			$cart = VirtueMartCart::getCart();
			$cart->_redirect = TRUE;
			// @since VM 2.0.26a changed condition in parent::display() from _redirect to _inCheckOut
// 			$cart->_inCheckOut = TRUE;

			$this->cart = $cart;
				
			$cart_bypv->fixShipment();
			$cart_bypv->fixPayment();
			
			if (VM_VERSION < 3)
			{
				$cart->prepareCartViewData();
				
				if (VmConfig::get('enable_content_plugin', 0))
				{
					shopFunctionsF::triggerContentPlugin($cart->vendor, 'vendor', 'vendor_terms_of_service');
				}
			}
			else
			{
				$cart->prepareCartData();
				
				// Fix of wrong behaviour of VM when shipment or payment has prices in non-vendor currency
				
				$dispatcher = new JDispatcher();
				
				if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
				JPluginHelper::importPlugin('vmshipment', null, true, $dispatcher);
				JPluginHelper::importPlugin('vmpayment', null, true, $dispatcher);
				
				$cartPrices = &$cart->cartPrices;
				
				$returnValues = $dispatcher->trigger('onSelectedCalculatePrice', array(
					$cart,
					&$cartPrices,
					&$cartPricesName
				));
				
				$cartPrices['shipmentTax'] = $cartPrices['salesPriceShipment'] - $cartPrices['shipmentValue'];
				$cartPrices['paymentTax'] = $cartPrices['salesPricePayment'] - $cartPrices['paymentValue'];
				
				$cartPrices['billTotal'] =
					$cartPrices['salesPriceShipment'] +
					$cartPrices['salesPricePayment'] +
					$cartPrices['withTax'] +
					$cartPrices['salesPriceCoupon'];
				
				$cartPrices['billTaxAmount'] =
					$cartPrices['taxAmount'] +
					$cartPrices['shipmentTax'] +
					$cartPrices['paymentTax'] +
					$cartPrices['cartTax'];
				
				unset($cartPrices, $cartPricesName);
				
				// End of fix
				
				$cart->prepareVendor();
			}
		
			// Continue Link
			
			$lastVisitedCategoryId = shopFunctionsF::getLastVisitedCategoryId();
			$categoryQueryParam = (!empty($lastVisitedCategoryId) ? '&virtuemart_category_id=' . $lastVisitedCategoryId : '');
			
			if (method_exists('shopFunctionsF', 'getLastVisitedItemId'))
			{
				$lastVisitedItemid = shopFunctionsF::getLastVisitedItemId();
			}
			$itemQueryParam = (!empty($lastVisitedItemid) ? '&Itemid=' . $lastVisitedItemid : '');
			
			if (!empty($categoryQueryParam) || !empty($itemQueryParam))
			{
				$this->continue_link = JRoute::_('index.php?option=com_virtuemart&view=category' . $categoryQueryParam . $itemQueryParam, FALSE);
				$this->continue_link_html = '<a class="continue_link" href="' . $this->continue_link . '" >' . JText::_('COM_VIRTUEMART_CONTINUE_SHOPPING') . '</a>';
			}
			else
			{
				$this->continue_link = NULL;
				$this->continue_link_html = NULL;
			}

			$this->cart_link = JRoute::_('index.php?option=com_virtuemart&view=cart' . $itemQueryParam, FALSE);

			// Coupon Code
			
			$this->couponCode = (isset($this->cart->couponCode) ? $this->cart->couponCode : '');
			$this->coupon_text = JText::_('COM_VIRTUEMART_COUPON_CODE_' . (empty($this->cart->couponCode) ? 'ENTER' : 'CHANGE'));

			// Currency
			
			if (!class_exists ('CurrencyDisplay')) require(JPATH_VM_ADMINISTRATOR . '/helpers/currencydisplay.php');
			$currencyDisplay = CurrencyDisplay::getInstance($this->cart->pricesCurrency);
			
			$this->currencyDisplay = $currencyDisplay;
		
			// Custom Fields
			
			if (VM_VERSION == 3)
			{
				$this->customfieldsModel = VmModel::getModel ('Customfields');
			}
			
			// Total In Payment Currency
			
			if (empty($this->cart->virtuemart_paymentmethod_id))
			{
				$totalInPaymentCurrency = NULL;
			}
			elseif (!$this->cart->paymentCurrency || $this->cart->paymentCurrency == $this->cart->pricesCurrency)
			{
				$totalInPaymentCurrency = NULL;
			}
			else
			{
				$paymentCurrency = CurrencyDisplay::getInstance($this->cart->paymentCurrency);
				
				$cartPrices = $this->getCartPrices_byPV();
				
				$totalInPaymentCurrency = $paymentCurrency->priceDisplay(
					$cartPrices['billTotal'],
					$this->cart->paymentCurrency
				);
				
				$currencyDisplay = CurrencyDisplay::getInstance($this->cart->pricesCurrency);
			}
			
			$this->totalInPaymentCurrency = $totalInPaymentCurrency;

			// Checkout Advertise
			
			JPluginHelper::importPlugin('vmcoupon');
			JPluginHelper::importPlugin('vmshipment');
			JPluginHelper::importPlugin('vmpayment');
			
			$checkoutAdvertise=array();

			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger('plgVmOnCheckoutAdvertise', array($this->cart, &$checkoutAdvertise));
			
			$this->checkoutAdvertise = $checkoutAdvertise;
			
			/////
			
			$cart_bypv->fixShipment();
			$cart_bypv->fixPayment();
			
			// Validation
			
			if ($cart->getDataValidated())
			{
				$pathway->addItem(JText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU'));
				$document->setTitle(JText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU'));
				$checkout_task = 'confirm';
			}
			else
			{
				$pathway->addItem(JText::_('COM_VIRTUEMART_CART_OVERVIEW'));
				$document->setTitle(JText::_('COM_VIRTUEMART_CART_OVERVIEW'));
				$checkout_task = 'checkout';
			}
			
			$this->checkout_task = $checkout_task;
			
			$this->select_shipment_text = JText::_(
				'COM_VIRTUEMART_CART_' . (empty($cart->virtuemart_shipmentmethod_id) ? 'EDIT' : 'CHANGE') . '_SHIPPING'
			);
		
			$this->select_payment_text = JText::_(
				'COM_VIRTUEMART_CART_' . (empty($cart->virtuemart_paymentmethod_id) ? 'EDIT' : 'CHANGE') . '_PAYMENT'
			);

			$this->prepareShipmentMethods();
			$this->preparePaymentMethods();

			// Check Shipments and Payments

			$shipments = $this->getShipmentsData_byPV();
			if (!isset($shipments->OPTIONS[$cart_bypv->getShipment()]))
			{
				$cart_bypv->setShipment(NULL);
			}

			$payments = $this->getPaymentsData_byPV();
			if (!isset($payments->OPTIONS[$cart_bypv->getPayment()]))
			{
				$cart_bypv->setPayment(NULL);
			}
					
			// VM Fix - invalid shipment and payment is reset later and name is blank string

			if ($cart->virtuemart_shipmentmethod_id == 0) {
				$cart->cartData['shipmentName'] = JText::_('COM_VIRTUEMART_CART_NO_SHIPMENT_SELECTED');
			}
			if ($cart->virtuemart_paymentmethod_id == 0) {
				$cart->cartData['paymentName'] = JText::_('COM_VIRTUEMART_CART_NO_PAYMENT_SELECTED');
			}
		
			// Set Order Language
			
			$lang = JFactory::getLanguage();
			$this->order_language = $lang->getTag();
		}
		
		$this->useSSL = VmConfig::get('useSSL', 0);
		$this->useXHTML = TRUE;
		
		$cart->setCartIntoSession();
		
		vmLanguage::loadJLang('com_virtuemart', TRUE);
		
		plgSystemOPC_for_VM_byPV::saveScriptsJS('__init');

		// Chosen library
		vmJsApi::chosenDropDowns();
		plgSystemOPC_for_VM_byPV::saveScriptsJS('__fields');
		
		parent::display($tpl);
		
		// Set the document
		$this->setDocument_byPV();
	}
	
	/*** Methods byPV ***/
	
	private function getFormattedPrice_byPV($product_prices, $name, $name_alt = NULL, $quantity = 1.0)
	{
		$price = 0;
		$nb = -1;
	
		if (is_array($product_prices))
		{
			if (isset($product_prices[$name])) $price = $product_prices[$name];
		}
		else $price = $product_prices;
	
		if (empty($price) && !plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_zero_amounts'))
		{
			return '';
		}
	
		if (!isset($this->currencyDisplay->_priceConfig[$name]))
		{
			$name = $name_alt;
		}
	
		if (isset($this->currencyDisplay->_priceConfig[$name], $this->currencyDisplay->_priceConfig[$name][1]))
		{
			$nb = $this->currencyDisplay->_priceConfig[$name][1];
		}
	
		return $this->currencyDisplay->priceDisplay($price, 0, (float) $quantity, FALSE, $nb);
	}
	
	private function prepareShipmentMethods()
	{
		$shipments_shipment_rates = array();
		
		$shipmentModel = VmModel::getModel('Shipmentmethod');
		$shipments = $shipmentModel->getShipments();
		
		if (empty($shipments))
		{
			vmInfo('COM_VIRTUEMART_NO_SHIPPING_METHODS_CONFIGURED', '');
		}
		else
		{
			if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmshipment');
			
			$dispatcher = JDispatcher::getInstance();
			$returnValues = $dispatcher->trigger('plgVmDisplayListFEShipment', array(
				$this->cart,
				empty($this->cart->virtuemart_shipmentmethod_id) ? 0 : $this->cart->virtuemart_shipmentmethod_id,
				&$shipments_shipment_rates
			));
		}
		
		$this->shipments_shipment_rates = $shipments_shipment_rates;
		$this->found_shipment_method = count($shipments_shipment_rates);
		$this->shipment_not_found_text = JText::_('COM_VIRTUEMART_CART_NO_SHIPPING_METHOD_PUBLIC');
	}

	private function preparePaymentMethods()
	{
		$payments_payment_rates = array();
		
		$paymentModel = VmModel::getModel('Paymentmethod');
		$payments = $paymentModel->getPayments(true, false);
		
		if (empty($payments))
		{
			vmInfo('COM_VIRTUEMART_NO_PAYMENT_METHODS_CONFIGURED', '');
		}
		else
		{
			if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmpayment');
			
			$dispatcher = JDispatcher::getInstance();
			$returnValues = $dispatcher->trigger('plgVmDisplayListFEPayment', array(
				$this->cart,
				empty($this->cart->virtuemart_paymentmethod_id) ? 0 : $this->cart->virtuemart_paymentmethod_id,
				&$payments_payment_rates
			));
		}
		
		$this->paymentplugins_payments = $payments_payment_rates;
		$this->found_payment_method = count($payments_payment_rates);
		// ??? Language Constant has %s in English
		$this->payment_not_found_text = JText::sprintf('COM_VIRTUEMART_CART_NO_PAYMENT_METHOD_PUBLIC', '');
	}
	
	protected function setDocument_byPV()
	{
		if (plgSystemOPC_for_VM_byPV::$requestFormat === 'json') return;
		
		$document = JFactory::getDocument();
		$document->setMetaData('robots', 'noindex, nofollow');
		
		// Translations used in JavaScript
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_empty_cart'))
		{
			JText::script('PLG_SYSTEM_OPC_FOR_VM_BYPV_EMPTY_CART_CONFIRM_MESSAGE');
		}
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_delete_shipping_address'))
		{
			JText::script('PLG_SYSTEM_OPC_FOR_VM_BYPV_DELETE_SHIPPING_ADDRESS_CONFIRM_MESSAGE');
		}
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_validation_in_browser'))
		{
			foreach (array('ERROR', 'MESSAGE', 'NOTICE', 'WARNING', 'COM_VIRTUEMART_MISSING_VALUE_FOR_FIELD') as $msgType)
			{
				JText::script($msgType);
			}
		}
		
		// VirtueMart
		
		if (VM_VERSION == 3)
		{
			vmJsApi::jPrice();
			vmJsApi::chosenDropDowns();
		}
		
		// OPC
		
		$document->addStyleSheet($this->getScriptUrl_byPV(self::SU_CSS, 'virtuemartcart_bypv'));
		$document->addScript($this->getScriptUrl_byPV(self::SU_JS, 'virtuemartcart_bypv'));
		
		$document->addScriptDeclaration("VirtueMartCart_byPV.base_uri = '" . JURI::root() . "';");
		$document->addScriptDeclaration("VirtueMartCart_byPV.cart_lang = '" . JFactory::getLanguage()->getTag() . "';");
		
		if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('remember_form_fields'))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.REMEMBER_FORM_FIELDS = false;");
		}
		
		$PLGCFG_TRACKING_OF_CHANGES = plgSystemOPC_for_VM_byPV::getPluginParam('tracking_of_changes');
		
		$tmp = array_merge(
			array_diff(array('zip', 'virtuemart_country_id'), $PLGCFG_TRACKING_OF_CHANGES),
			array_diff($PLGCFG_TRACKING_OF_CHANGES, array('zip', 'virtuemart_country_id'))
		);
		
		if (!empty($tmp))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.CHECKED_USER_FIELDS = " . json_encode($PLGCFG_TRACKING_OF_CHANGES) . ";");
		}

		if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('validate_fields_of_joomla_immediately'))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.VALIDATED_JOOMLA_FIELDS = [];");
		}

		if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_validation_in_browser'))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.ALLOW_VALIDATION_IN_BROWSER = false;");
		}
		
		$PLGCFG_SHOPPER_FIELDS_DISPLAY_CONDITIONS = plgSystemOPC_for_VM_byPV::getPluginParam('shopper_fields_display_conditions');
		
		if (!empty($PLGCFG_SHOPPER_FIELDS_DISPLAY_CONDITIONS))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.SHOPPER_FIELDS_DISPLAY_CONDITIONS = " . json_encode($PLGCFG_SHOPPER_FIELDS_DISPLAY_CONDITIONS) . ";");
		}
		
		$shipments_incompatible_with_ajax = plgSystemOPC_for_VM_byPV::getPluginParam('shipments_incompatible_with_ajax');
		
		if (!empty($shipments_incompatible_with_ajax))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.shipments_incompatible_with_ajax = " . json_encode($shipments_incompatible_with_ajax) . ";");
		}

		$payments_incompatible_with_ajax = plgSystemOPC_for_VM_byPV::getPluginParam('payments_incompatible_with_ajax');
		
		if (!empty($payments_incompatible_with_ajax))
		{
			$document->addScriptDeclaration("VirtueMartCart_byPV.payments_incompatible_with_ajax = " . json_encode($payments_incompatible_with_ajax) . ";");
		}

		$PLGCFG_LOADING_OVERLAY_SHOW_STYLE = plgSystemOPC_for_VM_byPV::getPluginParam('loading_overlay_show_style');
		$PLGCFG_LOADING_OVERLAY_HIDE_STYLE = plgSystemOPC_for_VM_byPV::getPluginParam('loading_overlay_hide_style');
		
		if ($PLGCFG_LOADING_OVERLAY_SHOW_STYLE !== 'CENTER' || $PLGCFG_LOADING_OVERLAY_HIDE_STYLE !== 'TRANSPARENCY')
		{
			$LOADING_OVERLAY_STYLE = array(
				'SHOW' => $PLGCFG_LOADING_OVERLAY_SHOW_STYLE,
				'HIDE' => $PLGCFG_LOADING_OVERLAY_HIDE_STYLE,
			);
			
			$document->addScriptDeclaration("VirtueMartCart_byPV.LOADING_OVERLAY_STYLE = " . json_encode($LOADING_OVERLAY_STYLE) . ";");
		}
		
		$use_plugin_layout_css = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('use_plugin_layout_css');
		
		if ($use_plugin_layout_css)
		{
			$layout_html = ($this->layout_html === 'vertical2' ? 'vertical' : $this->layout_html);
			
			$layout_css_variant = $layout_html;
			if (!empty($this->layout_css)) $layout_css_variant .= '_' . $this->layout_css;

			$document->addStyleSheet($this->getScriptUrl_byPV(self::SU_CSS, 'layout__base'));
			($layout_variant_base_css = $this->getScriptUrl_byPV(self::SU_CSS, 'layout_' . $layout_html . '__base'))
				? $document->addStyleSheet($layout_variant_base_css) : NULL;
			$document->addStyleSheet($this->getScriptUrl_byPV(self::SU_CSS, 'layout_' . $layout_css_variant));

			$use_plugin_layout_responsive_css = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('use_plugin_layout_responsive_css');
			
			if ($use_plugin_layout_responsive_css)
			{
				$document->addStyleSheet($this->getScriptUrl_byPV(self::SU_CSS, 'layout__base_responsive'));
				($layout_variant_responsive_css = $this->getScriptUrl_byPV(self::SU_CSS, 'layout_' . $layout_html . '_responsive'))
					? $document->addStyleSheet($layout_variant_responsive_css) : NULL;
			}
		}
		
		$plugin_theme_css = plgSystemOPC_for_VM_byPV::getPluginParam('plugin_theme_css');
		
		if ($plugin_theme_css !== 'none')
		{
			$document->addStyleSheet($this->getScriptUrl_byPV(self::SU_CSS, 'theme_' . $plugin_theme_css));
		}

		$use_plugin_custom_css = plgSystemOPC_for_VM_byPV::getPluginParam('use_plugin_custom_css');
		
		if ($use_plugin_custom_css)
		{
			$document->addStyleSheet($this->getScriptUrl_byPV(self::SU_CSS, 'custom'));
		}
	}
	
	const SU_CSS = 'css';
	const SU_JS = 'js';
	
	private function getScriptUrl_byPV($type, $script_name)
	{
		static $urlPrefix = NULL;
		static $urlSuffix = NULL;
		static $templateFolder = NULL;
		
		if ($urlPrefix === NULL || $urlSuffix === NULL || $templateFolder === NULL)
		{
			$urlPrefix = JURI::root(TRUE);

			$installer = JInstaller::getInstance();
			$installer->setPath('source', OPC_FOR_VM_BYPV_PLUGIN_PATH);
			
			if ($installer->findManifest()) {
				$manifest = $installer->getManifest();
					
				if (isset($manifest->version)) $version = $manifest->version;
			}
			
			if (isset($version)) $urlSuffix = '?v=' . $version;
			else $urlSuffix = '';

			$templateFolder = '/templates/';

			if (VM_VERSION < 3)
			{
				$templateFolder .= JFactory::getApplication()->getTemplate();
			}
			else
			{
				if (!class_exists('VmTemplate')) require(VMPATH_SITE . '/helpers/vmtemplate.php');
				$vmStyle = VmTemplate::loadVmTemplateStyle();
				$templateFolder .= $vmStyle['template'];
			}
		}
		
		$scriptUrl = $urlPrefix;
		$templateScriptPath = $templateFolder . sprintf('/%1$s/plg_system_opc_for_vm_bypv/%2$s.%1$s', $type, $script_name);
		$pluginScriptPath = '/media' . sprintf('/plg_system_opc_for_vm_bypv/%1$s/%2$s.%1$s', $type, $script_name);

		if (is_file(JPATH_ROOT . $templateScriptPath))
		{
			$scriptUrl .= $templateScriptPath;
		}
		elseif (is_file(JPATH_ROOT . $pluginScriptPath))
		{
			$scriptUrl .= $pluginScriptPath . $urlSuffix;
		}
		else {
			return NULL;
		}
		
		return $scriptUrl;
	}
	
	public function isFormTemplateLoaded_byPV($form_tpl)
	{
		return isset($this->form_template_cache[$form_tpl]);
	}
	
	public function loadFormTemplate_byPV($form_tpl)
	{
		if (!isset(self::$FORM_TEMPLATES[$form_tpl])) {
			return NULL;
		}
		
		// TODO: Shipping Address is not FormTemplate block anymore (this code is for backward compatibility)
		
		if ($form_tpl == self::TPL_SHIPPING_ADDRESS_SELECT)
		{
			return $this->loadTemplate($form_tpl . '_bypv');
		}
		
		$this->loaded_form_templates[] = $form_tpl;
		
		if ($this->isFormTemplateLoaded_byPV($form_tpl)) {
// 			$this->initializeFormTemplateJS_byPV($form_tpl);
			return $this->form_template_cache[$form_tpl];
		}

		$loadTemplate = FALSE;
		$IS_PHASE_CHECKOUT = ($this->checkout_task === 'checkout');
		
		if (in_array($form_tpl, [ self::TPL_CHANGE_SHOPPER, self::TPL_CHANGE_SHOPPER_GROUP ])) {
			$CART = $this->getCartData_byPV();
			$loadTemplate = ($CART->ALLOW_CHANGE_SHOPPER === TRUE);
		}
		elseif ($form_tpl == self::TPL_PRODUCT_LIST) {
			$loadTemplate = TRUE;
		}
		elseif ($form_tpl == self::TPL_ORDER_SUMMARY) {
			$loadTemplate = (plgSystemOPC_for_VM_byPV::getPluginParam('show_order_summary_in') === 'page');
		}
		elseif ($form_tpl == self::TPL_COUPON_CODE) {
			$loadTemplate = $IS_PHASE_CHECKOUT && VmConfig::get('coupons_enable')
				&& in_array(plgSystemOPC_for_VM_byPV::getPluginParam('show_coupon_code_in'), array('page', 'product_list_and_page'));
		}
		elseif ($form_tpl == self::TPL_SHIPMENTS) {
			$loadTemplate = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_' . $form_tpl)
				&& $IS_PHASE_CHECKOUT;
		}
		elseif ($form_tpl == self::TPL_PAYMENTS) {
			$CART_PRICES = $this->getCartPrices_byPV();
			
			$loadTemplate = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_' . $form_tpl)
				&& $IS_PHASE_CHECKOUT && $CART_PRICES['salesPrice'] != 0;
		}
		elseif ($form_tpl == self::TPL_CUSTOMER_TYPE_SELECT) {
			$loadTemplate = $IS_PHASE_CHECKOUT
				&& (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_customer_types_always')
				 || (count(VirtueMartCart_byPV::$CUSTOMER_TYPES) > 1 && !$this->isUserLogged_byPV())
				);
		}
		elseif ($form_tpl == self::TPL_COMMENT) {
			if (VM_VERSION < 3)
			{
				$loadTemplate = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_' . $form_tpl)
					&& ($IS_PHASE_CHECKOUT || !empty($this->cart->customer_comment));
			}
			else return NULL;
		}
		elseif ($form_tpl == self::TPL_ADVERTISEMENTS) {
			$ADVERTISEMENTS = $this->getAdvertisementsData_byPV();
			
			$loadTemplate = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_' . $form_tpl)
				&& $ADVERTISEMENTS->IS_ADVERTISEMENT;
		}
		elseif ($form_tpl == self::TPL_CART_FIELDS) {
			if (VM_VERSION == 3)
			{
				$CART_FIELDS = $this->getCartFieldsData_byPV();
				$loadTemplate = !empty($CART_FIELDS->GROUPS);
			}
			else return NULL;
		}
		elseif ($form_tpl == self::TPL_TOS) {
			if (VM_VERSION < 3)
			{
				$loadTemplate = $IS_PHASE_CHECKOUT && $this->isUserFieldAgreedRequired_byPV();
			}
			else return NULL;
		}
		elseif ($form_tpl == self::TPL_EXTERNAL_MODULES)
		{
			$external_modules_position = plgSystemOPC_for_VM_byPV::getPluginParam('external_modules_position');
			
			if (!empty($external_modules_position))
			{
				jimport('joomla.application.module.helper');
				$external_modules = JModuleHelper::getModules($external_modules_position);
			}
			
			$loadTemplate = !empty($external_modules_position) && !empty($external_modules);
			
			if ($loadTemplate)
			{
				$input = JFactory::getApplication()->input;

				if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_autorefresh_for_external_modules') && $input->getString('format') === 'json')
				{
					return NULL;
				}
			}
		}
		elseif (
			$form_tpl == self::TPL_SHIPPING_ADDRESS
			&&
			plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address') === FALSE
		) {
			$loadTemplate = FALSE;
		}
		elseif ($this->isUserLogged_byPV()) {
			if ($form_tpl == self::TPL_LOGIN) {
				$loadTemplate = $IS_PHASE_CHECKOUT;
			}
			else {
				$loadTemplate = in_array($form_tpl, array(
					self::TPL_BILLING_ADDRESS,
					self::TPL_SHIPPING_ADDRESS,
				));
			}
		}
		else {
			$cart_bypv = VirtueMartCart_byPV::getCart();
			$forms = array();
			
			switch ($cart_bypv->getCustomerType())
			{
				case VirtueMartCart_byPV::CT_LOGIN:
					if ($IS_PHASE_CHECKOUT) $forms = array(self::TPL_LOGIN);
					break;
					
				case VirtueMartCart_byPV::CT_REGISTRATION:
				case VirtueMartCart_byPV::CT_GUEST:
					$forms = array(self::TPL_BILLING_ADDRESS, self::TPL_SHIPPING_ADDRESS);
					break;
			}
			
			$loadTemplate = in_array($form_tpl, $forms);
		}
		
		if ($loadTemplate)
		{
			$html = $this->loadTemplate($form_tpl . '_bypv');
			$this->form_template_cache[$form_tpl] = $html;

			plgSystemOPC_for_VM_byPV::saveScriptsJS($form_tpl);
		}
		else
		{
			$html = '<span id="bypv_cart_' . $form_tpl . '" class="bypv_empty_cover"></span>';
		}
		
		return sprintf(self::BLOCK_COVER, $form_tpl, $html);
	}
	
	public function getLoadedFormTemplates_byPV()
	{
		return $this->loaded_form_templates;
	}
	
	public function isUserLogged_byPV()
	{
		return (JFactory::getUser()->guest != 1); // Different solution = JFactory::getUser()->id > 0
	}
	
	/*** Template Methods byPV ***/
	
	public function getCartPrices_byPV()
	{
		if (VM_VERSION < 3)
			return $this->cart->pricesUnformatted;
		else
			return $this->cart->cartPrices;
	}
	
	/***
	 * @deprecated since 1.14.0
	 */
	public function getFormChecksum_byPV($form_tpl = NULL, $json_encode = TRUE)
	{
		if ($json_encode === TRUE) {
			return '###FORM_CHECKSUM_BYPV###';
		}
		else {
			return ($form_tpl === NULL ? $this->bypv_form_checksum : $this->bypv_form_checksum[$form_tpl]);
		}
	}
	
	public function getCartData_byPV()
	{
		static $DATA = NULL;
		
		if (!empty($DATA)) return $DATA;
		
		$DATA = new stdClass();
		
		// Plugin Config
		
		$DATA->PLGCFG_ALLOW_EMPTY_CART = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_empty_cart');
		$DATA->PLGCFG_ALLOW_CONFIRMATION_PAGE = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_confirmation_page');
		$DATA->PLGCFG_SHOW_SELECTED_SHIPMENT = plgSystemOPC_for_VM_byPV::getPluginParam('show_selected_shipment');
		$DATA->PLGCFG_SHOW_SELECTED_PAYMENT = plgSystemOPC_for_VM_byPV::getPluginParam('show_selected_payment');
		
		// Virtuemart Config
		
		$DATA->VMCFG_SHOW_ORIGPRICE = VmConfig::get('checkout_show_origprice', 1);
		$DATA->VMCFG_SHOW_TAX = VmConfig::get('show_tax');
		$DATA->VMCFG_COUPONS_ENABLE = VmConfig::get('coupons_enable');
		$DATA->VMCFG_ONCHECKOUT_SHOW_IMAGES = VmConfig::get('oncheckout_show_images');
		$DATA->VMCFG_SHOW_LEGAL_INFO = VmConfig::get('oncheckout_show_legal_info', 1);
		$DATA->VMCFG_USE_FANCY = VmConfig::get('usefancy', 0);
		
		// Conditions
		
		$DATA->IS_PHASE_CHECKOUT = ($this->checkout_task === 'checkout');
		$DATA->IS_PHASE_CONFIRM = ($this->checkout_task === 'confirm');
		$DATA->IS_EMPTY = empty($this->cart->products);
		$DATA->IS_CONTINUE_LINK = !empty($this->continue_link);

		$DATA->DEFAULT_URL = JRoute::_(
			'index.php?option=com_virtuemart&view=cart'
			, $this->useXHTML, $this->useSSL
		);
		
		$DATA->CHECKOUT_URL = JRoute::_(
			'index.php?option=com_virtuemart&view=cart'
			. (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('use_unique_url_for_every_step') ? '&step=ordered' : '')
			, $this->useXHTML, $this->useSSL
		);
		
		$DATA->CONTINUE_LINK = $this->continue_link;
		$DATA->CONTINUE_LINK_HTML = $this->continue_link_html;
		$DATA->ORDER_LANGUAGE = $this->order_language;
		$DATA->CHECKOUT_TASK = $this->checkout_task;
		
		// Allow Change Shopper
		
		$DATA->ALLOW_CHANGE_SHOPPER = FALSE;
		
		if(VmConfig::get('oncheckout_change_shopper'))
		{
			if (VM_VERSION === 3)
			{
				$DATA->ALLOW_CHANGE_SHOPPER = vmAccess::manager('user');
			}
			else
			{
				$adminID = JFactory::getSession()->get('vmAdminID');
				
				$DATA->ALLOW_CHANGE_SHOPPER =
					JFactory::getUser()->authorise('core.admin', 'com_virtuemart')
					||
					JFactory::getUser($adminID)->authorise('core.admin', 'com_virtuemart')
				;
			}
			
			if ($DATA->ALLOW_CHANGE_SHOPPER)
			{
				if (!class_exists('VirtueMartModelUser')) require_once(VMPATH_ADMIN . '/models/user.php');
				
				if (VM_VERSION === 3)
				{
					$DATA->CURRENT_ADMIN = vmAccess::getBgManagerId();
					$DATA->CURRENT_USER = $this->cart->user->virtuemart_user_id;
					$superVendor = vmAccess::isSuperVendor($DATA->CURRENT_ADMIN);
				}
				else
				{
					$this->cart->user = VmModel::getModel('user');
					$DATA->CURRENT_ADMIN = JFactory::getSession()->get('vmAdminID');
					$DATA->CURRENT_USER = $this->cart->user->_data->virtuemart_user_id;
				}

				if (VM_VERSION === 3)
				{
					if ($superVendor)
					{
						$userModel = VmModel::getModel('user');
						$DATA->CHANGE_SHOPPER_LIST = $userModel->getSwitchUserList($superVendor, $DATA->CURRENT_ADMIN);
						
						$actualUser = $userModel->getCurrentUser();
						
						if (!class_exists('ShopFunctions'))	require(VMPATH_ADMIN . DS . 'helpers' . DS . 'shopfunctions.php');
						$DATA->CHANGE_SHOPPER_GROUP_LIST = ShopFunctions::renderShopperGroupList($actualUser->shopper_groups, TRUE, 'virtuemart_shoppergroup_id', 'COM_VIRTUEMART_DRDOWN_AVA2ALL');
					}
				}
				else
				{
					$db = JFactory::getDbo();
					$query = $db->getQuery(TRUE);
					
					$query
						->select(array(
							$db->quoteName('id'),
							$db->quoteName('name'),
							$db->quoteName('username')
						))
						->from($db->quoteName('#__users'))
						->order($db->quoteName('name'))
					;
					
					$DATA->CHANGE_SHOPPER_LIST = $db->setQuery($query)->loadObjectList();
					
					foreach($DATA->CHANGE_SHOPPER_LIST as $user)
					{
						$user->displayedName = $user->name . '&nbsp;&nbsp;( ' . $user->username . ' )';
					}
				}
				
				if (!$DATA->CHANGE_SHOPPER_LIST)
				{
					$DATA->ALLOW_CHANGE_SHOPPER = FALSE;
				}
			}
		}
		
		return $DATA;
	}
	
	public function getProductListData_byPV()
	{
		static $DATA = NULL;
		
		if (!empty($DATA)) return $DATA;
		
		$CART = $this->getCartData_byPV();
		$CART_PRICES = $this->getCartPrices_byPV();

		$DATA = new stdClass();
		
		/*** COLS ***/

		$DATA->PRODUCT_COLS = array();
		$DATA->PRICE_COLS = array();
		$DATA->ACTION_COLS = array();
		
		for ($i = 1; $i <= 9; ++$i)
		{
			$col_id = plgSystemOPC_for_VM_byPV::getPluginParam('product_list_col_' . $i);
			
			if (!empty($col_id) && $col_id !== 'none')
			{
				if (strpos($col_id, '::') === FALSE) $col_type = NULL;
				else list($col_id, $col_type) = explode('::', $col_id);

				if ($CART->IS_PHASE_CONFIRM && $col_id == 'DROP') continue;
				
				$COL = new stdClass();
				$COL->ID = $col_id;
				
				switch ($col_id)
				{
					case 'NAME':
					case 'NAME_WITHOUT_LINK':
						$COL->ID = 'NAME';
						$COL->WITH_PRODUCT_LINK = ($col_id === 'NAME'); 
						break;

					case 'PRICE_EXCL_TAX':
					case 'PRICE_INCL_TAX':
					case 'TOTAL_EXCL_TAX':
					case 'TOTAL_INCL_TAX':
					case 'TAX':
						$COL->SHOW_ORIGINAL_AND_DISCOUNTED = ($col_type == 'ORIGINAL_AND_DISCOUNTED');
						break;

					case 'QUANTITY':
						$COL->SHOW_QUANTITY_CONTROLS = ($col_type == 'EDIT' || $col_type == 'EDIT_DROP') && $CART->IS_PHASE_CHECKOUT;
						$COL->SHOW_UPDATE_BUTTON = !plgSystemOPC_for_VM_byPV::isPluginParamEnabled('update_product_quantity_promptly');
						$COL->SHOW_DROP_BUTTON = ($col_type == 'EDIT_DROP');
						break;
				}
				
				if ($i < 5)
					$DATA->PRODUCT_COLS[$COL->ID] = $COL;
				elseif ($i < 9)
					$DATA->PRICE_COLS[$COL->ID] = $COL;
				else
					$DATA->ACTION_COLS[$COL->ID] = $COL;
			}
		}

		/*** PRODUCTS ***/
		
		$DATA->PRODUCTS = array();
		
		foreach ($this->cart->products as $product_id => $product)
		{
			$PRODUCT = new stdClass();
			
			// Image
			
			if (VM_VERSION < 3)
			{
				$PRODUCT->IS_IMAGE = !empty($product->virtuemart_media_id) && !empty($product->image);
				$PRODUCT->SHOW_IMAGE = $CART->VMCFG_ONCHECKOUT_SHOW_IMAGES && $PRODUCT->IS_IMAGE;
				$PRODUCT->IMAGE_HTML = ($PRODUCT->SHOW_IMAGE ? $product->image->displayMediaThumb ('', FALSE) : '');
			}
			else
			{
				$PRODUCT->IS_IMAGE = !empty($product->images[0]);
				$PRODUCT->SHOW_IMAGE = $CART->VMCFG_ONCHECKOUT_SHOW_IMAGES && $PRODUCT->IS_IMAGE;
				$PRODUCT->IMAGE_HTML = ($PRODUCT->SHOW_IMAGE ? $product->images[0]->displayMediaThumb('', FALSE) : '');
			}
				
			// Atributes

			$PRODUCT->NAME = $product->product_name;
			$PRODUCT->LINK_NAME_HTML = JHTML::link($product->url, $product->product_name);
			$PRODUCT->SKU = $product->product_sku;
			$PRODUCT->QUANTITY = $product->quantity;
			$PRODUCT->STEP_ORDER_LEVEL = $this->getProductStepOrderLevel_byPV($product);

			if (VM_VERSION < 3)
			{
				$PRODUCT_PRICES = $CART_PRICES[$product_id];
				$PRODUCT->CUSTOM_FIELDS_HTML = $product->customfields;
			}
			else
			{
				$PRODUCT_PRICES = $product->prices;
				$PRODUCT->CUSTOM_FIELDS_HTML = $this->customfieldsModel->CustomsFieldCartDisplay($product);				
			}
			
			// Prices

			$PRODUCT->IS_DISCOUNTED = abs($PRODUCT_PRICES['discountAmount']) > 0;
						
			$PRODUCT->PRICE_EXCL_TAX_ORIGINAL = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'basePriceVariant');
			$PRODUCT->PRICE_EXCL_TAX = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'discountedPriceWithoutTax');
						
			$PRODUCT->PRICE_INCL_TAX_ORIGINAL = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'basePriceWithTax');
			$PRODUCT->PRICE_INCL_TAX = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'salesPrice');
			
			$PRODUCT->DISCOUNT = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'discountAmount', NULL, $PRODUCT->QUANTITY);
			
			$PRODUCT->TOTAL_EXCL_TAX_ORIGINAL = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'basePriceVariant', NULL, $PRODUCT->QUANTITY);
			$PRODUCT->TOTAL_EXCL_TAX = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'discountedPriceWithoutTax', NULL, $PRODUCT->QUANTITY);
			
			$PRODUCT->TAX_ORIGINAL = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'taxAmountOriginal', 'taxAmount', $PRODUCT->QUANTITY);
			$PRODUCT->TAX = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'taxAmount', NULL, $PRODUCT->QUANTITY);

			if (!empty($PRODUCT_PRICES['basePriceWithTax']) && $PRODUCT_PRICES['basePriceWithTax'] != $PRODUCT_PRICES['salesPrice'])
			{
				$PRODUCT->TOTAL_INCL_TAX_ORIGINAL = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'basePriceWithTax', NULL, $PRODUCT->QUANTITY);
			}
			elseif (empty($PRODUCT_PRICES['basePriceWithTax']) && $PRODUCT_PRICES['basePriceVariant'] != $PRODUCT_PRICES['salesPrice'])
			{
				$PRODUCT->TOTAL_INCL_TAX_ORIGINAL = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'basePriceVariant', NULL, $PRODUCT->QUANTITY);
			}
			
			$PRODUCT->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($PRODUCT_PRICES, 'salesPrice', NULL, $PRODUCT->QUANTITY);
			
			$PRODUCT->INPUT_AUTOCOMPLETE = 'off';
			
			$DATA->PRODUCTS[$product_id] = $PRODUCT;
		}

		// Subtotal
		
		$DATA->SUBTOTAL = new stdClass();
		$DATA->SUBTOTAL->DISCOUNT = $this->getFormattedPrice_byPV($CART_PRICES, 'discountAmount');
		$DATA->SUBTOTAL->TOTAL_EXCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'discountedPriceWithoutTax');
		$DATA->SUBTOTAL->TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'taxAmount');
		$DATA->SUBTOTAL->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'salesPrice');

		// Order Summary
		
		$DATA->SHOW_ORDER_SUMMARY = (plgSystemOPC_for_VM_byPV::getPluginParam('show_order_summary_in') === 'product_list');
		
		// Coupon code

		if ($CART->VMCFG_COUPONS_ENABLE)
		{
			$SHOW_COUPON_CODE_INPUT = $CART->IS_PHASE_CHECKOUT && in_array(plgSystemOPC_for_VM_byPV::getPluginParam('show_coupon_code_in'), array('product_list', 'product_list_and_page'));
			
			$DATA->COUPON_CODE = $this->getCouponCodeData_byPV(!$SHOW_COUPON_CODE_INPUT);
			$DATA->COUPON_CODE->SHOW_COUPON_CODE_INPUT = $SHOW_COUPON_CODE_INPUT;
		}
		else $DATA->COUPON_CODE = NULL;

		// Tax rules bill

		$DATA->TAX_RULES = array();
		
		$TAX_RULES = array(
			'DBTaxRulesBill' => 'db_tax_rule',
			'taxRulesBill' => 'tax_rule',
			'DATaxRulesBill' => 'da_tax_rule',
		);
		
		foreach ($TAX_RULES as $key => $class)
		{
			$DATA->TAX_RULES[$class] = array();
			
			foreach ($this->cart->cartData[$key] as $rule)
			{
				$RULE = new stdClass();
				
				$RULE->NAME = $rule['calc_name'];
				$RULE->TOTAL_EXCL_TAX = '';
				$RULE->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, $rule['virtuemart_calc_id'] . 'Diff', 'salesPrice');

				if ($key == 'taxRulesBill')
				{
					$RULE->TAX = $this->getFormattedPrice_byPV($CART_PRICES, $rule['virtuemart_calc_id'] . 'Diff', 'taxAmount');
					$RULE->DISCOUNT = '';
				}
				else
				{
					$RULE->TAX = '';
					$RULE->DISCOUNT = $this->getFormattedPrice_byPV($CART_PRICES, $rule['virtuemart_calc_id'] . 'Diff', 'discountAmount');
				}
				
				$DATA->TAX_RULES[$class][] = $RULE;
			}
		}

		// Shipment

		if ($CART->PLGCFG_SHOW_SELECTED_SHIPMENT == '1' || $CART->PLGCFG_SHOW_SELECTED_SHIPMENT == 'ONLY_WITH_FEE' && !empty($CART_PRICES['salesPriceShipment']))
		{
			$DATA->SHIPMENT = new stdClass();
			$DATA->SHIPMENT->NAME = $this->cart->cartData['shipmentName'];
			$DATA->SHIPMENT->TOTAL_EXCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'shipmentValue', 'discountedPriceWithoutTax');
			$DATA->SHIPMENT->TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'shipmentTax', 'taxAmount');
			$DATA->SHIPMENT->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'salesPriceShipment', 'salesPrice');
			$DATA->SHIPMENT->DISCOUNT = (
				$CART_PRICES['salesPriceShipment'] < 0
					? $this->getFormattedPrice_byPV($CART_PRICES, 'salesPriceShipment', 'discountAmount')
					: ''
			);
		}
		else $DATA->SHIPMENT = NULL;

		// Payment

		if ($CART->PLGCFG_SHOW_SELECTED_PAYMENT == '1' || $CART->PLGCFG_SHOW_SELECTED_PAYMENT == 'ONLY_WITH_FEE' && !empty($CART_PRICES['salesPricePayment']))
		{
			$DATA->PAYMENT = new stdClass();
			$DATA->PAYMENT->NAME = $this->cart->cartData['paymentName'];
			$DATA->PAYMENT->TOTAL_EXCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'paymentValue', 'discountedPriceWithoutTax');
			$DATA->PAYMENT->TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'paymentTax', 'taxAmount');
			$DATA->PAYMENT->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'salesPricePayment', 'salesPrice');
			$DATA->PAYMENT->DISCOUNT = (
				$CART_PRICES['salesPricePayment'] < 0
					? $this->getFormattedPrice_byPV($CART_PRICES, 'salesPricePayment', 'discountAmount')
					: ''
			);
		}
		else $DATA->PAYMENT = NULL;
		
		// Total
		
		$DATA->TOTAL = new stdClass();
		$DATA->TOTAL->DISCOUNT = $this->getFormattedPrice_byPV($CART_PRICES, 'billDiscountAmount', 'discountAmount');
		$DATA->TOTAL->TOTAL_EXCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES['billTotal'] - $CART_PRICES['billTaxAmount'], 'billTotalExclTax', 'discountedPriceWithoutTax');
		$DATA->TOTAL->TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'billTaxAmount', 'taxAmount');
		$DATA->TOTAL->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'billTotal', 'salesPrice');
		
		// Total in payment currency
		
		if ($this->totalInPaymentCurrency) {
			$DATA->TOTAL_CURRENCY = new stdClass();
			$DATA->TOTAL_CURRENCY->TOTAL_EXCL_TAX = '';
			$DATA->TOTAL_CURRENCY->TAX = '';
			$DATA->TOTAL_CURRENCY->TOTAL_INCL_TAX = $this->totalInPaymentCurrency;
			$DATA->TOTAL_CURRENCY->DISCOUNT = '';
		}
		else
		{
			$DATA->TOTAL_CURRENCY = NULL;
		}
		
		return $DATA;
	}

	public function getCouponCodeData_byPV($show_no_enter_message = TRUE)
	{
		$CART = $this->getCartData_byPV();
		$CART_PRICES = $this->getCartPrices_byPV();

		$DATA = new stdClass();
		
		if ($show_no_enter_message === TRUE) $DATA->NAME = JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_NO_COUPON_CODE_ENTERED');
		else $DATA->NAME = '';
		
		$DATA->ENTERED = FALSE;
		$DATA->DISCOUNT = '';
		$DATA->TOTAL_EXCL_TAX = '';
		$DATA->TAX = '';
		$DATA->TOTAL_INCL_TAX = '';
			
		if (!empty($this->cart->cartData['couponCode']))
		{
			$DATA->ENTERED = TRUE;
			$DATA->NAME = $this->cart->cartData['couponCode'];
		
			if (!empty($this->cart->cartData['couponDescr']))
			{
				$DATA->NAME .= ' (' . $this->cart->cartData['couponDescr'] . ')';
			}
			
			$DATA->TOTAL_EXCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES['salesPriceCoupon'] - $CART_PRICES['couponTax'], 'billTaxAmount', 'discountedPriceWithoutTax');
			$DATA->TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'couponTax', 'taxAmount');
			$DATA->TOTAL_INCL_TAX = $this->getFormattedPrice_byPV($CART_PRICES, 'salesPriceCoupon', 'salesPrice');
		}

		$DATA->PLACEHOLDER_TEXT = $this->coupon_text;
		
		// HTML5 Autocomplete
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_autocompleting_forms'))
		{
			$DATA->INPUT_AUTOCOMPLETE = 'section-checkout coupon-code';
		}
		else
		{
			$DATA->INPUT_AUTOCOMPLETE = 'off';
		}
		
		return $DATA;
	}
	
	public function getProductStepOrderLevel_byPV($product)
	{
		$step_order_level = (int) $product->step_order_level;
		if ($step_order_level < 1) $step_order_level = 1;
	
		$min_order_level = (int) $product->min_order_level;
		if ($min_order_level < 1) $min_order_level = 1;
		if ($min_order_level % $step_order_level > 0) $min_order_level -= $min_order_level % $step_order_level;
		if ($min_order_level < $step_order_level) $min_order_level = $step_order_level;
	
		$max_order_level = (int) $product->max_order_level;
		if ($max_order_level % $step_order_level > 0) $max_order_level -= $max_order_level % $step_order_level;
		if ($max_order_level < 1 || $max_order_level < $min_order_level) $max_order_level = 0; // Unlimited
			
		return $min_order_level . ':' . $step_order_level . ':' . $max_order_level;
	}
	
	public function getShipmentsData_byPV()
	{
		static $DATA = NULL;
		
		if (!empty($DATA)) return $DATA;

		$DATA = new stdClass();
			
		$DATA->IS_AUTOMATIC_SELECTED = $this->cart->automaticSelectedShipment;
		$DATA->IS_FOUND_METHOD = $this->found_shipment_method;
		
		$DATA->NAME = $this->cart->cartData['shipmentName'];
		$DATA->NOT_FOUND_TEXT = $this->shipment_not_found_text;
		$DATA->INFO_HTML = JText::_(plgSystemOPC_for_VM_byPV::getPluginParam('shipment_info'));

		$DATA->OPTIONS = $this->getMethodsOptions_byPV($this->shipments_shipment_rates);
		
		return $DATA;
	}
	
	public function getPaymentsData_byPV()
	{
		static $DATA = NULL;
		
		if (!empty($DATA)) return $DATA;

		$CART_PRICES = $this->getCartPrices_byPV();
		
		$DATA = new stdClass();

		$DATA->IS_ZERO_SALES_PRICE = ($CART_PRICES['salesPrice'] == 0);
		$DATA->IS_AUTOMATIC_SELECTED = $this->cart->automaticSelectedPayment;
		$DATA->IS_FOUND_METHOD = $this->found_payment_method;

		$DATA->NAME = $this->cart->cartData['paymentName'];
		$DATA->NOT_FOUND_TEXT = $this->payment_not_found_text;
		$DATA->INFO_HTML = JText::_(plgSystemOPC_for_VM_byPV::getPluginParam('payment_info'));

		$DATA->OPTIONS = $this->getMethodsOptions_byPV($this->paymentplugins_payments);
		
		return $DATA;
	}
	
	private function getMethodsOptions_byPV($plugins_methods)
	{
		$OPTIONS = array();
		
		if (is_array($plugins_methods)) foreach ($plugins_methods as $plugin_methods)
		{
			$method_content = array();
				
			if (is_array($plugin_methods)) foreach ($plugin_methods as $method_html)
			{
				$method_content[] = $method_html;
		
				// ID is in the first INPUT
				preg_match('/value="(\d+)"/', $method_html, $matches);
		
				if (!empty($matches[1]))
				{
					$OPTION = new stdClass();
					$OPTION->ID = (int) $matches[1];
					$OPTION->HTML = implode('', $method_content);
						
					$OPTIONS[$OPTION->ID] = $OPTION;
						
					$method_content = array();
				}
			}
		}
		
		return $OPTIONS;
	}
	
	public function getLoginData_byPV()
	{
		JFactory::getLanguage()->load('com_users', JPATH_SITE);
		
		$DATA = new stdClass();
		
		$DATA->IS_REMEMBER_ALLOWED = JPluginHelper::isEnabled('system', 'remember');
		$DATA->IS_USER_LOGGED = $this->isUserLogged_byPV();

		$DATA->LOGIN_RESET_URL = JRoute::_('index.php?option=com_users&view=reset');
		$DATA->LOGIN_REMIND_URL = JRoute::_('index.php?option=com_users&view=remind');
		
		$DATA->USER_NAME = JFactory::getUser()->get('name');
		
		// HTML5 Autocomplete
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_autocompleting_forms'))
		{
			$DATA->INPUT_USERNAME_AUTOCOMPLETE = 'section-login username';
			$DATA->INPUT_PASSWORD_AUTOCOMPLETE = 'section-login current-password';
		}
		else
		{
			$DATA->INPUT_USERNAME_AUTOCOMPLETE = 'off';
			$DATA->INPUT_PASSWORD_AUTOCOMPLETE = 'off';
		}

		return $DATA;
	}
	
	public function getCustomerData_byPV()
	{
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$DATA = new stdClass();
		
		$DATA->SELECTED_TYPE = $cart_bypv->getCustomerType();
		$DATA->TYPES = array();
		
		foreach (VirtueMartCart_byPV::$CUSTOMER_TYPES as $type_id => $type) {
			$TYPE = new stdClass();
			
			$TYPE->NAME = $type['name'];
			$TYPE->DESCRIPTION = $type['description'];
			$TYPE->ALLOWED = !$this->isUserLogged_byPV() || $type_id == VirtueMartCart_byPV::CT_LOGIN;
			
			$DATA->TYPES[$type_id] = $TYPE;
		}

		return $DATA;
	}
	
	public function getBillToData_byPV()
	{
		$DATA = new stdClass();
		
		$DATA->GROUPS = $this->getGroupedUserFields_byPV(VirtueMartCart_byPV::UFT_BILLING_ADDRESS);
		
		return $DATA;
	}
	
	public function getShipToData_byPV()
	{
		$DATA = new stdClass();
		$DATA->NOT_NEEDED = FALSE;
		
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		if ($cart_bypv->getShipment() !== NULL)
		{
			$cfgHideShippingAddressForSelectedShipments = plgSystemOPC_for_VM_byPV::getPluginParam('hide_shipping_address_for_selected_shipments');

			if (in_array($cart_bypv->getShipment(), $cfgHideShippingAddressForSelectedShipments))
			{
				$DATA->NOT_NEEDED = TRUE;
			}
		}

		if ($DATA->NOT_NEEDED == FALSE)
		{
			$DATA->GROUPS = $this->getGroupedUserFields_byPV(VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS);
		}
		
		return $DATA;
	}
	
	public function getCartFieldsData_byPV()
	{
		$DATA = new stdClass();
		
		$DATA->GROUPS = $this->getGroupedUserFields_byPV(VirtueMartCart_byPV::UFT_CART);
		
		return $DATA;
	}
	
	public function getGroupedUserFields_byPV($user_field_type)
	{
		$cart_bypv = VirtueMartCart_byPV::getCart();
		$lang = JFactory::getLanguage();
	
		// Load User Fields
		$userFields = $cart_bypv->getUserFields($user_field_type);
		if (empty($userFields)) return array();
	
		// Set User Data
		switch ($user_field_type)
		{
			case VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS:
				if ($cart_bypv->getShipTo() === VirtueMartCart_byPV::ST_SAME_AS_BILL_TO) {
					return array();
				}
				break;
		}
		
		$userData = $cart_bypv->getUserFieldsData($user_field_type);
	
		/* @var $userFieldsModel VirtueMartModelUserfields */
		$userFieldsModel = VmModel::getModel('userfields');
	
		// Load language file for translating titles in getUserFieldsFilled() method
		vmLanguage::loadJLang('com_virtuemart_shoppers', TRUE);
	
		// Fill User Data
		$userFieldsFilled = $userFieldsModel->getUserFieldsFilled(
			$userFields,
			$userData,
			'bypv_' . $user_field_type . '_'
		);
		
		$PLGCFG_HIDE_SHOPPER_FIELDS = plgSystemOPC_for_VM_byPV::getPluginParam('hide_shopper_fields');
		$PLGCFG_SHOPPER_FIELDS_DISPLAY_CONDITIONS = plgSystemOPC_for_VM_byPV::getPluginParam('shopper_fields_display_conditions');
		
		$conditionsForShowField = array();
			
		foreach ($PLGCFG_SHOPPER_FIELDS_DISPLAY_CONDITIONS as $condition)
		{
			foreach ($condition->SHOW as $field)
			{
				$CONDITION = new stdClass();
				$CONDITION->FIELD = $condition->FIELD;
				$CONDITION->VALUE = $condition->VALUE;
					
				$conditionsForShowField[$field] = $CONDITION;
			}
		}
		
		if (!is_array($userData)) $userData = array();
		
		// Create groups of user fields
		$GROUP = new stdClass();
		$GROUP->ID = 'none';
		$GROUP->TITLE = '';
		$GROUP->FIELDS = array();
		$GROUP->SHOW = TRUE;
		
		$GROUPS = array($GROUP->ID => $GROUP);
		
		foreach ($userFields as $field)
		{
			$item_id = $field->name;
			$item = $userFieldsFilled['fields'][$item_id];
			$item['formcode_preview'] = $item['value'];
			
			// VM Fix: We rewrite public values (texts) in Radio, Select, etc. back to internal values. 
			if (isset($userData[$item_id])) $item['value'] = $userData[$item_id];
			
			$fieldCondition = (isset($conditionsForShowField[$item_id]) ? $conditionsForShowField[$item_id] : NULL);
			
			if ($item['type'] === 'delimiter')
			{
				if (empty($GROUP->FIELDS)) unset($GROUPS[$GROUP->ID]);

				$GROUP = new stdClass();
				$GROUP->ID = $item_id;
				$GROUP->TITLE = $item['title'];
				$GROUP->FIELDS = array();
				$GROUP->SHOW = (
					empty($fieldCondition)
						|| array_key_exists($fieldCondition->FIELD, $userData) && in_array($userData[$fieldCondition->FIELD], $fieldCondition->VALUE) 
				);

				$GROUPS[$GROUP->ID] = $GROUP;
			}
			elseif (!in_array($item_id, $PLGCFG_HIDE_SHOPPER_FIELDS))
			{
				$FIELD = new stdClass();
				
				foreach ($item as $key => $value)
				{
					$key = strtoupper($key);
					$FIELD->$key = $value;
				}
				
				switch ($FIELD->TYPE)
				{
					case 'checkbox':
						$FIELD->FORMCODE_PREVIEW = JText::_($FIELD->VALUE == 1 ? 'COM_VIRTUEMART_YES' : 'COM_VIRTUEMART_NO');
						break;
							
					case 'password':
						$FIELD->FORMCODE_PREVIEW = (empty($FIELD->VALUE) ? '' : '**********');
						break;

					case 'custom':
						if (VM_VERSION == 3)
						{
							$FIELD->FORMCODE = str_replace(
								'bypv_' . $user_field_type . '_' . 'bypv_' . $user_field_type . '_',
								'bypv_' . $user_field_type . '_',
								$FIELD->FORMCODE
							);
							
							$langKey = sprintf(
								'PLG_SYSTEM_OPC_FOR_VM_BYPV_USERFIELD_%s_VALUE_%s'
								, $item_id, $FIELD->VALUE
							);
							
							if ($lang->hasKey($langKey))
							{
								$FIELD->FORMCODE_PREVIEW = JText::_($langKey);
							}
						}
						break;
						
					case 'multicheckbox':
					case 'multiselect':
					case 'select':
					case 'radio':
						if ($FIELD->VALUE == $FIELD->FORMCODE_PREVIEW)
						{
							$db = JFactory::getDbo();
							$db->setQuery(
								'SELECT `fieldtitle` FROM `#__virtuemart_userfield_values` ' .
								'WHERE `virtuemart_userfield_id` = ' . $db->quote($field->virtuemart_userfield_id) .' AND `fieldvalue` = ' . $db->quote($FIELD->VALUE)
							);
							$dbValue = $db->loadResult();
							
							if ($dbValue !== NULL)
							{
								$FIELD->FORMCODE_PREVIEW = JText::_($dbValue);
							}
						}
						break;
				}

				switch ($item_id)
				{
					case 'tos':
						// Backward compatibility
						if ($FIELD->FORMCODE_PREVIEW === $FIELD->VALUE)
						{
							$FIELD->FORMCODE_PREVIEW = JText::_('COM_VIRTUEMART_USER_FORM_BILLTO_TOS_' . ($FIELD->VALUE ? 'YES' : 'NO'));
						}
						break;
				}
				
				if (!isset($FIELD->DESCRIPTION)) $FIELD->DESCRIPTION = NULL;
				
				$FIELD->SHOW = (
					empty($fieldCondition)
						|| array_key_exists($fieldCondition->FIELD, $userData) && in_array($userData[$fieldCondition->FIELD], $fieldCondition->VALUE)
				);
				
				// HTML5 Autocomplete
				
				if (in_array($FIELD->TYPE, array('text', 'textarea', 'emailaddress', 'password', 'select')))
				{
					if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_autocompleting_forms')
						// Fix for Chrome
						&& !in_array($item_id, array('username', 'password', 'password2')))
					{
// 						if (in_array($item_id, array('username', 'password', 'password2')))
// 						{
// 							$autoCompleteAttribute = 'section-registration';
// 						}
// 						else
// 						{
							$autoCompleteAttribute = 'section-' . $user_field_type;
							
							if ($user_field_type === VirtueMartCart_byPV::UFT_BILLING_ADDRESS)
								$autoCompleteAttribute .= ' billing';
							elseif ($user_field_type === VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS)
								$autoCompleteAttribute .= ' shipping';
// 						}
						
						$autoCompleteAttribute .= ' ';
						
						if (isset(self::$AUTOCOMPLETE_FIELD_NAMES[$item_id]))
						{
							$autoCompleteAttribute .= self::$AUTOCOMPLETE_FIELD_NAMES[$item_id];
						}
						else
						{
							$autoCompleteAttribute .= 'field-' . $item_id;
						}
					}
					else
					{
						$autoCompleteAttribute = 'off';
					}
					
					if ($FIELD->TYPE === 'select')
					{
						$FIELD->FORMCODE = str_replace('<select', '<select autocomplete="' . $autoCompleteAttribute . '" ', $FIELD->FORMCODE);
					}
					else
					{
						$FIELD->FORMCODE = str_replace('<input', '<input autocomplete="' . $autoCompleteAttribute . '" ', $FIELD->FORMCODE);
					}
				}
				
				$GROUP->FIELDS[$item_id] = $FIELD;
			}
		}

		return $GROUPS;
	}
	
	public function getShipToSelectData_byPV()
	{
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$DATA = new stdClass();
		
		$DATA->SELECTED_ADDRESS = $cart_bypv->getShipTo();
		$DATA->PLGCFG_ALLOW_DELETE_SHIPPING_ADDRESS = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_delete_shipping_address');
		
		/* @var $userModel VirtueMartModelUser */
		$userModel = VmModel::getModel('user');
	
		// Load BT address, because the user might want to use the information about it in template.
		$addressesBT = $userModel->getUserAddressList($userModel->getId(), 'BT');
		if (empty($addressesBT)) $addressesBT = array(0 => new stdClass());
	
		$addressesBT[0]->virtuemart_userinfo_id = VirtueMartCart_byPV::ST_SAME_AS_BILL_TO;
		$addressesBT[0]->address_type_name = JText::_('COM_VIRTUEMART_ACC_BILL_DEF');
	
		$addressesST = $userModel->getUserAddressList($userModel->getId(), 'ST');
	
		$new_address = new stdClass();
		$new_address->virtuemart_userinfo_id = VirtueMartCart_byPV::ST_NEW_ADDRESS;
		$new_address->address_type_name = 'PLG_SYSTEM_OPC_FOR_VM_BYPV_ADD_SHIPTO_LABEL';
	
		$addressesST[] = $new_address;
	
		$DATA->ADDRESSES = array();
	
		foreach (array_merge($addressesBT, $addressesST) as $address) {
			$ADDRESS = new stdClass();
			$ADDRESS->NAME = $address->address_type_name;
			
			$DATA->ADDRESSES[$address->virtuemart_userinfo_id] =  $ADDRESS;
		}
	
		return $DATA;
	}
	
	public function isUserFieldAgreedRequired_byPV()
	{
		if (!class_exists ('VirtueMartModelUserfields')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'userfields.php');
		}
	
		$userFieldsModel = VmModel::getModel('userfields');
		return ($userFieldsModel->getIfRequired('agreed') == 1);
	}
	
	public function isTermOfServiceAccepted_byPV()
	{
		if (!VmConfig::get('agree_to_tos_onorder', 0)) {
			$userModel = VmModel::getModel('user');
			$user = $userModel->getCurrentUser();
				
			foreach ($user->userInfo as $address) {
				if ($address->address_type === 'BT' && $address->agreed == 1) {
					return TRUE;
				}
			}
		}
	
		return FALSE;
	}
	
	public function printHeader_byPV($level, $text, $custom_text = NULL)
	{
		if (!is_numeric($level)) $level = 1;

		$offset = plgSystemOPC_for_VM_byPV::getPluginParam('header_level_offset');
		if ($offset > 0) $level += $offset;
		
		$text = JText::_($text);
		if ($custom_text !== NULL) $text .= $custom_text;
		
		if (!empty($text))
		{
			echo '<h' . $level . ' class="cart_block_title">' . $text . '</h' . $level . '>';
		}
	}
	
	public function getAdvertisementsData_byPV()
	{
		$DATA = new stdClass();
		
		$DATA->IS_ADVERTISEMENT = !empty($this->checkoutAdvertise);

		if ($DATA->IS_ADVERTISEMENT) {
			$DATA->IS_ADVERTISEMENT = FALSE;
			
			foreach ($this->checkoutAdvertise as $advertise) if (!empty($advertise)) {
				$DATA->IS_ADVERTISEMENT = TRUE;
				break;
			}
		}
		
		$DATA->ADVERTISEMENTS_HTML = $this->checkoutAdvertise;
		
		return $DATA;
	}
	
	public function getTermOfServiceData_byPV()
	{
		$DATA = new stdClass();
		
		$DATA->IS_USER_FIELD_AGREED_REQUIRED = $this->isUserFieldAgreedRequired_byPV();
		$DATA->IS_ACCEPTED = $this->isTermOfServiceAccepted_byPV();
		
		$DATA->CONTENT_HTML = $this->cart->vendor->vendor_terms_of_service;
		$DATA->URL = JRoute::_('index.php?option=com_virtuemart&view=vendor&layout=tos&virtuemart_vendor_id=1', FALSE);
		
		if (VM_VERSION < 3)
		{
			$DATA->INPUT_NAME = 'tosAccepted';
		}
		else
		{
			$DATA->INPUT_NAME = 'tos';
		}
		
		return $DATA;
	}
	
	public function getCommentData_byPV()
	{
		$DATA = new stdClass();
		
		$DATA->IS_ENTERED = !empty($this->cart->customer_comment);
		$DATA->TEXT = $this->cart->customer_comment;
		
		return $DATA;
	}
	
	public function getButtonsData_byPV()
	{
		$CART = $this->getCartData_byPV();
		
		$DATA = new stdClass();
		
		$DATA->SHOW_CHECKOUT_BUTTON = ($CART->PLGCFG_ALLOW_CONFIRMATION_PAGE && $CART->IS_PHASE_CHECKOUT);
		$DATA->SHOW_BACK_TO_CHECKOUT_BUTTON = ($CART->PLGCFG_ALLOW_CONFIRMATION_PAGE && $CART->IS_PHASE_CONFIRM);
		$DATA->SHOW_CONFIRM_BUTTON = (!$CART->PLGCFG_ALLOW_CONFIRMATION_PAGE || $CART->IS_PHASE_CONFIRM);
		
		return $DATA;
	}

	public function getExternalModulesData_byPV()
	{
		$DATA = new stdClass();
		
		$DATA->MODULES = array();
		
		$external_modules_position = plgSystemOPC_for_VM_byPV::getPluginParam('external_modules_position');
		
		if (!empty($external_modules_position))
		{
			jimport('joomla.application.module.helper');
			$modules = JModuleHelper::getModules($external_modules_position);

			$external_modules_chrome_style = plgSystemOPC_for_VM_byPV::getPluginParam('external_modules_chrome_style');
			
			foreach ($modules as $module)
			{
				$MODULE = new stdClass();
				
				$attribs = array(
					'headerLevel' => 2
				);
				
				if (!empty($external_modules_chrome_style))
				{
					$attribs['style'] = $external_modules_chrome_style;
				}
				
				$MODULE->HTML = JModuleHelper::renderModule($module, $attribs);
				
				$DATA->MODULES[] = $MODULE;
			}
		}
		 
		return $DATA;
	}
}
