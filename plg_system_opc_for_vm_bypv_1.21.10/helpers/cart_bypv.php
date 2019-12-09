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

require_once(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'image.php');
require_once(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');

class VirtueMartCart_byPV
{
	// Customer Type
	
	const CT_LOGIN				= 'login';
	const CT_REGISTRATION		= 'registration';
	const CT_GUEST				= 'guest';
	
	public static $CUSTOMER_TYPES = array(
		self::CT_LOGIN => array(
			'name' 			=> 'PLG_SYSTEM_OPC_FOR_VM_BYPV_LOGIN_LABEL',
			'description'	=> 'PLG_SYSTEM_OPC_FOR_VM_BYPV_LOGIN_DESCRIPTION'
		),
		self::CT_REGISTRATION => array(
			'name' 			=> 'PLG_SYSTEM_OPC_FOR_VM_BYPV_REGISTRATION_LABEL',
			'description'	=> 'PLG_SYSTEM_OPC_FOR_VM_BYPV_REGISTRATION_DESCRIPTION'
		),
		self::CT_GUEST => array(
			'name' 			=> 'PLG_SYSTEM_OPC_FOR_VM_BYPV_GUEST_LABEL',
			'description'	=> 'PLG_SYSTEM_OPC_FOR_VM_BYPV_GUEST_DESCRIPTION'
		),
	);
	
	// User Field Type
	
	const UFT_BILLING_ADDRESS	= 'billing_address';
	const UFT_SHIPPING_ADDRESS	= 'shipping_address';
	const UFT_CART				= 'cart';

	public static $USER_FIELD_TYPES = array(
		self::UFT_BILLING_ADDRESS	=> array('vmcart_var' => 'BT'),
		self::UFT_SHIPPING_ADDRESS	=> array('vmcart_var' => 'ST'),
		self::UFT_CART				=> array('vmcart_var' => 'cartfields'),
	);
	
	private $user_fields_data = array();
	
	// Ship To
	
	const ST_SAME_AS_BILL_TO	= -1;
	const ST_NEW_ADDRESS		= 0;
	
	public static $SHIPPING_TO_TYPES = array(
		self::ST_SAME_AS_BILL_TO 	=> '',
		self::ST_NEW_ADDRESS		=> ''
	);
	
	private static $cart = NULL;

	private $checkout_displayed = FALSE; 
	
	private $shipment = NULL;
	private $payment = NULL;
	
	private $customer_type = NULL;
	private $shipto = self::ST_SAME_AS_BILL_TO;
	private $logged_user = 0;
	
	public static function Initialize()
	{
		// Customer Types
		
		$disable_customer_types = array();
		
		$PLGCFG_ALLOW_LOGIN = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_login_when_ordering');
		$PLGCFG_ALLOW_REGISTRATION = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_registration_when_ordering');
		$PLGCFG_ALLOW_GUEST = plgSystemOPC_for_VM_byPV::isPluginParamEnabled('allow_order_from_guest');
		
		$VMCFG_ONCHECKOUT_ONLY_REGISTERED = (VmConfig::get('oncheckout_only_registered', 0) == 1);
		$VMCFG_ONCHECKOUT_SHOW_REGISTER = (VmConfig::get('oncheckout_show_register', 0) == 1);
		
		if (!$PLGCFG_ALLOW_LOGIN && JFactory::getUser()->id == 0)
		{
			$disable_customer_types[] = self::CT_LOGIN;
		}
		
		if (!$PLGCFG_ALLOW_REGISTRATION || !$VMCFG_ONCHECKOUT_SHOW_REGISTER)
		{
			$disable_customer_types[] = self::CT_REGISTRATION;
		}

		if ($VMCFG_ONCHECKOUT_ONLY_REGISTERED || !$PLGCFG_ALLOW_GUEST)
		{
			$disable_customer_types[] = self::CT_GUEST;
		}
		
		foreach ($disable_customer_types as $customer_type)
		{
			unset(self::$CUSTOMER_TYPES[$customer_type]);
		}
		
		// Shipping To Types
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address') === FALSE)
		{
			unset(self::$SHIPPING_TO_TYPES[self::ST_NEW_ADDRESS]);
		}
		
		$cart = VirtueMartCart::getCart();
		$cart_bypv = self::getCart();

		if ($cart_bypv->isUserFieldsData(self::UFT_BILLING_ADDRESS))
		{
			$cart->BT = $cart_bypv->getUserFieldsData(self::UFT_BILLING_ADDRESS);
		}
		elseif (!empty($cart->BT))
		{
			$user_fields_data_override[self::UFT_BILLING_ADDRESS] = $cart->BT;
		}
		
		$cart->STsameAsBT = ($cart_bypv->getShipTo() === self::ST_SAME_AS_BILL_TO);

		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address'))
		{
			if ($cart_bypv->isUserFieldsData(self::UFT_SHIPPING_ADDRESS))
			{
				$cart->ST = $cart_bypv->getUserFieldsData(self::UFT_SHIPPING_ADDRESS);
			}
			elseif (!empty($cart->ST))
			{
				$user_fields_data_override[self::UFT_SHIPPING_ADDRESS] = $cart->ST;
			}
		}

		if (VM_VERSION == 3)
		{
			if ($cart_bypv->isUserFieldsData(self::UFT_CART))
			{
				$cart->cartfields = $cart_bypv->getUserFieldsData(self::UFT_CART);
			}
			elseif (!empty($cart->cartfields))
			{
				$user_fields_data_override[self::UFT_CART] = $cart->cartfields;
			}
		}
		
		if (!empty($user_fields_data_override))
		{
			$cart_bypv->overrideFieldsData(TRUE, $user_fields_data_override);
		}
		
// TODO: Resolve a problem with filling of address in another way (PayPal Express, Masterpass, etc.)
// TODO: Probably with conditions for specific query params... 
		
// 		$app = JFactory::getApplication();
// 		$router = $app->getRouter();
		
// 		if ($router->getVar('option') === 'com_virtuemart' || JRequest::getVar('option') === 'com_virtuemart')
// 		{
// 			if (JRequest::getVar('view') === 'cart_bypv' && !in_array(JRequest::getVar('task'), array('checkout', 'confirm')))
// 			{
// 				$cart->BT = NULL;
// 				$cart->ST = NULL;
				
// 				if (JFactory::getUser()->id > 0)
// 				{
// 					$userModel = VmModel::getModel('user');
// 					$user = $userModel->getCurrentUser();
// 					$user->userInfo = array();
// 				}
// 			}
// 		}

		$cart_bypv->fixShipment();
		$cart_bypv->fixPayment();
	}
	
	public function checkoutDisplayed()
	{
		return ($this->checkout_displayed === TRUE);
	}

	public function setCheckoutDisplayed($displayed)
	{
		$this->checkout_displayed = ($displayed === TRUE);
	}

	public static function getCart($validate_states = TRUE)
	{
		if (empty(self::$cart))
		{
			$session = JFactory::getSession();
			
			if ($session->has('vmcart_bypv', 'vm')) {
				self::$cart = unserialize($session->get('vmcart_bypv', NULL, 'vm'));
			}
			else {
				self::clearFieldsLocalJS();
			}
			
			if (empty(self::$cart) || !self::$cart instanceof VirtueMartCart_byPV) {
				self::$cart = new VirtueMartCart_byPV();
				self::$cart->setCartIntoSession();
			}
		}
		
		if ($validate_states === TRUE)
		{
			self::$cart->validationStates();
		}
		
		return self::$cart;
	}
	
	public function emptyCart($VirtueMartCart_too = FALSE)
	{
		JFactory::getSession()->clear('vmcart_bypv', 'vm');
		self::$cart = NULL;

		if ($VirtueMartCart_too === TRUE) {
			$cart = VirtueMartCart::getCart();
			$cart->BT = NULL;
			$cart->ST = NULL;
			$cart->emptyCart();
		}
	}
	
	public function validationStates()
	{
		// Customer Type
		
		$customer_type = $this->getCustomerType();
		
		if (JFactory::getUser()->id > 0 && (int) JFactory::getUser()->block === 0)
		{
			$customer_type = self::CT_LOGIN;
		}
		elseif ($customer_type === NULL)
		{
			$customer_type = plgSystemOPC_for_VM_byPV::getPluginParam('default_customer_type');
		}
		
		if (!isset(self::$CUSTOMER_TYPES[$customer_type]))
		{
			$customer_type = key(self::$CUSTOMER_TYPES);
		}
		
		$this->setCustomerType($customer_type);

		// Shipping To

		$shipto = $this->shipto;
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address') === FALSE)
		{
			$shipto = self::ST_SAME_AS_BILL_TO;
		}

		if (!is_numeric($shipto) && !isset(self::$SHIPPING_TO_TYPES[$shipto]))
		{
			$shipto = key(self::$SHIPPING_TO_TYPES);
		}
		
		$this->setShipTo($shipto);
		
		// User Data
		
		$this->initUserDataInCart();
		
		// Save to session
		
		$this->setCartIntoSession();
	}
	
	public function setCartIntoSession($VirtueMartCart_too = FALSE)
	{
		$session = JFactory::getSession();
		$session->set('vmcart_bypv', serialize($this), 'vm');
		
		if ($VirtueMartCart_too === TRUE) {
			$cart = VirtueMartCart::getCart();
			$cart->setCartIntoSession();
		}
	}
	
	public function isProductInCart()
	{
		$products = $this->getProductsQuantity();
		return !empty($products);
	}
	
	public function getProductsQuantity()
	{
		$cart = VirtueMartCart::getCart();
		$products = array();
		
		if (VM_VERSION < 3)
		{
			foreach ($cart->products as $product_key => $product)
			{
				$products[$product_key] = $product->quantity;
			}
		}
		else
		{
			foreach ($cart->cartProductsData as $product_key => $product)
			{
				$products[$product_key] = $product['quantity'];
			}
		}
		
		return $products;
	}
	
	/**
	 * Set Shipment
	 *
	 * Note: plgVmOnSelectCheckShipment should be triggered only when form submits.
	 *       Extra parameters for methods are checked.
	 */
	public function setShipment($shipment_id, $trigger_select_check = TRUE)
	{
		$cart = VirtueMartCart::getCart(FALSE);
		$app = JFactory::getApplication();
		$VALID = TRUE;
		
		// ApplicationWrapper for forbid redirect
		require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'application_wrapper_bypv.php');
			
		$override_methods = array();
		if ($trigger_select_check === TRUE) $override_methods[] = 'redirect';
		else $override_methods[] = 'enqueueMessage';
				
		ApplicationWrapper_OPC_for_VM_byPV::attach($override_methods);
				
		try
		{
			$this->shipment = $shipment_id;
			
			$cart->virtuemart_shipmentmethod_id = (int) $shipment_id;
			
			if (!empty($shipment_id) && $trigger_select_check === TRUE)
			{
				if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
				JPluginHelper::importPlugin('vmshipment');
				
				//Add a hook here for other payment methods, checking the data of the choosed plugin
				$_dispatcher = JDispatcher::getInstance();
				$_retValues = $_dispatcher->trigger('plgVmOnSelectCheckShipment', array( &$cart));
				
				$dataValid = true;
				foreach ($_retValues as $_retVal) {
					if ($_retVal === true ) {
						$VALID = TRUE;
						// Plugin completed successfull; nothing else to do
						break;
					} elseif ($_retVal === false ) {
						$VALID = FALSE;
						break;
					}
				}
				
// 				if (!$VALID)
// 				{
// 					$this->shipment = NULL;
// 					$cart->virtuemart_shipmentmethod_id = 0;
// 				}
			}
		}
		catch (RedirectException_byPV $e)
		{
			if ($trigger_select_check === TRUE && $e->isMessage()) $app->enqueueMessage($e->getMessage(), $e->getMessageType());
			$VALID = FALSE;
		}
		
		ApplicationWrapper_OPC_for_VM_byPV::detach();
		
		$cfgHideShippingAddressForSelectedShipments = plgSystemOPC_for_VM_byPV::getPluginParam('hide_shipping_address_for_selected_shipments');
		
		if (in_array($this->shipment, $cfgHideShippingAddressForSelectedShipments))
		{
			$this->setShipTo(VirtueMartCart_byPV::ST_SAME_AS_BILL_TO);
		}
		
		return $VALID;
	}
	
	public function fixShipment()
	{
		$cart = VirtueMartCart::getCart(FALSE);

		if (empty($this->shipment) && !empty($cart->virtuemart_shipmentmethod_id))
		{
			$this->setShipment($cart->virtuemart_shipmentmethod_id);
		}
		elseif (!empty($this->shipment))
		{
			$cart->virtuemart_shipmentmethod_id = $this->shipment;
		}
		
		$prices = (VM_VERSION < 3 ? $cart->pricesUnformatted : $cart->cartPrices);
		if (empty($prices)) return;
		$cart->CheckAutomaticSelectedShipment($prices, TRUE);

		if ($cart->automaticSelectedShipment && !empty($cart->virtuemart_shipmentmethod_id) && $cart->virtuemart_shipmentmethod_id != $this->shipment)
		{
			$this->setShipment($cart->virtuemart_shipmentmethod_id);
			$this->setCartIntoSession();
		}
	}

	public function getShipment()
	{
		return $this->shipment;
	}

	/**
	 * Set Payment
	 * 
	 * Note: plgVmOnSelectCheckPayment should be triggered only when form submits.
	 *       Extra parameters for methods are checked.
	 */
	public function setPayment($payment_id, $trigger_select_check = TRUE)
	{
		$cart = VirtueMartCart::getCart(FALSE);
		$app = JFactory::getApplication();
		$VALID = TRUE;
		
		// ApplicationWrapper for forbid redirect
		require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'application_wrapper_bypv.php');
			
		$override_methods = array();
		if ($trigger_select_check === TRUE) $override_methods[] = 'redirect';
		else $override_methods[] = 'enqueueMessage';
		
		ApplicationWrapper_OPC_for_VM_byPV::attach($override_methods);
		
		try
		{
			$this->payment = $payment_id;
			
			$cart->virtuemart_paymentmethod_id = (int) $payment_id;
			
			if (!empty($payment_id) && $trigger_select_check === TRUE)
			{
				if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
				JPluginHelper::importPlugin('vmpayment');
				
				//Add a hook here for other payment methods, checking the data of the choosed plugin
				$msg = '';
				$_dispatcher = JDispatcher::getInstance();
				$_retValues = $_dispatcher->trigger('plgVmOnSelectCheckPayment', array( $cart, &$msg));

				foreach ($_retValues as $_retVal) {
					if ($_retVal === true ) {
						$VALID = TRUE;
						// Plugin completed succesfull; nothing else to do
						break;
					} else if ($_retVal === false ) {
						$VALID = FALSE;
						break;
					}
				}
				
// 				if (!$VALID)
// 				{
// 					$this->payment = NULL;
// 					$cart->virtuemart_paymentmethod_id = 0;
// 				}
			}
				
		}
		catch (RedirectException_byPV $e)
		{
			if ($trigger_select_check === TRUE && $e->isMessage()) $app->enqueueMessage($e->getMessage(), $e->getMessageType());
			$VALID = FALSE;
		}
		
		ApplicationWrapper_OPC_for_VM_byPV::detach();
		
		return $VALID;
	}

	public function fixPayment()
	{
		$cart = VirtueMartCart::getCart(FALSE);
		
		if (empty($this->payment) && !empty($cart->virtuemart_paymentmethod_id))
		{
			$this->payment = $cart->virtuemart_paymentmethod_id;
		}
		elseif (!empty($this->payment))
		{
			$cart->virtuemart_paymentmethod_id = $this->payment;
		}
		
		$prices = (VM_VERSION < 3 ? $cart->pricesUnformatted : $cart->cartPrices);
		if (empty($prices)) return;
		
		$cart->CheckAutomaticSelectedPayment($prices, TRUE);
		
		if ($cart->automaticSelectedPayment && !empty($cart->virtuemart_paymentmethod_id) && $cart->virtuemart_paymentmethod_id != $this->payment)
		{
			$this->payment = $cart->virtuemart_paymentmethod_id;
			$this->setCartIntoSession();
		}
	}
	
	public function getPayment()
	{
		return $this->payment;
	}
	
	public function setCustomerType($customer_type)
	{
		static $errorDisplayed = FALSE;
		
		if (isset(self::$CUSTOMER_TYPES[$customer_type]))
		{
			if ($this->customer_type == $customer_type)
			{
				return TRUE;
			}
			
			$tmp_customer_type = $this->customer_type;
			$this->customer_type = $customer_type;
			
			// Don't rewrite existing cart data when first set
			
			if ($tmp_customer_type === NULL)
			{
				return TRUE;
			}
			
			$cart = VirtueMartCart::getCart();

			if ($customer_type !== self::CT_LOGIN)
// 			{
// 				$cart->BT = NULL;
// 				$cart->ST = NULL;
// 			}
// 			else
			{
				$cart->BT = $this->getUserFieldsData(self::UFT_BILLING_ADDRESS);
				$cart->ST = $this->getUserFieldsData(self::UFT_SHIPPING_ADDRESS);
			}
			
			return TRUE;
		}
		else
		{
			$this->customer_type = NULL;
			
			if (!$errorDisplayed)
			{
				if (count(self::$CUSTOMER_TYPES) < 1)
				{
					$msg = 'Error in the configuration (no customer type allowed)!';
				}
				else
				{
					$msg = 'Invalid argument in function ' . __FUNCTION__ . '. Valid argument is constant CT_... from class ' . __CLASS__ . '!';
				}
				
				vmError(
					$msg,
					'Internal error when setting customer type.'
				);
				
				$errorDisplayed = TRUE;
			}
			
			return FALSE;
		}
	}
	
	public function checkCustomerType($customer_types)
	{
		$customer_types = func_get_args();
		
		return in_array($this->customer_type, $customer_types);
	}
	
	public function getCustomerType()
	{
		return $this->customer_type;
	}
	
	public function setShipTo($virtuemart_userinfo_id)
	{
// 		if ($this->shipto == $virtuemart_userinfo_id) {
// 			return TRUE;
// 		}
		
		$result = FALSE;
		
		if (isset(self::$SHIPPING_TO_TYPES[$virtuemart_userinfo_id])) {
			$this->shipto = $virtuemart_userinfo_id;
			
			$cart = VirtueMartCart::getCart();
			$cart->selected_shipto = NULL;
			
			switch ($virtuemart_userinfo_id) {
				case self::ST_SAME_AS_BILL_TO:
					if (VM_VERSION == 3)
					{
						$cart->selected_shipto = 0;
					}
					$cart->STsameAsBT = 1;
					$cart->ST = NULL;
					break;
					
				case self::ST_NEW_ADDRESS:
					if (VM_VERSION == 3)
					{
						$cart->selected_shipto = -1;
					}
					
					$cart->STsameAsBT = 0;
					
					if (!empty($cart->ST) && empty($cart->ST['virtuemart_userinfo_id']) && !$this->isUserFieldsData(self::UFT_SHIPPING_ADDRESS))
					{
						$this->setUserFieldsData(self::UFT_SHIPPING_ADDRESS, $cart->ST);
					}
					else
					{
						$cart->ST = $this->getUserFieldsData(self::UFT_SHIPPING_ADDRESS);
					}
					break;
			}
			
			$result = TRUE;
		}
		elseif ($virtuemart_userinfo_id > 0)
		{
			if (isset($this->user_fields_data[self::UFT_SHIPPING_ADDRESS][$virtuemart_userinfo_id]))
			{
				$this->shipto = $virtuemart_userinfo_id;
				
				$cart = VirtueMartCart::getCart();
				$cart->selected_shipto = $virtuemart_userinfo_id;
				$cart->ST = $this->getUserFieldsData(self::UFT_SHIPPING_ADDRESS);
				$cart->STsameAsBT = 0;
				
				$result = TRUE;
			}
			else
			{
				/* @var $userModel VirtueMartModelUser */
				$userModel = VmModel::getModel('user');
				
				$stData = $userModel->getUserAddressList($userModel->getId(), 'ST', $virtuemart_userinfo_id);
				$stData = get_object_vars($stData[0]);
				
				// Check if address exists
				if (!empty($stData)) {
					$this->shipto = $virtuemart_userinfo_id;
					
					$cart = VirtueMartCart::getCart();
					$cart->selected_shipto = $virtuemart_userinfo_id;
					$this->setUserFieldsData(self::UFT_SHIPPING_ADDRESS, $stData);
					$cart->STsameAsBT = 0;
	
					$result = TRUE;
				}
			}
		}
		
		if ($result === FALSE)
		{
			vmError(
				sprintf('Invalid argument ($virtuemart_userinfo_id = %s, $shipto = %s) in function ' . __FUNCTION__ . '. Valid argument is constant ST_... from class ' . __CLASS__ . ' or ID of address.', $virtuemart_userinfo_id, $this->shipto),
				'Internal error when setting shipping address.'
			);
		}
		
		return $result;
	}
	
	public function getShipTo()
	{
		return $this->shipto;
	}
		
	public function getUserFields($user_field_type)
	{
		// Field "address_type" is not in DB - it's virtual field, that we set up manually in checkout phase 
		switch ($user_field_type)
		{
			case self::UFT_BILLING_ADDRESS:
				if ($this->customer_type === self::CT_REGISTRATION)
				{
					if (VM_VERSION < 3) $section = 'registration';
					else $section = 'account';
					
					$skip = array('address_type');
				}
				else
				{
					$section = 'account';
					$skip = array('username', 'name', 'password', 'password2', 'agreed', 'address_type');
				}
				$switches = array();
				
				break;
	
			case self::UFT_SHIPPING_ADDRESS:
				$section = 'shipment';
				$switches = array();
				$skip = array('username', 'name', 'password', 'password2', 'agreed', 'address_type');
				break;
	
			case self::UFT_CART:
				$section = 'cart';
				$switches = array('captcha' => true, 'delimiters' => false); // In VM3 is delimiter => TRUE
				$skip = array('delimiter_userinfo', 'user_is_vendor', 'username', 'password', 'password2', 'agreed', 'address_type');
				break;
	
			default:
				vmError(
					'Invalid argument in function ' . __FUNCTION__ . '. Valid argument is constant UFT_... from class ' . __CLASS__ . '.',
					'Internal error when loading user fields.'
				);
				return array();
		}

		/* @var $userFieldsModel VirtueMartModelUserfields */
		$userFieldsModel = VmModel::getModel('userfields');
		$userFields = (array) $userFieldsModel->getUserFields($section, $switches, $skip);

		JPluginHelper::importPlugin('vmuserfield');
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('plgVmOnGetUserfields', array(self::$USER_FIELD_TYPES[$user_field_type]['vmcart_var'], &$userFields));
		
		if ($user_field_type === self::UFT_BILLING_ADDRESS && $this->customer_type === self::CT_REGISTRATION)
		{
			foreach ($userFields as $userField) if (isset($userField->register) && $userField->register == 1)
			{
				$userField->required = 1;
			}
		}
		
		return $userFields;
	}
	
	private function getUserFieldDefaultValues($user_field_type) {
		$values = array();
		
		foreach ($this->getUserFields($user_field_type) as $field) {
			$value = NULL;
			
			if (empty($field->default))
			{
				// @before VM 2.0.26
				if (VM_VERSION < 3 && version_compare(vmVersion::$RELEASE, '2.0.26', '<'))
				{
					// Problem with shipment plugin "weight_countries" - if "zip" not exists, then COND is FALSE 
					if ($field->name == 'zip') $value = '';
				}
			}
			else {
				$value = $field->default;
			}
			
			if ($value !== NULL) {
				$values[$field->name] = $value;
			}
		}
		
		return $values;
	}
	
	public function setUserFieldsData($user_field_type, $data)
	{
		if (!isset(self::$USER_FIELD_TYPES[$user_field_type]))
		{
			vmError(
				'Invalid argument in function ' . __FUNCTION__ . '. Valid argument is constant UFT_... from class ' . __CLASS__ . '.',
				'Internal error when set of user fields.'
			);
		}
		
		$data = (array) $data;
		if (empty($data)) $data = NULL;
		
		if ($user_field_type == self::UFT_SHIPPING_ADDRESS)
		{
			if (empty($this->user_fields_data[$user_field_type]))
			{
				$this->user_fields_data[$user_field_type] = array();
			}
			$this->user_fields_data[$user_field_type][$this->shipto] = $data;
		}
		else
		{
			$this->user_fields_data[$user_field_type] = $data;
		}

		$this->setCartIntoSession();
		
		$cart = VirtueMartCart::getCart();
		$cart->{self::$USER_FIELD_TYPES[$user_field_type]['vmcart_var']} = $this->getUserFieldsData($user_field_type);
	}
	
	public function isUserFieldsData($user_field_type)
	{
		$data = $this->getUserFieldsData($user_field_type);
		return (!empty($data));
	}

	public function getUserFieldsData($user_field_type)
	{
		if (!isset(self::$USER_FIELD_TYPES[$user_field_type]))
		{
			vmError(
				'Invalid argument in function ' . __FUNCTION__ . '. Valid argument is constant UFT_... from class ' . __CLASS__ . '.',
				'Internal error when get of user fields.'
			);
		}

		$data = (
			isset($this->user_fields_data[$user_field_type])
			? $this->user_fields_data[$user_field_type]
			: NULL
		);
		
		if ($user_field_type == self::UFT_SHIPPING_ADDRESS && is_array($data))
		{
			$data = (isset($data[$this->shipto]) ? $data[$this->shipto] : NULL);
		}
		
		return $data;
	}
	
	private function initUserDataInCart()
	{
		$user = JFactory::getUser();
		$cart = VirtueMartCart::getCart();

		if ($this->logged_user !== $user->id)
		{
			$this->logged_user = $user->id;
			self::clearFieldsLocalJS();
			
			if ($user->id > 0 && (int) $user->block === 0)
			{
				$userModel = VmModel::getModel('user');
				$userVM = $userModel->getCurrentUser();
			
				if (!empty($userVM->userInfo)) foreach ($userVM->userInfo as $address) if ($address->address_type === 'BT')
				{
					$cart->saveAddressInCart((array) $address, $address->address_type, FALSE);
				}
				
				$this->setUserFieldsData(self::UFT_BILLING_ADDRESS, !empty($cart->BT) ? $cart->BT : array());
				$this->setShipTo(self::ST_SAME_AS_BILL_TO);
// 				$this->setUserFieldsData(self::UFT_SHIPPING_ADDRESS, NULL);
			}
			
			$cart->setDataValidation(FALSE);
		}
	}
	
	public static function clearFieldsLocalJS($BT = TRUE, $ST = TRUE)
	{
		static $cleared = FALSE;
		
		if ($cleared === TRUE || !plgSystemOPC_for_VM_byPV::isPluginParamEnabled('remember_form_fields')) return;

		$BT = ($BT === TRUE ? 'true' : 'false');
		$ST = ($ST === TRUE ? 'true' : 'false');
		
		$document = JFactory::getDocument();
		$document->addScriptDeclaration(
			"if (typeof(VirtueMartCart_byPV) !== 'undefined') VirtueMartCart_byPV.clearFieldsLocal($BT, $ST); " .
			"else if (typeof(Storage) !== 'undefined') { " .
				($BT === 'true' ? "sessionStorage.setItem('bypv.virtuemartcart.bt', null);" : '') .
				($ST === 'true' ? "sessionStorage.setItem('bypv.virtuemartcart.st', null);" : '') .
			" }"
		);
		
		$cleared = TRUE;
	}
	
	private $user_fields_data_override = array();
	
	public function detectChangesInUserFieldsData()
	{
		$cart = VirtueMartCart::getCart();

		$BT = $this->getUserFieldsData(self::UFT_BILLING_ADDRESS);

		if (!empty($cart->BT) && $cart->BT != $BT)
		{
			$this->user_fields_data_override[self::UFT_BILLING_ADDRESS] = $cart->BT;
		}
		
		if (empty($BT))
		{
			$cart->BT = $this->getUserFieldDefaultValues(self::UFT_BILLING_ADDRESS);
		}
		else
		{
			$cart->BT = $BT;
		}
		
		if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address'))
		{
			$ST = $this->getUserFieldsData(self::UFT_SHIPPING_ADDRESS);
	
			if (!empty($cart->ST) && $cart->ST != $ST)
			{
				$this->user_fields_data_override[self::UFT_SHIPPING_ADDRESS] = $cart->ST;
			}
	
			if (empty($ST) && $this->shipto == self::ST_NEW_ADDRESS)
			{
				$cart->ST = $this->getUserFieldDefaultValues(self::UFT_SHIPPING_ADDRESS);
			}
			else
			{
				$cart->ST = $ST;
			}
		}

		if (VM_VERSION == 3)
		{
			$cartFields = $this->getUserFieldsData(self::UFT_CART);
	
			if (!empty($cart->cartfields) && $cart->cartfields != $cartFields)
			{
				$this->user_fields_data_override[self::UFT_CART] = $cart->cartfields;
			}
			
			if (empty($cartFields))
			{
				$cart->cartfields = $this->getUserFieldDefaultValues(self::UFT_CART);
			}
			else
			{
				$cart->cartfields = $cartFields;
			}
		}
		
		if (!empty($this->user_fields_data_override))
		{
			$this->overrideFieldsData(TRUE);
			
			// TODO: Lang Constant
			
// 			if ((int) JFactory::getUser()->id > 0 || empty($BT))
// 			{
// 				$this->overrideFieldsData(TRUE);
// 			}
// 			else
// 			{
// 				JFactory::getDocument()->addScriptDeclaration(
// 					"jQuery( function($) {" .
// 						"VirtueMartCart_byPV.overrideFieldsData('Došlo ke změně v údajích zákazníka. Přejete se změny přijmout?');" .
// 					"});"
// 				);
// 			}
		}
		
		$this->setCartIntoSession();
	}
	
	public function overrideFieldsData($override, $user_fields_data_override = NULL)
	{
		if ($user_fields_data_override === NULL) $user_fields_data_override =& $this->user_fields_data_override;
		if (empty($user_fields_data_override)) return FALSE;
		
		if ($override === TRUE)
		{
			$BTFieldsChanged = FALSE;
			$STFieldsChanged = FALSE;

			// Only not logged users and users with empty billing address
			
			if (isset($user_fields_data_override[self::UFT_BILLING_ADDRESS]) && (int) JFactory::getUser()->id == 0 && !$this->isUserFieldsData(self::UFT_BILLING_ADDRESS))
			{
				$this->setUserFieldsData(self::UFT_BILLING_ADDRESS, $user_fields_data_override[self::UFT_BILLING_ADDRESS]);
				$BTFieldsChanged = TRUE;
			}

			if (plgSystemOPC_for_VM_byPV::isPluginParamEnabled('show_shipping_address'))
			{
				if (isset($user_fields_data_override[self::UFT_SHIPPING_ADDRESS]))
				{
					$this->setShipTo(self::ST_NEW_ADDRESS);
					$this->setUserFieldsData(self::UFT_SHIPPING_ADDRESS, $user_fields_data_override[self::UFT_SHIPPING_ADDRESS]);
					$STFieldsChanged = TRUE;
				}
			}

			if (VM_VERSION == 3 && isset($user_fields_data_override[self::UFT_CART]))
			{
				$this->setUserFieldsData(self::UFT_CART, $user_fields_data_override[self::UFT_CART]);
			}
			
			if ($BTFieldsChanged || $STFieldsChanged)
			{
				if ($this->checkCustomerType(self::CT_LOGIN))
				{
					if (isset(self::$CUSTOMER_TYPES[self::CT_REGISTRATION])) $this->setCustomerType(self::CT_REGISTRATION);
					elseif (isset(self::$CUSTOMER_TYPES[self::CT_GUEST])) $this->setCustomerType(self::CT_GUEST);
				}
				
				self::clearFieldsLocalJS($BTFieldsChanged, $BTFieldsChanged);
			}
		}
		
		$user_fields_data_override = array();
		$this->setCartIntoSession();
		
		return TRUE;
	}
}

VirtueMartCart_byPV::Initialize();
