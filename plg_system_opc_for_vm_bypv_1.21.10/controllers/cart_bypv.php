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
 
// import Joomla controller library
jimport('joomla.application.component.controller');
 
if (!class_exists('VirtueMartControllerCart')) require(JPATH_VM_SITE . DS . 'controllers' . DS . 'cart.php');
require(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'views' . DS . 'cart_bypv' . DS . 'view.html.php');

class VirtueMartControllerCart_byPV extends VirtueMartControllerCart
{
	/*** OVERRIDE ***/
	
	public function __construct($config = array())
	{
		$config['base_path'] = OPC_FOR_VM_BYPV_PLUGIN_PATH;
		parent::__construct($config);

		// HACK: Because VirtueMartControllerCart::__construct() is not same as JController::__construct() 
		$this->basePath = OPC_FOR_VM_BYPV_PLUGIN_PATH;
		$this->setPath('view', $this->basePath . '/views');
		
		// Prevention sending order again
		
		$task = JRequest::getCmd('task');
		
		if (JRequest::getMethod() === 'POST' && in_array($task, array('checkout', 'confirm')))
		{
			$cart_bypv = VirtueMartCart_byPV::getCart();
			
			if (!$cart_bypv->CheckoutDisplayed())
			{
				$this->redirectToCart_byPV();
			}
		}
		
		// Prevention displaying of the confirmation page unless permitted
		
		$cart = VirtueMartCart::getCart();
		if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_confirmation_page') && $cart->getDataValidated() !== FALSE)
		{
			$cart->setDataValidation(FALSE);
		}
		
		$this->checkSefCheckoutURL_byPV();
	}
	
	public function display($cachable = false, $urlparams = false)
	{
		// We don't want to run triggers on shipment/payment plugins here
		if (VM_VERSION < 3)
		{
			return parent::display($cachable, $urlparams);
		}
		
		// ELSE
		
		$document = JFactory::getDocument();
		$viewType = $document->getType();
		$viewName = vRequest::getCmd('view', $this->default_view);
		$viewLayout = vRequest::getCmd('layout', 'default');
	
		$view = $this->getView($viewName, $viewType, '', array('layout' => $viewLayout));
	
		$view->document = $document;
	
		$cart = VirtueMartCart::getCart();
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		// Shipment
		
		if ($cart_bypv->getShipment() == NULL)
		{
			$cfgAutomaticShipmentId = VmConfig::get('set_automatic_shipment', -1);
			
			if ($cfgAutomaticShipmentId > -1)
			{
				$methods = VmModel::getModel('Shipmentmethod')->getShipments(TRUE);
				
				if (!empty($methods)) foreach ($methods as $method)
				{
					if ($cfgAutomaticShipmentId == 0 || $cfgAutomaticShipmentId == $method->virtuemart_shipmentmethod_id)
					{
						$cart_bypv->setShipment($method->virtuemart_shipmentmethod_id, FALSE);
						break;
					}
				}
			}
		}
		
		// Payment
		
		if ($cart_bypv->getPayment() == NULL)
		{
			$cfgAutomaticPaymentId = VmConfig::get('set_automatic_payment', -1);
			
			if ($cfgAutomaticPaymentId > -1)
			{
				$methods = VmModel::getModel('Paymentmethod')->getPayments(TRUE);

				if (!empty($methods)) foreach ($methods as $method)
				{
					if ($cfgAutomaticPaymentId == 0 || $cfgAutomaticPaymentId == $method->virtuemart_paymentmethod_id)
					{
						$cart_bypv->setPayment($method->virtuemart_paymentmethod_id, FALSE);
						break;
					}
				}
			}
		}
		
		$cart->_fromCart = FALSE;
		$cart->order_language = vRequest::getString('order_language', $cart->order_language);
		$cart->prepareCartData();
		
		$view->display();
	
		return $this;
	}
	
	public function getView($name = '', $type = '', $prefix = '', $config = array())
	{
		if ($name == 'cart') {
			$name = 'cart_bypv';
		}
		
		return parent::getView($name, $type, $prefix, $config);
	}
	
	public function checkout()
	{
		if (VmConfig::get('use_as_catalog', 0)) return;

		$input = JFactory::getApplication()->input;
		
		$return_to_cart = (
			$input->getMethod() === 'POST'
			? $this->checkoutFromPost()
			: FALSE
		);

		if (JRequest::getBool('returnToCart') === TRUE)
		{
			$return_to_cart = TRUE;
		}
		
		if ($return_to_cart == FALSE)
		{
			// Checkout
			if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_confirmation_page'))
			{
				$cart = VirtueMartCart::getCart();
				
				if (VM_VERSION < 3)
				{
					// Fix of loading Agreed field in VM 2.6.x
					$userFieldsModel = VmModel::getModel('Userfields');
					$userFieldsModel->_data = $userFieldsModel->getTable('userfields');
					$userFieldsModel->_data->load('agreed', 'name');
					
					$cart->checkout();
				}
				else
				{
					$cart->prepareCartData();
					$cart->checkoutData();
				}
				
				$return_to_cart = TRUE;
			}
			// Confirm
			else
			{
				$this->confirm(TRUE);
			}
		}
		
		if ($return_to_cart) {
			$this->redirectToCart_byPV();
		}
	}
	
	private function checkoutFromPost()
	{
		$cart = VirtueMartCart::getCart();
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		if (!$cart_bypv->isProductInCart())
		{
			$this->redirectToCart_byPV();
		}

		vmLanguage::loadJLang('com_virtuemart_shoppers', TRUE);
		
		/* @var $userModel VirtueMartModelUser */
		$userModel = VmModel::getModel('user');
		
		$return_to_cart = FALSE;

		$input = JFactory::getApplication()->input;

		// Customer Comment
		
		if (VM_VERSION < 3)
		{
			$this->validateCustomerComment_byPV();
		}
		
		// Product List Form
		
		$product_quantity = $input->get('bypv_quantity', NULL, 'array');
		
		// TODO: Check Product List Configuration
		if ($product_quantity === NULL) {
// 			vmInfo('COM_VIRTUEMART_EMPTY_CART');
// 			$return_to_cart = TRUE;
		}
		else
		{
			if ($this->updateProductsInCart_byPV($product_quantity))
			{
				vmInfo('COM_VIRTUEMART_CART_PRODUCT_UPDATED');
				$return_to_cart = TRUE;
			}
		}

		// Customer Type Form
		
		if (count(VirtueMartCart_byPV::$CUSTOMER_TYPES) == 1)
		{
			$customer_type = $cart_bypv->getCustomerType();
		}
		else
		{
			$customer_type = $input->getWord('bypv_customer_type');
		}

		if ($this->isUserLogged_byPV()) {
			if (empty($customer_type)) {
				$customer_type = VirtueMartCart_byPV::CT_LOGIN;
			}

			if ($customer_type !== VirtueMartCart_byPV::CT_LOGIN) {
				vmError('User is logged, but customer_type has filled value "' . $customer_type . '"!', JText::_('COM_VIRTUEMART_CART_DATA_NOT_VALID'));
				$return_to_cart = TRUE;
				$customer_type = FALSE;
			}
		}
		elseif ($customer_type === VirtueMartCart_byPV::CT_LOGIN) {
			vmInfo('JGLOBAL_YOU_MUST_LOGIN_FIRST');
			$return_to_cart = TRUE;
			$customer_type = FALSE;
		}
		
		if ($customer_type) {
			if ($cart_bypv->setCustomerType($customer_type)) {
				$cart_bypv->setCartIntoSession();
			}
			else {
				$return_to_cart = TRUE;
				$customer_type = FALSE;
			}
		}
		elseif ($customer_type === NULL) {
			vmWarn('PLG_SYSTEM_OPC_FOR_VM_BYPV_NO_CUSTOMER_TYPE_SELECTED');
			$return_to_cart = TRUE;
		}

		// Address Forms
		
		if ($customer_type) {
			
			// Billing Address Form
			
			if (!$this->validateUserForm_byPV(VirtueMartCart_byPV::UFT_BILLING_ADDRESS, $input)) {
				$return_to_cart = TRUE;
			}

			// Shipping Address Select Form
			
			if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address'))
			{
				$shipto = $input->getInt('shipto');
				
				$virtuemart_shipmentmethod_id = $input->getInt('virtuemart_shipmentmethod_id');
				
				if (empty($virtuemart_shipmentmethod_id))
				{
					$virtuemart_shipmentmethod_id = $cart_bypv->getShipment();
				}
				
				if ($virtuemart_shipmentmethod_id !== NULL)
				{
					$cfgHideShippingAddressForSelectedShipments = plgSystemOPC_for_VM_byPV::getPluginParam('hide_shipping_address_for_selected_shipments');
				
					if (in_array($virtuemart_shipmentmethod_id, $cfgHideShippingAddressForSelectedShipments))
					{
						$shipto = VirtueMartCart_byPV::ST_SAME_AS_BILL_TO;
					}
				}
				
				if ($shipto === NULL) {
					vmWarn('PLG_SYSTEM_OPC_FOR_VM_BYPV_NO_SHIPTO_SELECTED');
					$return_to_cart = TRUE;
				}
				else {
					if ($cart_bypv->setShipTo($shipto)) {
						$cart_bypv->setCartIntoSession();
						// VM Checkout checks REQUEST for NULL
						JRequest::setVar('shipto', NULL);
						
						if ($shipto > VirtueMartCart_byPV::ST_SAME_AS_BILL_TO) {
				
							// Shipping Address Form
							
							if (!$this->validateUserForm_byPV(VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS, $input)) {
								$return_to_cart = TRUE;
							}
						}
					}
					else {
						$return_to_cart = TRUE;
					}
				}
			}
		}

		// Shipment Form
		
		$cart_bypv->fixShipment();
		
		$virtuemart_shipmentmethod_id = $input->getInt('virtuemart_shipmentmethod_id');
		
		if ($virtuemart_shipmentmethod_id === NULL && $cart->automaticSelectedShipment === TRUE) {
			$virtuemart_shipmentmethod_id = $cart->virtuemart_shipmentmethod_id;
		}
			
		if ($virtuemart_shipmentmethod_id === NULL) {
			vmWarn('COM_VIRTUEMART_CART_NO_SHIPMENT_SELECTED');
			$return_to_cart = TRUE;
		}
		else {
			if ($cart_bypv->setShipment($virtuemart_shipmentmethod_id, TRUE))
			{
				JRequest::setVar('virtuemart_shipmentmethod_id', $virtuemart_shipmentmethod_id);
			}
			else $return_to_cart = TRUE;
		}
		
		// Payment Form

		$cart_bypv->fixPayment();

		$virtuemart_paymentmethod_id = $input->getInt('virtuemart_paymentmethod_id');
		
		if ($virtuemart_paymentmethod_id === NULL && $cart->automaticSelectedPayment === TRUE) {
			$virtuemart_paymentmethod_id = $cart->virtuemart_paymentmethod_id;
		}

		if ($virtuemart_paymentmethod_id === NULL) {
			vmWarn('COM_VIRTUEMART_CART_NO_PAYMENT_SELECTED');
			$return_to_cart = TRUE;
		}
		else {
			if ($cart_bypv->setPayment($virtuemart_paymentmethod_id, TRUE))
			{
				JRequest::setVar('virtuemart_paymentmethod_id', $virtuemart_paymentmethod_id);
			}
			else $return_to_cart = TRUE;
		}
		
		// TOS or Cart Fields

		if (VM_VERSION < 3)
		{
			$cart->tosAccepted = 0;
		}
		else
		{
			// Billing Address Form
				
			if (!$this->validateUserForm_byPV(VirtueMartCart_byPV::UFT_CART, $input)) {
				$return_to_cart = TRUE;
			}
			
// 			$cart->saveCartFieldsInCart();
// 			$cart->tosAccepted = 0;
		}
		
		if (plgSystemOPC_for_VM_byPV::detectExtension_byPV('vm_bonus'))
		{
			JLoader::discover('VmbonusHelperFront', JPATH_SITE . '/components/com_vm_bonus/helpers');
			VmbonusHelperFrontBonus::ParseCart();
		
			$lang = JFactory::getLanguage();
			$lang->load('com_virtuemart', JPATH_BASE, NULL, TRUE);
		}
		
		// Save Cart
		
		$cart->setCartIntoSession();

		return $return_to_cart;
	}

	public function backToCheckout()
	{
		$this->redirectToCart_byPV();
	}

	public function confirm($from_checkout = FALSE)
	{
		if (VmConfig::get('use_as_catalog', 0)) return;

		if (JRequest::getBool('returnToCart') === TRUE)
		{
			$this->redirectToCart_byPV(FALSE);
		}
		
		$cart = VirtueMartCart::getCart();
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		if (VM_VERSION == 3)
		{
			$cart->prepareCartData();
		}
		
		if (!$cart_bypv->isProductInCart() || ($from_checkout === FALSE && $cart->getDataValidated() === FALSE))
		{
			$this->redirectToCart_byPV();
		}
		
		// Probably not needed, but for sure we set the correct Task
		JRequest::setVar('task', 'confirm');
			
		$app = JFactory::getApplication();
		
		// Back to cart button - DEPRECATED
		
		if ($app->input->getString('bypv_submit_back_to_checkout') !== NULL) {
			$this->redirectToCart_byPV();
		}
		
		// Remember products quantity for check after $cart->checkout()
		
		if ($from_checkout === FALSE)
		{
			$this->updateProductsInCart_byPV();
			$products_quantity_tmp = $cart_bypv->getProductsQuantity();
		}
		
		// ApplicationWrapper for forbid redirect
		require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'application_wrapper_bypv.php');
		
		ApplicationWrapper_OPC_for_VM_byPV::attach();
			
		try
		{
			$cart->setDataValidation(FALSE);
			
			if (VM_VERSION < 3)
			{
				$cart->checkout();
			}
			else
			{
				$cart->checkoutData();
			}
		}
		catch (RedirectException_byPV $e)
		{
			$message = trim($e->getMessage());
			
			if (!empty($message) && $message != JText::_('COM_VIRTUEMART_CART_CHECKOUT_DONE_CONFIRM_ORDER'))
			{
				$app->enqueueMessage($message, $e->getMessageType());
			}
		}

		ApplicationWrapper_OPC_for_VM_byPV::detach();
		
		// Check changes in products quantity after $cart->checkout()
		
		if ($from_checkout === FALSE)
		{
			if ($cart_bypv->getProductsQuantity() != $products_quantity_tmp)
			{
				vmInfo('COM_VIRTUEMART_CART_PRODUCT_UPDATED');
				$this->redirectToCart_byPV();
			}
		}

		// Back to cart if not valid checkout

		if ($cart->getDataValidated() === FALSE) {
			$this->redirectToCart_byPV();
		}
		
		// Save user account information

		$cart->BT = (array) $cart_bypv->getUserFieldsData(VirtueMartCart_byPV::UFT_BILLING_ADDRESS);
		$cart->ST = $cart_bypv->getUserFieldsData(VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS);
		
		if (empty($cart->BT)) {
			vmError('Missing Billing Address!', JText::_('COM_VIRTUEMART_CART_DATA_NOT_VALID'));
			$this->redirectToCart_byPV();
		}

		if ($cart_bypv->getShipTo() > VirtueMartCart_byPV::ST_SAME_AS_BILL_TO && empty($cart->ST)) {
			vmError('Missing Shipping Address!', JText::_('COM_VIRTUEMART_CART_DATA_NOT_VALID'));
			$this->redirectToCart_byPV();
		}
		
		// Register User
		
		if ($cart_bypv->checkCustomerType(VirtueMartCart_byPV::CT_REGISTRATION)) {
			if (!$this->registerUser_byPV($cart->BT)) {
				$this->redirectToCart_byPV();
			}
		}
		
		// Update User
		
		if ($cart_bypv->checkCustomerType(VirtueMartCart_byPV::CT_LOGIN)) {
			if (!$this->updateUser_byPV($cart->BT)) {
				$this->redirectToCart_byPV();
			}
		}
    
		if ($cart_bypv->checkCustomerType(VirtueMartCart_byPV::CT_LOGIN, VirtueMartCart_byPV::CT_REGISTRATION))
		{
			// Update Customer Number in the Cart Object for non-guest customers

			$userModel = VmModel::getModel('user');
			$cart->customer_number = $userModel->getCustomerNumberById();
			$cart->setCartIntoSession();

			// Save Addresses

			if (!$this->saveAddress_byPV('BT', $cart->BT)) {
				vmWarn('PLG_SYSTEM_OPC_FOR_VM_BYPV_BILLTO_WAS_NOT_UPDATED');
			}

			if ($cart_bypv->getShipTo() > VirtueMartCart_byPV::ST_SAME_AS_BILL_TO) {
				if (!$this->saveAddress_byPV('ST', $cart->ST)) {
					vmWarn('PLG_SYSTEM_OPC_FOR_VM_BYPV_SHIPTO_WAS_NOT_UPDATED');
				}
			}
		}
		else
		{
			// Update Customer Number in the Cart Object for guest customers (by same way in VM)

			if (empty($cart->customer_number) || strpos($cart->customer_number, 'nonreg_') !== FALSE)
			{
				$cart->customer_number = 'nonreg_';

				if (!empty($cart->BT['first_name'])) $cart->customer_number .= $cart->BT['first_name'];
				if (!empty($cart->BT['last_name'])) $cart->customer_number .= $cart->BT['last_name'];
				if (!empty($cart->BT['email'])) $cart->customer_number .= $cart->BT['email'];
			}
		}

		// Save order

		if (VM_VERSION == 3)
		{
			$cart->cartfields = (array) $cart_bypv->getUserFieldsData(VirtueMartCart_byPV::UFT_CART);
			$cart->BT = array_merge($cart->BT, $cart->cartfields);
		}
		
		$cart->_confirmDone = true;
		$cart->confirmedOrder();
		
		// If user is blocked (beacause user activation is enabled) then unlog user

		$user = JFactory::getUser();

		if (intval($user->block) === 1) {
			JFactory::getSession()->clear('user');
		}
		
		// Empty Cart
		
		$cart_bypv->emptyCart(TRUE);
		
		// If any plugin has own order_done, there is general

		$view = $this->getView('cart', 'html');
		$view->setLayout('orderdone');
		$view->display();
	}
	
	public function add()
	{
		$cart = VirtueMartCart::getCart();
		
		if ($cart->getDataValidated() !== FALSE) {
			$cart->setDataValidation(FALSE);
		}

		if (plgSystemOPC_for_VM_byPV::detectExtension_byPV('vm_bonus') && class_exists('VmbonusHelperFrontCart'))
		{
			VmbonusHelperFrontCart::add();
		}
		else
		{
			parent::add();
		}
	}
	
	public function addJS()
	{
		$cart = VirtueMartCart::getCart();
		
		if ($cart->getDataValidated() !== FALSE) {
			$cart->setDataValidation(FALSE);
		}
		
		if (plgSystemOPC_for_VM_byPV::detectExtension_byPV('vm_bonus') && class_exists('VmbonusHelperFrontCart'))
		{
			VmbonusHelperFrontCart::addJS();
		}
		else
		{
			parent::addJS();
		}
	}
	
	/*** Methods byPV ***/

	private function updateProductsInCart_byPV($quantity = NULL)
	{
		$cart = VirtueMartCart::getCart();
		
		if (VM_VERSION < 3)
		{
			$productModel = VmModel::getModel('product');
				
			if (!empty($cart->products) && is_array($cart->products))
			{
				foreach ($cart->products as $product)
				{
					$tmpProduct = $productModel->getProduct($product->virtuemart_product_id, true, false, true);
				
					$product->product_in_stock = $tmpProduct->product_in_stock;
					$product->product_ordered = $tmpProduct->product_ordered;
					$product->min_order_level = $tmpProduct->min_order_level;
					$product->max_order_level = $tmpProduct->max_order_level;
					$product->step_order_level= $tmpProduct->step_order_level;
				}
			}
		}
		
		if (!empty($quantity) && is_array($quantity))
		{
			$UPDATED = FALSE;
			
			$cart_bypv = VirtueMartCart_byPV::getCart();
			$products_quantity_tmp = $cart_bypv->getProductsQuantity();
			$products_quantity_requested_tmp = $products_quantity_tmp;
			
			foreach ($quantity as $product_id => $set_quantity) {
				$products_quantity_requested_tmp[$product_id] = $set_quantity;
				
				if (VM_VERSION < 3)
				{
					JRequest::setVar('cart_virtuemart_product_id', $product_id);
					JRequest::setVar('quantity', $set_quantity);
				}
				else
				{
					JRequest::setVar('quantity', array($product_id => $set_quantity));
				}
					
				$cart->updateProductCart();
			}
			
			$UPDATED = ($cart_bypv->getProductsQuantity() != $products_quantity_tmp || $products_quantity_requested_tmp != $products_quantity_tmp);
				
			if (VM_VERSION < 3) JRequest::setVar('cart_virtuemart_product_id');
			JRequest::setVar('quantity');
			
			return $UPDATED;
		}
		
		return FALSE;
	}
	
	private function checkSefCheckoutURL_byPV()
	{
		if (JFactory::getDocument()->getType() !== 'html') return;
		if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('use_unique_url_for_every_step')) return;
		
		$input = JFactory::getApplication()->input;
		if ($input->getMethod() === 'POST') return;
		
		$cart = VirtueMartCart::getCart();
		
		if ($cart->getDataValidated() !== FALSE && $input->get('step') !== 'confirmation')
		{
			$step = 'confirmation';
		}
		elseif ($cart->getDataValidated() === FALSE && trim($input->get('step')))
		{
			$step = NULL;
		}
		else return;
		
		$app = JFactory::getApplication();
		$app->redirect(JRoute::_(
			'index.php?option=com_virtuemart&view=cart' . (empty($step) ? '' : '&step=' . $step)
			, FALSE
		));
		jexit();
	}
	
	private function redirectToCart_byPV($invalidate_cart = TRUE)
	{
		if ($invalidate_cart === TRUE)
		{
			$cart = VirtueMartCart::getCart();
			$cart->setDataValidation(FALSE);
			$cart->setCartIntoSession();
		}
		
		$app = JFactory::getApplication();
		$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE));
		jexit();
	}
	
	private function validateCustomerComment_byPV()
	{
		$cart = VirtueMartCart::getCart();
		
		// @since VM 2.0.26
		if (method_exists($cart, 'getFilterCustomerComment'))
		{
			$cart->getFilterCustomerComment();
		}
		else
		{
			$cart->customer_comment = JRequest::getVar('customer_comment', $cart->customer_comment);
			// no HTML TAGS but permit all alphabet
			$value = preg_replace('@<[\/\!]*?[^<>]*?>@si','',$cart->customer_comment);//remove all html tags
			$value = (string)preg_replace('#on[a-z](.+?)\)#si','',$value);//replace start of script onclick() onload()...
			$value = trim(str_replace('"', ' ', $value),"'") ;
			$cart->customer_comment = (string)preg_replace('#^\'#si','',$value);//replace ' at start
		}
	}
		
	/**
	 * @param string $user_field_type
	 * @param JInput $input
	 * @return boolean
	 */
	private function validateUserForm_byPV($user_field_type, $input)
	{
		$cart = VirtueMartCart::getCart();
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$form_valid = TRUE;
		$form_data = array();
		
		
		switch ($user_field_type)
		{
			case VirtueMartCart_byPV::UFT_BILLING_ADDRESS:
				$form_data['address_type'] = 'BT';
				break;
		
			case VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS:
				$form_data['address_type'] = 'ST';
				break;
		}
		
		$password_data = array();
		
		$PLGCFG_HIDE_SHOPPER_FIELDS = plgSystemOPC_for_VM_byPV::getPluginParam('hide_shopper_fields');
	
		$userFieldsTitle = array();
		
		foreach ($cart_bypv->getUserFields($user_field_type) as $field)
		{
			if (in_array($field->name, $PLGCFG_HIDE_SHOPPER_FIELDS))
			{
				$userFieldsTitle[$field->name] = $field->title;
				continue;
			}
			
			$field_prefix = 'bypv_' . $user_field_type . '_';
			
			$form_data[$field->name] = $input->get($field_prefix . $field->name, NULL, 'RAW');
			
			if (is_scalar($form_data[$field->name]))
			{
				$form_data[$field->name] = $this->stripSlashes_byPV(trim($form_data[$field->name]));
			}
	
			if ($form_data[$field->name] === '' || is_array($form_data[$field->name]) && empty($form_data[$field->name]))
			{
				$form_data[$field->name] = NULL;
			}
	
			// VM: This is a special test for the virtuemart_state_id. There is the speciality that the virtuemart_state_id could be 0 but is valid.
			if ($field->name === 'virtuemart_state_id') {
				$virtuemart_country_id = $input->getInt($field_prefix . 'virtuemart_country_id', NULL);
	
				if ($virtuemart_country_id !== NULL) {
					if (!class_exists('VirtueMartModelState')) require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'state.php');
	
					if (plgSystemOPC_for_VM_byPV::isVmVersion('3.2.15')) {
						$resultRequired = null;
						$testStateCountryResult = VirtueMartModelState::testStateCountry($virtuemart_country_id, $form_data[$field->name], $resultRequired);
					}
					else {
						$testStateCountryResult = VirtueMartModelState::testStateCountry($virtuemart_country_id, $form_data[$field->name]);
					}
					
					if ($testStateCountryResult) {
						if ($form_data[$field->name] === NULL) {
							$form_data[$field->name] = 0;
						}
					}
					else {
						$form_data[$field->name] = NULL;
					}
				}
			}
			
			if ($form_data[$field->name] === NULL) {
				if ($field->required) {
					vmWarn(JText::sprintf('COM_VIRTUEMART_MISSING_VALUE_FOR_FIELD', JText::_($field->title)));
					$form_valid = FALSE;
				}
				
				// Problem with shipment plugin "weight_countries" - if "zip" not exists, then COND is FALSE
				if ($field->name === 'zip') {
					$form_data[$field->name] = '';
				}
			}
			
			// Plugin Validation
			else {
				$valid = TRUE;
				
				switch ($field->name) {
					case 'email':
						$valid = JMailHelper::isEmailAddress($form_data[$field->name]);
// 						if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
// 							$valid = (filter_var($form_data[$field->name], FILTER_VALIDATE_EMAIL) !== FALSE);
// 						}
						break; 
				}
				
				if (!$valid) {
					vmWarn(JText::sprintf('PLG_SYSTEM_OPC_FOR_VM_BYPV_INVALID_VALUE_FOR_FIELD', JText::_($field->title)));
					$form_valid = FALSE;
				}
			}
			
			if ($field->type === 'password') {
				$password_data[] =& $form_data[$field->name];
			}
		}
		
		// Autofill Hidden Shopper Fields
		
		$userFieldsData = $cart_bypv->getUserFieldsData($user_field_type);
		
		$PLGCFG_AUTOFILL_HIDDEN_SHOPPER_FIELDS = plgSystemOPC_for_VM_byPV::getPluginParam('autofill_hidden_shopper_fields');

		foreach ($PLGCFG_HIDE_SHOPPER_FIELDS as $field_id)
		{
			if (isset($PLGCFG_AUTOFILL_HIDDEN_SHOPPER_FIELDS[$field_id]))
			{
				$field_cfg = $PLGCFG_AUTOFILL_HIDDEN_SHOPPER_FIELDS[$field_id];
			}
			else $field_cfg = NULL;

			if ($this->isUserLogged_byPV() && (empty($field_cfg) || empty($field_cfg->MODIFIERS) || !in_array('UPDATE', $field_cfg->MODIFIERS)))
			{
				if (array_key_exists($field_id, $userFieldsData))
				{
					$form_data[$field_id] = $userFieldsData[$field_id];
				}
				
				continue;
			}
			
			if (!empty($field_cfg->VALUE))
			{
				$patterns = array();
				$replacements = array();
				
				if (preg_match_all('/{([\w\d_]+)}/', $field_cfg->VALUE, $matches) > 0)
				{
					foreach ($matches[1] as $field_to_replace)
					{
						$patterns[] = '/{' . $field_to_replace . '}/';
						$replacements[] = (
							isset($form_data[$field_to_replace]) && is_scalar($form_data[$field_to_replace])
							? $form_data[$field_to_replace] : ''
						);
					}
				}
				
				$patterns[] = '/\s+/';
				$replacements[] = ' ';
				
				$form_data[$field_id] = preg_replace($patterns, $replacements, $field_cfg->VALUE);
			}
			else
			{
				$form_data[$field_id] = '';
			}

			if (!empty($field_cfg->MODIFIERS))
			{
				if (in_array('UC', $field_cfg->MODIFIERS))
				{
					$form_data[$field_id] = JString::strtoupper($form_data[$field_id]);
				}

				if (in_array('LC', $field_cfg->MODIFIERS))
				{
					$form_data[$field_id] = JString::strtolower($form_data[$field_id]);
				}

				if (in_array('AN', $field_cfg->MODIFIERS))
				{
					jimport('phputf8.utils.ascii');
					
					$form_data[$field_id] = preg_replace('/[^\w\d]+/i', ''
						, utf8_strip_non_ascii_ctrl(utf8_accents_to_ascii($form_data[$field_id]))
					);
				}

				if (in_array('RS', $field_cfg->MODIFIERS))
				{
					$form_data[$field_id] = str_replace(' ', '', $form_data[$field_id]);
				}

				if (
					($field_id === 'username' || in_array('UNIQUE', $field_cfg->MODIFIERS))
					&&
					!empty($form_data[$field_id])
				)
				{
					$db = JFactory::getDBO();
					$query = $db->getQuery(true);
					
					$query
						->select($db->quoteName($field_id))
						->where($db->quoteName($field_id) . ' LIKE ' . $db->quote($form_data[$field_id] . '%'));
					
					if (in_array($field_id, array('name', 'username', 'email', 'password')))
					{
						$query->from($db->quoteName('#__users'));
						
						if (JFactory::getUser()->id > 0)
						{
							$query->where($db->quoteName('id') . ' <> ' . $db->quote(JFactory::getUser()->id));
						}
					}
					else
					{
						$query->from($db->quoteName('#__virtuemart_userinfos'));
						
						if (JFactory::getUser()->id > 0)
						{
							$query->where(
								$db->quoteName('virtuemart_user_id') . ' <> ' . $db->quote(JFactory::getUser()->id));
						}
					}
					
					$db->setQuery($query);
					$storedValues = $db->loadColumn();

					$value = $form_data[$field_id];
					$i = 0;
					
					while (in_array($value, $storedValues) && ++$i <= 100)
					{
						$value = $form_data[$field_id] . rand(1000, 9999);
					}

					if (in_array($value, $storedValues))
					{
						vmWarn(JText::sprintf('PLG_SYSTEM_OPC_FOR_VM_BYPV_INVALID_VALUE_FOR_FIELD', JText::_($userFieldsTitle[$field_id])));
						$form_valid = FALSE;
					}
					else
					{
						$form_data[$field_id] = $value;
					}
				}
			}
		}
		
		// Check Passwords

		if (!empty($password_data) && count(array_unique($password_data)) > 1) {
			// Load the language file for com_users
			$lang = JFactory::getLanguage();
			$lang->load('com_users', JPATH_SITE);
			
			vmWarn('COM_USERS_REGISTER_PASSWORD1_MESSAGE');
			$form_valid = FALSE;

			foreach ($password_data as &$password) {
				$password = NULL;
			}
		}
		
		// VMUserFieldPlugins
		
		JPluginHelper::importPlugin('vmuserfield');
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('plgVmOnBeforeUserfieldDataSave',array(&$form_valid, JFactory::getUser()->get('id'), &$form_data, JFactory::getUser()));

		$cart_bypv->setUserFieldsData($user_field_type, $form_data);
		
		$cart->setCartIntoSession();
		
		return $form_valid;
	}
	
	private function registerUser_byPV($data)
	{
		$user_keys = array('email', 'username', 'password', 'password2', 'name');
		$user_data = array();
		
		foreach ($data as $key => $value) {
			if (in_array($key, $user_keys)) $user_data[$key] = $value;
		}

		$user_data['email1'] = $user_data['email'];
		$user_data['password1'] = $user_data['password'];

		// Load the language file for com_users
		$lang = JFactory::getLanguage();
		$lang->load('com_users', JPATH_SITE);
		
		// Save selected frontend language
		
		$user_data['params'] = array(
			'language' => $lang->getTag()
		);
		
		$com = JPATH_SITE . DS . 'components' . DS . 'com_users';
		if (!class_exists('UsersModelRegistration')) require($com . DS . 'models' . DS . 'registration.php');

		JForm::addFormPath($com . '/models/forms');
		JForm::addFieldPath($com . '/models/fields');
		JForm::addFormPath($com . '/model/form');
		JForm::addFieldPath($com . '/model/field');

		$model = new UsersModelRegistration();
		$result = $model->register($user_data);

		if (in_array($result, array('useractivate', 'adminactivate'))) {
			$q = '
				SELECT `id`
				FROM #__users
				WHERE `username` = "' . $user_data['username'] . '" AND `email` = "' . $user_data['email'] . '"
			';
		
			$db = JFactory::getDbo();
			$db->setQuery($q, 0, 1);
			$user_id = (int) $db->loadResult();
		}
		else {
			$user_id = $result;
		}
		
		$app = JFactory::getApplication();
		
		if (is_numeric($user_id) && $user_id > 0) {
			$user = JFactory::getUser($user_id);
			JFactory::getSession()->set('user', $user);

			$userModel = VmModel::getModel('user');
			// Set ID and reset CutomerNumber.
			$userModel->getCurrentUser();
			
			$userModel->saveUserData($user_data);

			// Message after user registration

			if ($result === 'adminactivate')
			{
				$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_VERIFY'));
			}
			elseif ($result === 'useractivate')
			{
				$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_ACTIVATE'));
			}
			else
			{
				$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'));
			}
			
			return TRUE;
		}
		else {
			foreach ($model->getErrors() as $error) {
				$app->enqueueMessage($error, 'error'); 
			}
		}
		
		return FALSE;
	}
	
	private function updateUser_byPV($data)
	{
		$user = JFactory::getUser();
		
		if ($user->id < 1) {
			vmError('User is not logged!', JText::_('COM_VIRTUEMART_CART_DATA_NOT_VALID'));
			return FALSE; 
		} 

		if (isset($data['username'])) {
			$user->set('username', $data['username']);
		}

		if (isset($data['name'])) {
			$user->set('name', $data['name']);
		}

		if (!isset($data['email'])) {
			vmError('Input field "email" not found!', JText::_('COM_VIRTUEMART_CART_DATA_NOT_VALID'));
			return FALSE; 
		}
		
		$user->set('email', $data['email']);
		
		// Load the language file for com_users
		$lang = JFactory::getLanguage();
		$lang->load('com_users', JPATH_SITE);
		
		// Store the data.
		if (!$user->save(TRUE)) {
			$app = JFactory::getApplication();
			
			foreach ($user->getErrors() as $error) {
				$app->enqueueMessage($error, 'error');
			}

			// Joomla! Fix - Why errors are saved to session???
			$user->set('_errors', array());
			
			return FALSE;
		}

		$userModel = VmModel::getModel('user');
		// Set ID and reset CutomerNumber.
		$userModel->getCurrentUser();

		return $userModel->saveUserData($data);
	}
	
	private function saveAddress_byPV($type, $data)
	{
		if (!in_array($type, array('BT', 'ST'))) {
			vmError(
				'Invalid argument $type in function ' . __FUNCTION__ . '. Valid argument is BT or ST ($type = ' . json_encode($type) . ').',
				'Error when save user address.'
			);
			
			return FALSE;
		}

		if (empty($data) || !is_array($data)) {
			vmError(
				'Invalid argument $data in function ' . __FUNCTION__ . '. Argument $data must be non-empty array with address fields ($data = ' . json_encode($data) . ').',
				'Error when save user address.'
			);
			
			return FALSE;
		}
		
		$cart_bypv = VirtueMartCart_byPV::getCart(FALSE);

		$data['virtuemart_user_id'] = JFactory::getUser()->id;
		$virtuemart_userinfo_id = 0;

		if ($type == 'BT' && $cart_bypv->getCustomerType() == VirtueMartCart_byPV::CT_LOGIN) {
			$q = 'SELECT `virtuemart_userinfo_id`, `agreed` FROM #__virtuemart_userinfos
				WHERE `virtuemart_user_id` = ' . JFactory::getUser()->id . ' AND `address_type` = "BT"';
			
			$db = JFactory::getDbo();
			$db->setQuery($q, 0, 1);
			list($virtuemart_userinfo_id, $agreed) = $db->loadRow();

			if ($virtuemart_userinfo_id > 0)
			{
				// Field "agreed" is not in $data if user is logged
				$data['agreed'] = $agreed;
			}
		}
		
		if ($type == 'ST') {
			if ($cart_bypv->getShipTo() === VirtueMartCart_byPV::ST_SAME_AS_BILL_TO) {
				return FALSE;
			}
			
			if ($cart_bypv->getShipTo() > 0) {
				$virtuemart_userinfo_id = $cart_bypv->getShipTo();
			}
		}
		
		$userModel = VmModel::getModel('user');
		$userInfoTable = $userModel->getTable('userinfos');
		
		if ($virtuemart_userinfo_id > 0) {
			$userInfoTable->load($virtuemart_userinfo_id);
		}
		
		$userInfoData = $userModel->_prepareUserFields($data, $type, $userInfoTable);
		
		if (!$userInfoTable->bindChecknStore($userInfoData)) {
			vmError('storeAddress ' . $userInfoTable->getError());
			return FALSE;
		}
		
		return TRUE;
	}
	
	/*** JSON ***/
	
	public function setCartDataJS_byPV()
	{
		$UPDATED = $UPDATED_PRODUCTS = FALSE;
		
		$cart = VirtueMartCart::getCart(FALSE);
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$input = JFactory::getApplication()->input;
		
		$input_fields = array(
			'bypv_quantity'					=> 'array',
			'bypv_coupon_code'				=> 'string',
			'virtuemart_shipmentmethod_id'	=> 'int',
			'virtuemart_paymentmethod_id'	=> 'int',
		);
		
		$PLGCFG_TRACKING_OF_CHANGES = plgSystemOPC_for_VM_byPV::getPluginParam('tracking_of_changes');
		
		foreach (VirtueMartCart_byPV::$USER_FIELD_TYPES as $user_field_type => $user_field_type_params) {
			foreach ($PLGCFG_TRACKING_OF_CHANGES as $field_name) {
				$input_fields['bypv_' . $user_field_type . '_' . $field_name] = 'string';
			}
		}
		
		$form = $input->getArray($input_fields);
		
		$skipBlocks = array();
		$forceBlocks = array();

		if (is_array($form)) {
			// Product Quantity
			
			if (isset($form['bypv_quantity']) && is_array($form['bypv_quantity'])) {
				$UPDATED = $UPDATED_PRODUCTS = $this->updateProductsInCart_byPV($form['bypv_quantity']);
				
				if ($UPDATED_PRODUCTS)
				{
					$forceBlocks[] = VirtueMartViewCart_byPV::TPL_PRODUCT_LIST;

					// Fix for VM3: No INFO message (only ERROR) if product quantity reaches stock limit

					$cart->prepareCartData();
					
					foreach ($cart->products as $product)
					{
						if (!empty($product->errorMsg) && $product->errorMsg === vmText::sprintf('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_QUANTITY', $product->product_name, $product->quantity))
						{
							vmInfo($product->errorMsg);
						}
					}
				}
			}
			
			// Coupon Code

			if (isset($form['bypv_coupon_code']))
			{
				// @before VM 3.0.9.8
				if (VM_VERSION == 3 && version_compare(vmVersion::$RELEASE, '3.0.9.8', '<=')
						&& !isset($cart->pricesUnformatted['salesPrice'])
					)
				{
					$cart->prepareCartData();
				}
				
				if (VM_VERSION < 3 && trim($form['bypv_coupon_code']) === '')
				{
					$cart->couponCode = '';
					$result = false;
				}
				else
				{
					$result = $cart->setCouponCode(trim($form['bypv_coupon_code']));
				}

				if ($result === false && trim($form['bypv_coupon_code']) === '' && empty($cart->couponCode)) {
					$result = JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_COUPON_CODE_REMOVED');
				}
				
				if (is_string($result)) {
					JFactory::getApplication()->enqueueMessage($result);
				}

				// VM Hack - If coupon is not valid, info remains...
				unset(
					$cart->cartData['couponCode'],
					$cart->cartData['couponDescr']
				);
				
				$UPDATED = $UPDATED_PRODUCTS = TRUE;
			}

			// Shipment

			if (isset($form['virtuemart_shipmentmethod_id'])) {
				$cart_bypv->setShipment($form['virtuemart_shipmentmethod_id'], FALSE);
				$UPDATED = TRUE;
			}
			
			// Payment
			
			if (isset($form['virtuemart_paymentmethod_id'])) {
				$cart_bypv->setPayment($form['virtuemart_paymentmethod_id'], FALSE);
				$UPDATED = TRUE;
			}

			// User Fields

			$USER_FIELDS_UPDATED = FALSE;

			foreach (VirtueMartCart_byPV::$USER_FIELD_TYPES as $user_field_type => $user_field_type_params)
			{
				$field_prefix = 'bypv_' . $user_field_type . '_';
				$fields_data = $cart_bypv->getUserFieldsData($user_field_type);
				
				foreach ($PLGCFG_TRACKING_OF_CHANGES as $field_name)
				{
					// Fields not sent by JS are null -> isset() = false
					if (isset($form[$field_prefix . $field_name]))
					{
						$fields_data[$field_name] = $form[$field_prefix . $field_name];
						$USER_FIELDS_UPDATED = TRUE;
						
						switch ($user_field_type)
						{
							case VirtueMartCart_byPV::UFT_BILLING_ADDRESS:
								$skipBlocks[] = VirtueMartViewCart_byPV::TPL_BILLING_ADDRESS;
								break;
								
							case VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS:
								$skipBlocks[] = VirtueMartViewCart_byPV::TPL_SHIPPING_ADDRESS;
								break;
								
							case VirtueMartCart_byPV::UFT_CART:
								$skipBlocks[] = VirtueMartViewCart_byPV::TPL_CART_FIELDS;
								break;
						}
					}
				}
				
				$cart_bypv->setUserFieldsData($user_field_type, $fields_data);
			}

			if ($USER_FIELDS_UPDATED)
			{
				// VMUserFieldPlugins
				JPluginHelper::importPlugin('vmuserfield');
				$dispatcher = JDispatcher::getInstance();
				$form_valid = TRUE;
				
				$cart->BT['address_type'] = 'BT';
				$dispatcher->trigger('plgVmOnBeforeUserfieldDataSave',array(&$form_valid, JFactory::getUser()->get('id'), &$cart->BT, JFactory::getUser()));
				$cart_bypv->setUserFieldsData(VirtueMartCart_byPV::UFT_BILLING_ADDRESS, $cart->BT);
				
				if (!empty($cart->ST))
				{
					$cart->ST['address_type'] = 'ST';
					$dispatcher->trigger('plgVmOnBeforeUserfieldDataSave',array(&$form_valid, JFactory::getUser()->get('id'), &$cart->ST, JFactory::getUser()));
					$cart_bypv->setUserFieldsData(VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS, $cart->ST);
				}
				
				$cart->setCartIntoSession();
				$UPDATED = TRUE;
				
				if (!class_exists('VirtueMartViewCart_byPV'))
				{
					require(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'views' . DS . 'cart_bypv' . DS . 'view.html.php');
				}
			}
		}
		
		$cart_bypv->setCartIntoSession();
		
		$cart = VirtueMartCart::getCart();
		$cart->setCartIntoSession(TRUE); // TRUE - Save cart in the VM3 

		if ($UPDATED_PRODUCTS)
		{
			// Patch for the component "VM Bonus (Discounts.com)"
			
			if (plgSystemOPC_for_VM_byPV::detectExtension_byPV('vm_bonus'))
			{
				JLoader::discover('VmbonusHelperFront', JPATH_SITE . '/components/com_vm_bonus/helpers');
				VmbonusHelperFrontBonus::ParseCart();
				
				$lang = JFactory::getLanguage();
				$lang->load('com_virtuemart', JPATH_BASE, NULL, TRUE);
				
				$UPDATED = TRUE;
			}
		}
			
		// Check empty cart

		if ($cart_bypv->isProductInCart() && $UPDATED)
		{
			$this->redirectToRefreshCartJS_byPV(array_unique($skipBlocks), $forceBlocks);
		}
		else
		{
			$this->display();
		}
	}
	
	public function emptyCartJS_byPV()
	{
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_empty_cart'))
		{
			$cart_bypv = VirtueMartCart_byPV::getCart();
			$cart_bypv->emptyCart(TRUE);
		}

		$this->display();
	}

	public function validateJoomlaFieldsJS_byPV()
	{
		plgSystemOPC_for_VM_byPV::$processOnlyMessages = TRUE;
		
		$input = JFactory::getApplication()->input;
		
		$fieldName = trim($input->getCmd('field_name'));
		$fieldValue = trim($input->getString('field_value'));
		
		if (in_array($fieldName, array('email', 'username')) && !empty($fieldValue))
		{
			$form = JForm::getInstance(
				'plg_system_opc_for_vm_bypv.registration',
				OPC_FOR_VM_BYPV_PLUGIN_PATH . '/models/forms/registration.xml'
			);

			$fieldValue = $input->get('field_value', NULL, $form->getFieldAttribute($fieldName, 'filter', 'string'));
			
			if ($form->validate(array($fieldName => $fieldValue)) == FALSE)
			{
				JFactory::getLanguage()->load('com_users');

				$app = JFactory::getApplication();
				
				foreach ($form->getErrors() as $error)
				{
					if ($error instanceof Exception)
					{
						$error = $error->getMessage();
					}

					$app->enqueueMessage(JText::_($error), 'warning');
				}
			}
		}
	}
	
	private function redirectToRefreshCartJS_byPV($skip_blocks = array(), $force_blocks = array())
	{
		$app = JFactory::getApplication();
		
		if (!empty($skip_blocks))
		{
			$skip_blocks_params = array();
			
			foreach ((array) $skip_blocks as $block)
			{
				$skip_blocks_params[] = 'skip_blocks[]=' . $block;
			}
		}

		if (!empty($force_blocks))
		{
			$force_blocks_params = array();
			
			foreach ((array) $force_blocks as $block)
			{
				$force_blocks_params[] = 'force_blocks[]=' . $block;
			}
		}

		$url = JRoute::_('index.php?option=com_virtuemart&view=cart'
			. '&task=refreshCartBlocksJS_byPV&format=json&cart_lang=' . $app->input->get('cart_lang')
			. '&bypv_form_checksum=' . $app->input->getString('bypv_form_checksum')
			. (isset($skip_blocks_params) ? '&' . implode('&', $skip_blocks_params) : '')
			. (isset($force_blocks_params) ? '&' . implode('&', $force_blocks_params) : '')
		, FALSE);
		
		// Fix for servers with no support for json requests with suffix ".json" (only when SEF with Suffix is enabled).
		
		$app->redirect(str_replace(
			'refreshCartBlocksJS_byPV.json?',
			'refreshCartBlocksJS_byPV.html?format=json&',
			$url
		));

		jexit();
	}
	
	/**
	 * Login user by JSON request.
	 * 
	 * Based on com_users->user(controller)->login() method.
	 * 
	 * @return void
	 */
	public function loginJS_byPV()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		
		$data = array();
		$data['username'] = $input->post->getUsername('username');
		$data['password'] = $input->post->getRaw('password');
		
		// Get the log in options.
		$options = array();
		$options['remember'] = $input->post->getBool('remember', FALSE);
		
		// Get the log in credentials.
		$credentials = array();
		$credentials['username'] = $data['username'];
		$credentials['password'] = $data['password'];
		
		// Perform the log in.
		if (true === $app->login($credentials, $options)) {
			// Success
			
			// VM Fix: Reload Language (this reinitialize Router, ...).
			if (class_exists('vmrouterHelper'))
			{
				vmrouterHelper::getInstance()->menu = null;
			}
			if (class_exists('vmLanguage'))
			{
				vmLanguage::$jSelLangTag = false;
				vmLanguage::initialise();
			}
			
			// VM Fix: In $cart->setPreferred() is condition for count(BT) < 1
			$cart = VirtueMartCart::getCart(TRUE);
			$cart->BT = NULL;
			
			$app->setUserState('users.login.form.data', array());

			// Patch for the component "VM Bonus (Discounts.com)"
				
			if (plgSystemOPC_for_VM_byPV::detectExtension_byPV('vm_bonus'))
			{
				JLoader::discover('VmbonusHelperFront', JPATH_SITE . '/components/com_vm_bonus/helpers');
				VmbonusHelperFrontBonus::ParseCart();
			
				$lang = JFactory::getLanguage();
				$lang->load('com_virtuemart', JPATH_BASE, NULL, TRUE);
			
				$UPDATED = TRUE;
			}
		}
		else {
			// Login failed !
			$data['remember'] = (int) $options['remember'];
			$app->setUserState('users.login.form.data', $data);
		}
	}

	/**
	 * Login user by JSON request.
	 * 
	 * Based on com_users->user(controller)->logout() method.
	 * 
	 * @return void
	 */
	public function logoutJS_byPV()
	{
		$app = JFactory::getApplication();
		
		// Perform the log out.
		$error = $app->logout();
		
		if (VM_VERSION == 3)
		{
			$cart = VirtueMartCart::getCart(TRUE);
		}
		else
		{
			VirtueMartCart::getCart()->emptyCart();
		}
		
		$this->display();
	}
	
	public function updateCustomerFormJS_byPV()
	{
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$input = JFactory::getApplication()->input;
		$customer_type = $input->getWord('bypv_customer_type');

		if ($customer_type !== NULL) {
			if ($cart_bypv->setCustomerType($customer_type)) {
				$address_bt = $this->stripSlashes_byPV($input->getString('address_bt'));
				$address_st = $this->stripSlashes_byPV($input->getString('address_st'));

				$this->setCartAddressByUserLocalFields_byPV(VirtueMartCart_byPV::UFT_BILLING_ADDRESS, $address_bt);
				$this->setCartAddressByUserLocalFields_byPV(VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS, $address_st);

				$cart_bypv->setCartIntoSession();
			}
		}

		$this->display();
	}
	
	// Not used
	public function overrideFieldsDataJS_byPV()
	{
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$input = JFactory::getApplication()->input;
		$override = ($input->getInt('override') === 1);
		
		$cart_bypv->overrideFieldsData($override);

		if ($override)
		{
			$this->display();
		}
	}
	
	public function updateShippingAddressJS_byPV()
	{
		$cart_bypv = VirtueMartCart_byPV::getCart();
		
		$input = JFactory::getApplication()->input;
		$shipto = $input->getInt('shipto');
		
		if ($shipto !== NULL) {
			if ($cart_bypv->setShipTo($shipto)) {
				$address_st = $this->stripSlashes_byPV($input->getString('address_st'));
				$this->setCartAddressByUserLocalFields_byPV(VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS, $address_st);
				
				$cart_bypv->setCartIntoSession(TRUE);
			}
			else {
				vmError('Invalid Shipping Address ID!', JText::_('COM_VIRTUEMART_CART_DATA_NOT_VALID'));
			}
		}
		
		$this->display();
	}
	
	public function deleteShippingAddressJS_byPV()
	{
    if (!plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_delete_shipping_address'))
    {
      return;
    }
    
		$input = JFactory::getApplication()->input;
		
    $user = JFactory::getUser();
    $shipTo = $input->getInt('shipto');
		
    if ($user->id > 0 && $shipTo > 0 && !isset(VirtueMartCart_byPV::$SHIPPING_TO_TYPES[$shipTo]))
    {
      $db = JFactory::getDBO();
      
      $query = $db->getQuery(true)
        ->delete($db->quoteName('#__virtuemart_userinfos'))
        ->where($db->quoteName('virtuemart_user_id') . ' = ' . $db->quote($user->id))
        ->where($db->quoteName('virtuemart_userinfo_id') . ' = ' . $db->quote($shipTo))
      ;
      
      $result = $db->setQuery($query)->execute();
      
      if ($result && $db->getAffectedRows() > 0)
      {
        $cart_bypv = VirtueMartCart_byPV::getCart();

        if ($cart_bypv->getShipTo() == $shipTo)
        {
          $cart_bypv->setShipTo(VirtueMartCart_byPV::ST_SAME_AS_BILL_TO);
          $cart_bypv->setCartIntoSession(TRUE);
        }
      }
      else
      {
        JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_SHIPTO_WAS_NOT_UPDATED'));
      }
    }
    
		$this->display();
	}
	
	private function setCartAddressByUserLocalFields_byPV($user_field_type, $address_data)
	{
		if (empty($address_data)) return;
		$cart_bypv = VirtueMartCart_byPV::getCart();

		if (!isset(VirtueMartCart_byPV::$USER_FIELD_TYPES[$user_field_type])) return;
		
		if (
			$cart_bypv->checkCustomerType(VirtueMartCart_byPV::CT_REGISTRATION, VirtueMartCart_byPV::CT_GUEST)
			||
			$cart_bypv->checkCustomerType(VirtueMartCart_byPV::CT_LOGIN) && $this->isUserLogged_byPV()
		)
		{
			if ($user_field_type != VirtueMartCart_byPV::UFT_SHIPPING_ADDRESS || $cart_bypv->getShipTo() > VirtueMartCart_byPV::ST_SAME_AS_BILL_TO)
			{
				if ($address_data = json_decode($address_data, TRUE))
				{
					$field_prefix = 'bypv_' . $user_field_type . '_';
					$fields_data = $cart_bypv->getUserFieldsData($user_field_type);
					
					foreach ($address_data as $field_name => $field_value)
					{
						if (substr($field_name, 0, strlen($field_prefix)) === $field_prefix)
						{
							$fields_data[substr($field_name, strlen($field_prefix))] = $field_value;
						}
					}
					
					$cart_bypv->setUserFieldsData($user_field_type, $fields_data);
				}
			}
		}
	}
	
	private function isUserLogged_byPV()
	{
		return (JFactory::getUser()->guest != 1); // Different solution = JFactory::getUser()->id > 0
	}
	
	private function stripSlashes_byPV($string)
	{
		if (empty($string) || !get_magic_quotes_gpc()) return $string;
		
		if (is_array($string)) foreach ($string as &$value) $value = $this->stripSlashes_byPV($value);
		else $string =  stripslashes($string);

		return $string;
	}
	
	public function viewJS()
	{
		jimport('joomla.application.module.helper');
		
		if (!JModuleHelper::isEnabled('mod_virtuemart_cart'))
		{
			return parent::viewJS();
		}
		
		if (!class_exists('VirtueMartCart')) require(VMPATH_SITE . '/helpers/cart.php');
		$cart = VirtueMartCart::getCart(FALSE);
		$cart->prepareCartData();
		$data = $cart->prepareAjaxData(TRUE);
		
		if (VM_VERSION < 3)
		{
			$extension = 'com_virtuemart';
			vmLanguage::loadJLang($extension); //  when AJAX it needs to be loaded manually here >> in case you are outside virtuemart !
			
			if ($data->totalProduct > 1) $data->totalProductTxt = JText::sprintf('COM_VIRTUEMART_CART_X_PRODUCTS', $data->totalProduct);
			elseif ($data->totalProduct == 1) $data->totalProductTxt = JText::_('COM_VIRTUEMART_CART_ONE_PRODUCT');
			else $data->totalProductTxt = JText::_('COM_VIRTUEMART_EMPTY_CART');
			
			// TODO: It's not needed for our needs...
			
			if ($data->dataValidated == true)
			{
				$taskRoute = '&task=confirm';
				$linkName = JText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU');
			}
			else
			{
				$taskRoute = '';
				$linkName = JText::_('COM_VIRTUEMART_CART_SHOW');
			}
			
			$data->cart_show = '<a class="floatright" href="' . JRoute::_("index.php?option=com_virtuemart&view=cart" . $taskRoute, $this->useXHTML, $this->useSSL) . '" rel="nofollow">' . $linkName . '</a>';
			$data->billTotal = JText::_('COM_VIRTUEMART_CART_TOTAL') . ' : <strong>' . $data->billTotal . '</strong>';
		}

		// VM Hack for module "mod_virtuemart_cart" if the cart is empty
		
		if ($data->totalProduct == 0)
		{
			$data->totalProduct = 0;
			$data->billTotal = '';
			$data->cart_show = '';
		}
		
		echo json_encode($data);
		jexit();
	}
}