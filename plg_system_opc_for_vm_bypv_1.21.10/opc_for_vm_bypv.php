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

define('OPC_FOR_VM_BYPV_PLUGIN_PATH', dirname(__FILE__));
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

JForm::addFieldPath(dirname(__FILE__) . '/models/fields');

class plgSystemOPC_for_VM_byPV extends JPlugin
{
	const DEMO_MODULE = 'mod_demo_opc_for_vm_bypv';
	
	/**
	 * @var JRegistry
	 */
	private static $plugin_params = NULL;
	
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = FALSE;
	
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if ($this->initDemoModule_byPV())
		{
			modDemoOPCforVMbyPVHelper::onLoad($this);
		}
		
		self::$plugin_params = $this->params;
	}
	
	public static function getPluginParam($path)
	{
		switch ($path)
		{
			case 'show_shipments':
			case 'show_payments':
			case 'show_shipping_address':
			case 'show_advertisements':
			case 'show_comment':
			case 'allow_delete_shipping_address':
			case 'show_selected_shipment':
			case 'show_selected_payment':
			case 'show_zero_amounts':
			case 'allow_login_when_ordering':
			case 'allow_registration_when_ordering':
			case 'allow_order_from_guest':
			case 'allow_confirmation_page':
			case 'remember_form_fields':
			case 'validate_fields_of_joomla_immediately':
			case 'allow_validation_in_browser':
			case 'allow_autocompleting_forms':
			case 'use_plugin_layout_css':
			case 'use_plugin_layout_responsive_css':
			case 'allow_autorefresh_for_external_modules':
				$default_value = '1';
				break;

			case 'allow_empty_cart':
			case 'update_product_quantity_promptly':
			case 'show_customer_types_always':
			case 'use_plugin_custom_css':
			case 'header_level_offset':
			case 'use_unique_url_for_every_step':

			case 'enable_patch_for_vm_privacy':
			case 'enable_patch_for_vm_bonus':
				$default_value = '0';
				break;

			case 'default_customer_type':
				$default_value = 'login';
				break;

			case 'plugin_layout':
				$default_value = 'vertical';
				break;
				
			case 'plugin_theme_css':
				$default_value = 'j25_beez_vm20_default';
				break;
				
			case 'show_order_summary_in':
			case 'show_coupon_code_in':
				$default_value = 'product_list';
				break;
				
			case 'product_list_col_1':
				$default_value = 'SKU';
				break;
			case 'product_list_col_2':
				$default_value = 'NAME';
				break;
			case 'product_list_col_3':
				$default_value = 'PRICE_EXCL_TAX::ORIGINAL';
				break;
			case 'product_list_col_4':
				$default_value = 'QUANTITY::EDIT';
				break;
			case 'product_list_col_5':
				$default_value = 'DISCOUNT';
				break;
			case 'product_list_col_6':
				$default_value = 'TOTAL_EXCL_TAX::DISCOUNTED';
				break;
			case 'product_list_col_7':
				$default_value = 'TAX::DISCOUNTED';
				break;
			case 'product_list_col_8':
				$default_value = 'TOTAL_INCL_TAX::DISCOUNTED';
				break;
			case 'product_list_col_9':
				$default_value = 'DROP';
				break;

			case 'hide_shipping_address_for_selected_shipments':
			case 'shipments_incompatible_with_ajax':
			case 'payments_incompatible_with_ajax':
			case 'hide_shopper_fields':
				$default_value = array();
				break;

			case 'tracking_of_changes':
				$default_value = array('zip', 'virtuemart_country_id');
				break;

			case 'loading_overlay_show_style':
				$default_value = 'CENTER';
				break;

			case 'loading_overlay_hide_style':
				$default_value = 'TRANSPARENCY';
				break;

			default:
				$default_value = NULL;
		}
		
		$value = self::$plugin_params->get($path, $default_value);
		
		switch ($path)
		{
			case 'tracking_of_changes':
				if (is_string($value)) $value = explode('|', $value);

			case 'hide_shopper_fields':
				if (!is_array($value)) $value = array();

				foreach ($value as $i => $userFieldName)
				{
					if (empty($userFieldName)) unset($value[$i]);
				}
				
				$value = array_values($value);
				break;

			case 'autofill_hidden_shopper_fields':
				if (preg_match_all('/^\s*([\w\d_]+)\s*=\s*"([^"]+)"(?:\s*\(\s*([^\)]+)\s*\))?\s*$/m', $value, $matches, PREG_SET_ORDER) > 0)
				{
					$value = array();
					
					foreach ($matches as $match)
					{
						$FIELD = new stdClass();
						$FIELD->VALUE = trim($match[2]);

						if (!empty($FIELD->VALUE))
						{
							if (!empty($match[3]))
							{
								if (preg_match_all('/[A-Z]+/', $match[3], $mod_matches) > 0)
								{
									$FIELD->MODIFIERS = $mod_matches[0]; 
								}
							}
							
							$value[$match[1]] = $FIELD;
						}
					}
				}
				else $value = array();
				break;

			case 'shopper_fields_display_conditions':
				if (preg_match_all('/^\s*IF\s*{([\w\d_]+)}\s*=\s*(\[[^\]]+\]|"[^"]+")\s*SHOW(.+)$/m', $value, $matches, PREG_SET_ORDER) > 0)
				{
					$value = array();
					
					foreach ($matches as $match)
					{
						$CONDITION = new stdClass();
						$CONDITION->FIELD = $match[1];
						$CONDITION->VALUE = $match[2];
						$CONDITION->SHOW = $match[3];

						if (!empty($CONDITION->FIELD) && !empty($CONDITION->VALUE) && !empty($CONDITION->SHOW))
						{
							if (substr($CONDITION->VALUE, 0, 1) === '"')
							{
								$CONDITION->VALUE = array(substr($CONDITION->VALUE, 1, -1));
							}
							elseif (substr($CONDITION->VALUE, 0, 1) === '[' && preg_match_all('/"([^"]+)"/', substr($CONDITION->VALUE, 1, -1), $value_matches) > 0)
							{
								$CONDITION->VALUE = $value_matches[1]; 
							}
							
							if (preg_match_all('/{([\w\d_]+)}/', $CONDITION->SHOW, $show_matches) > 0)
							{
								$CONDITION->SHOW = $show_matches[1]; 
							}
							
							$value[] = $CONDITION;
						}
					}
				}
				else $value = array();
				break;
		}
		
		return $value;
	}
	
	public static function isPluginParamEnabled($path)
	{
		return (self::getPluginParam($path) === '1');
	}
	
	/*** TRIGGERS ***/
	
	private $pluginProcessed = FALSE;
	
	private $initTask = NULL;
	private $comVMBonusEnabled = NULL;
	
	function onAfterInitialise()
	{
		$app = JFactory::getApplication();
		
		if ($app->isSite())
		{
			$this->initTask = JRequest::getCmd('task');
			
			// Reinitialize plugin to last position
			
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->detach($this);
			$dispatcher->attach($this);
			
			if (self::detectExtension_byPV('vm_bonus'))
			{
				$comVMBonusHelper = JComponentHelper::getParams('com_vm_bonus');
				$this->comVMBonusEnabled = (int) $comVMBonusHelper->get('bonus_enabled', 0);
				
				if ($this->comVMBonusEnabled == 1)
				{
					$comVMBonusHelper->set('bonus_enabled', 0);
				}
			}
			
			// SEF URL
			
			if (self::isPluginParamEnabled('use_unique_url_for_every_step'))
			{
				$router = $app->getRouter();
	
				if ($router->getMode() == JROUTER_MODE_SEF)
				{
					if (version_compare(JVERSION, '3.0.0', '>='))
					{
						$router->attachParseRule(array($this, 'parseRule'), JRouter::PROCESS_AFTER);
						$router->attachBuildRule(array($this, 'buildRule'), JRouter::PROCESS_BEFORE);
					}
					else
					{
						$router->attachParseRule(array($this, 'parseRule'));
						$router->attachBuildRule(array($this, 'buildRule'));
					}
				}
			}
		}
	}
	
    public function onAfterRoute()
	{
    	$app = JFactory::getApplication();
    	$router = $app->getRouter();

    	if ($app->isAdmin())
    	{
//     		if ($app->input->get('option') === 'com_plugins' && $app->input->get('view') === 'plugin' && $app->input->get('layout') === 'edit' && $app->input->getInt('extension_id') > 0)
//     		{
//     			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_plugins/models');
//     			JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_plugins/models/forms');
//     			JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_plugins/models/fields');
//     			$form = JModelLegacy::getInstance('plugin', 'PluginsModel')->getForm();

//     			$form->removeField('allow_confirmation_page', 'params');

// 	    		$db = JFactory::getDbo();
// 	    		$db->setQuery('SELECT `extension_id` FROM `#__extensions` WHERE `folder`=' . $db->quote($this->_type) . ' AND `element`=' . $db->quote($this->_name));
	    		
// 	    		if ($app->input->getInt('extension_id') == $db->loadResult())
// 	    		{
// 	    			// Load VmConfig
// 	    			if (!class_exists('VmConfig')) {
// 	    				require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
// 	    			}
	    			
// 	    			vmLanguage::loadJLang('com_virtuemart');
// 	    		}
//     		}
    	}
    	
    	else
    	{
	    	// Component Virtuemart
	    	
	    	if ($router->getVar('option') === 'com_virtuemart' || JRequest::getVar('option') === 'com_virtuemart')
	    	{
	    		// Load VmConfig
	    		if (!class_exists('VmConfig')) {
	    			require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
	    		}
	    		VmConfig::loadConfig();
	    		 
	    		// Fix for older VM with undefined constant
	    		if (!defined('VM_VERSION'))
	    		{
	    			define('VM_VERSION', (version_compare(vmVersion::$RELEASE, '2.9.0', '>=') ? 3 : 2));
	    		}
	    		
	    		require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'cart_bypv.php');

	    		if (self::detectExtension_byPV('vm_bonus') && $this->comVMBonusEnabled == 1)
	    		{
	    			$comVMBonusHelper = JComponentHelper::getParams('com_vm_bonus');
	    			$comVMBonusHelper->set('bonus_enabled', 1);
	    			 
	   				if ($comVMBonusHelper->get('loadcss', 1))
	   				{
	   					JFactory::getDocument()->addStyleSheet(JURI::root(TRUE) . '/components/com_vm_bonus/assets/css/vmbonus.css');
	   				}
	    		}
	    		
	    		$viewName = JRequest::getVar('view');
	    		
	    		if (substr($viewName, -5) === '_bypv')
	    		{
	    			$viewName = substr($viewName, 0, -5);
	    			JRequest::setVar('view', $viewName);
	    		}
	    		
	    		switch (JRequest::getVar('view')) {
			    	
			    	// View Cart
	    			case 'cart':
						if ($this->initDemoModule_byPV() && modDemoOPCforVMbyPVHelper::onRequest())
						{
							$this->redirectToCart_byPV();
						}
						
						// We set manually ItemId for correct function of JModuleHelper Class 
						$menus      = $app->getMenu('site');
						$component  = JComponentHelper::getComponent('com_virtuemart');
						$items      = $menus->getItems('component_id', $component->id);
						
						foreach ($items as $item)
						{
							if (isset($item->query, $item->query['view']))
							{
								if ($item->query['view'] === 'cart')
								{
									JRequest::setVar('Itemid', $item->id);
									break;
								}
							}
						}
	
			    	// Plugin Response
	    			case 'vmplg':
	    			case 'pluginresponse':
	    				// For sure
	    				vmJsApi::jQuery();
	    				
		    			// If OPC Enabled
		    			if (VmConfig::get('oncheckout_opc', 1) == 1)
		    			{
			    			JRequest::setVar('view', JRequest::getVar('view') . '_bypv');
			    			
			    			require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'controllers' . DS . 'cart_bypv.php');
			    			
			    			// VM >= 2.9.9f
			    			if (VM_VERSION == 3 && is_file(JPATH_VM_SITE . DS . 'controllers' . DS . 'vmplg.php'))
			    			{
			    				if (JRequest::getVar('view') === 'pluginresponse_bypv')
			    				{
			    					JRequest::setVar('view', 'vmplg_bypv');
			    				}
			    				
			    				require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'controllers' . DS . 'vmplg_bypv.php');
			    			}
			    			else
			    			{
			    				require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'controllers' . DS . 'pluginresponse_bypv.php');
			    			}
	
			    			if (JRequest::getVar('task') === 'editpayment' || JRequest::getVar('task') === 'edit_shipment') {
				    			$this->redirectToCart_byPV();
				    		}
				    		
			    			$this->loadLanguage_byPV();
			    			
			    			if (self::detectExtension_byPV('vm_privacy'))
			    			{
				    			if (in_array($this->initTask, array('checkout', 'confirm')) && $this->initTask !== JRequest::getCmd('task'))
				    			{
					    			JRequest::setVar('task', $this->initTask);
					    			JRequest::setVar('returnToCart', TRUE);
				    			}
			    			}
			    			
			    			$this->pluginProcessed = TRUE;
			    			
			    			self::$requestFormat = JFactory::getApplication()->input->get('format', 'html', 'word');
			    			
			    			if (self::$requestFormat === 'json')
			    			{
			    				if (isset(JFactory::getDocument()->helix))
				    			{
				    				$helix = JFactory::getDocument()->helix;
				    			}
				    			
				    			JFactory::getApplication()->input->set('format', 'html');
				    			JFactory::$document = NULL;
				    			
				    			if (isset($helix))
				    			{
				    				JFactory::getDocument()->helix = $helix;
				    			}
			    			}
		    			}
		    			break;
	
		    		// View User
	    			case 'user':
	    				if (JRequest::getCmd('task') === 'editaddresscheckout') {
	    					$this->redirectToCart_byPV();
	    				}
							// Save Shipping Address
	    				elseif (JRequest::getCmd('task') === 'saveUser') {
								require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'cart_bypv.php');
								
								$cart_bypv = VirtueMartCart_byPV::getCart();
								$cart_bypv->emptyCart();
	    				}
	    				break;
	    		}
	
	    	}
    	}
    }
    
    public static $requestFormat = NULL;
    public static $processOnlyMessages = FALSE;
    public static $jsScript = array();
    
    public function onAfterRender()
    {
    	if ($this->pluginProcessed !== TRUE) return;

    	$app = JFactory::getApplication();
    	$document = JFactory::getDocument();
    	 
    	if (self::$requestFormat === 'json')
    	{
    		if (isset($document->_script['text/javascript']))
    		{
    			$jsLatestScript = $document->_script['text/javascript'];
    		}
    		
	    	$app->input->set('format', self::$requestFormat);
	    	JFactory::$document = NULL;
    	
	    	if (version_compare(JVERSION, '3.0.0', '>='))
	    	{
		    	$app->loadDocument();
	    	}
	    	
	    	$document = JFactory::getDocument();
	    	
	    	$document->setName('response');
	    	$document->render(FALSE);
    	}
    	 
    	// Final processing of output (clean HTML, convert to JSON, etc.)

    	$html = JResponse::getBody(FALSE);

    	$blockContents = array();
		$blockChecksums = array();
		
		if (!class_exists('VirtueMartViewCart_byPV'))
		{
			require(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'views' . DS . 'cart_bypv' . DS . 'view.html.php');
		}
    	
    	preg_match('/' . sprintf(VirtueMartViewCart_byPV::FORM_COVER, '(.*)') . '/ms', $html, $matches);
    	
    	if (!empty($matches))
    	{
	    	$cartHtml = $matches[1];
	    	 
	    	preg_match_all('/#BLOCK_BYPV-([^-]+)-BEGIN#(.*)#BLOCK_BYPV-(\\1)-END#/ms', $cartHtml, $matches, PREG_SET_ORDER);
	    	
	    	foreach ($matches as $i => $match)
	    	{
	    		$form_tpl = $match[1];
	    		
	    		$blockContents[$form_tpl] = $match[2];
	    		$blockChecksums[$form_tpl] = md5(preg_replace(array('/checked="checked"/' ,'/\s/'), '', $blockContents[$form_tpl]));
	    	}
	
			$formChecksum = base64_encode(json_encode($blockChecksums));
    	}
    	else
    	{
    		$formChecksum = '';
    	}
    	
    	if (self::$requestFormat === 'html')
    	{
    		$html = preg_replace(
    			array(
    				'/#FORM_BYPV-(?:BEGIN|END)#/',
    				'/#BLOCK_BYPV-[^-]+-(?:BEGIN|END)#/',
    				'/###FORM_CHECKSUM_BYPV###/'
    			),
    			array(
    				'',
    				'',
    				$formChecksum
    			),
    			$html
    		);

    		JResponse::setBody($html);
    	}
    	elseif (self::$requestFormat === 'json')
    	{
    		$responseData = new stdClass();
    		
    		// Simple fix for Joomla/VM Bug: Joomla replaces first occurence of character "-" to ":" in all query parameters and VM doesn't replace this character back if VM SEO is disabled.
    		$cartLang = str_replace(':', '-', $app->input->getString('cart_lang'));
 
    		if (!empty($cartLang) && $cartLang !== JFactory::getLanguage()->getTag()
    			// Login action + succesfully loged (@since Joomla! 3.9 due to possible enabled privacy feature)
    			|| $app->input->post->getCmd('task') === 'loginJS_byPV' && JFactory::getUser()->id > 0
    		)
    		{
    			$responseData = new stdClass();
    		
    			$responseData->evalOtherJS = array(
    					"VirtueMartCart_byPV.refreshCartData('" . JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE) . "')",
    			);
    		
    			// Messages are not saved at this time...
    			JFactory::getSession()->set('application.queue', $app->getMessageQueue());
    		}
    		else
    		{
    			if (self::$processOnlyMessages !== TRUE)
    			{
		    		$responseData->evalOtherJS = array();
		    		
		    		if (isset(self::$jsScript['__init']))
		    		{
		    			$responseData->evalOtherJS = array_merge($responseData->evalOtherJS, self::$jsScript['__init']);
		    		}
		    		
		    		if (!empty($cartHtml))
		    		{
			    		$responseData->replaceHTML = array();
			    		
			    		if (
			    			empty($blockContents)
			    			||
			    			(
			    				count($blockContents) == 2
			    				&&
			    				isset($blockContents[VirtueMartViewCart_byPV::TPL_CHANGE_SHOPPER])
			    				&&
			    				isset($blockContents[VirtueMartViewCart_byPV::TPL_CHANGE_SHOPPER_GROUP])
			    			)
			    		)
			    		{
			    			$cartHtml = preg_replace('/#BLOCK_BYPV-[^-]+-(?:BEGIN|END)#/', '', $cartHtml);
			    			$responseData->replaceHTML['form'] = $cartHtml;
			    		}
			    		else
			    		{
				    		$skipBlocks = (array) $app->input->get('skip_blocks', array(), 'array');
				    		$forceBlocks = (array) $app->input->get('force_blocks', array(), 'array');
		
				    		$blockChecksumsOld = $app->input->getString('bypv_form_checksum');
				    		$blockChecksumsOld = json_decode(base64_decode($blockChecksumsOld), TRUE);
				    		
				    		if (!is_array($blockChecksumsOld)) {
				    			$blockChecksumsOld = array();
				    		}
				    		
				    		foreach ($blockContents as $form_tpl => $content)
				    		{
				    			if (
				    				in_array($form_tpl, $forceBlocks)
				    				||
				    				!in_array($form_tpl, $skipBlocks)
				    				&&
				    				(
					    				!isset($blockChecksumsOld[$form_tpl])
					    				||
					    				$blockChecksumsOld[$form_tpl] !== $blockChecksums[$form_tpl]
				    				)
				    			)
				    			{
				    				$responseData->replaceHTML[$form_tpl] = $content;
				    				
				    				if (!empty(self::$jsScript[$form_tpl]))
				    				{
				    					$responseData->evalOtherJS = array_merge($responseData->evalOtherJS, self::$jsScript[$form_tpl]);
				    				}
				    			}
				    		}
				    		
				    		if (
				    			!empty(self::$jsScript['__fields'])
				    			&&
				    			(
					    			isset($responseData->replaceHTML[VirtueMartViewCart_byPV::TPL_BILLING_ADDRESS])
					    			|| isset($responseData->replaceHTML[VirtueMartViewCart_byPV::TPL_SHIPPING_ADDRESS])
					    			|| isset($responseData->replaceHTML[VirtueMartViewCart_byPV::TPL_CART_FIELDS])
					    		)
				    		)
				    		{
		    					$responseData->evalOtherJS = array_merge($responseData->evalOtherJS, self::$jsScript['__fields']);
				    		}
			    		}
			    		
			    		$responseData->formChecksum = $formChecksum;
		    		}
		    		
		    		if (isset($jsLatestScript))
		    		{
		    			$responseData->evalOtherJS[] = $jsLatestScript;
		    		}
    			}

					$message_queue = $app->getMessageQueue();

					if (!empty($message_queue))
					{
						$responseData->systemMessage = array();

						while ($msg = array_pop($message_queue))
						{
							$type = $msg['type'];

							if (!isset($responseData->systemMessage[$type]))
								$responseData->systemMessage[$type] = array();

							$responseData->systemMessage[$type][] = $msg['message'];
						}
					}
				}

				JResponse::setBody(json_encode($responseData));
			}
    	
    	// Disable cache
    	
    	if (version_compare(JVERSION, '3.0.0', '>=')) return;

    	JResponse::allowCache(TRUE);
    	 
    	// Override all Content-Type
    	JResponse::setHeader('Content-Type', $document->_mime . ($document->_charset ? '; charset=' . $document->_charset : ''), TRUE);
    	// Expires in the past.
    	JResponse::setHeader('Expires', 'Mon, 1 Jan 2001 00:00:00 GMT', TRUE);
    	// Always modified.
    	JResponse::setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', TRUE);
    	JResponse::setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', TRUE);
    	// HTTP 1.0
    	JResponse::setHeader('Pragma', 'no-cache', TRUE);
    }
    
    /**
     * @param	array		$user		Holds the new user data.
	 * @param	boolean		$isnew		True if a new user is stored.
	 * @param	boolean		$success	True if user was succesfully stored in the database.
	 * @param	string		$msg		Message.
     */
    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
    	$app = JFactory::getApplication();
    	
    	if ($app->isSite() && !$isnew && $success)
    	{
    		// Load VmConfig
    		if (!class_exists('VmConfig')) {
    			require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
    		}
    		VmConfig::loadConfig();
    		
    		// Fix for older VM with undefined constant
    		if (!defined('VM_VERSION'))
    		{
    			define('VM_VERSION', (version_compare(vmVersion::$RELEASE, '2.9.0', '>=') ? 3 : 2));
    		}
    		 
    		require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'cart_bypv.php');
    		
    		$cart_bypv = VirtueMartCart_byPV::getCart();
    		
    		$dataBT = $cart_bypv->getUserFieldsData(VirtueMartCart_byPV::UFT_BILLING_ADDRESS);
    		
    		$dataBT['name'] = $user['name'];
    		$dataBT['email'] = $user['email'];
    		
    		$cart_bypv->setUserFieldsData(VirtueMartCart_byPV::UFT_BILLING_ADDRESS, $dataBT);
    		
    		$cart_bypv->setCartIntoSession(TRUE);
    	}
    }
    
    // Save User Account
    public function plgVmAfterUserStore($data)
    {
    	if (JRequest::getCmd('task') === 'saveUser')
    	{
    		require_once(OPC_FOR_VM_BYPV_PLUGIN_PATH . DS . 'helpers' . DS . 'cart_bypv.php');
    		
    		$cart_bypv = VirtueMartCart_byPV::getCart();
    		$cart_bypv->emptyCart();
    	}

    	return $data;
    }

    public static function saveScriptsJS($form_tpl = NULL)
    {
    	if (self::$requestFormat !== 'json') return;
    	 
    	$scripts = array();
    	 
    	// Fix for non-standard inserting of JS Sripts in the VM3 (since VM 2.9.9c)
    	if (VM_VERSION == 3 && method_exists('vmJsApi', 'getJScripts'))
    	{
    		$vmJS = vmJsApi::getJScripts();
    		 
    		if (!empty($vmJS)) foreach($vmJS as $name => $jsToAdd)
    		{
    			if (!empty($jsToAdd['script']) && (strpos($jsToAdd['script'],'/') !== 0 || strpos($jsToAdd['script'],'//<![CDATA[') === 0))
    			{
    				$script = trim($jsToAdd['script']);
    				 
    				if (!empty($script))
    				{
    					$scripts[] = $script;
    				}
    			}
    				
    			vmJsApi::removeJScript($name);
    		}
    	}
    	 
    	$document = JFactory::getDocument();
    	 
    	if (!empty($document->_script['text/javascript']))
    	{
    		$scripts[] = $document->_script['text/javascript'];
    		unset($document->_script['text/javascript']);
    	}
    	 
    	if (!empty($form_tpl) && !empty($scripts))
    	{
    		$jsScript =& self::$jsScript[$form_tpl];
    
    		if (empty($jsScript)) $jsScript = array();
    		 
    		$jsScript = array_merge($jsScript, $scripts);
    	}
    }
    
    /*** RULES FOR ROUTER ***/

    /**
     * Parse rule for router.
     *
     * @param   JRouter  &$router  JRouter object.
     * @param   JUri     &$uri     JUri object.
     *
     * @return  array
     */
    public function parseRule(&$router, &$uri)
    {
    	$path = $uri->getPath();

    	// Remove the suffix
    	if (JFactory::getConfig()->get('sef_suffix'))
    	{
    		if ($suffix = pathinfo($path, PATHINFO_EXTENSION))
    		{
    			$path = str_replace('.' . $suffix, '', $path);
    		}
    	}
    	
    	$path = explode('/', $path);
			
			if (version_compare(JVERSION, '3.8', '>='))
			{
				$stepDelimiter = ':';
			}
			else
			{
				$stepDelimiter = '-';
			}
    	
    	foreach (array('confirmation', 'ordered') as $step)
    	{
    		$stepPart = $this->translateStepValue_byPV('step') . $stepDelimiter . $this->translateStepValue_byPV($step);

    		$key = array_search($stepPart, $path);
    
    		if ($key !== FALSE)
    		{
    			unset($path[$key]);
    			$uri->setPath(implode('/', $path));
    
    			return array('step' => $step);
    		}
    	}
    
    	return array();
    }
    
    /**
     * Build rule for router.
     *
     * @param   JRouter  &$router  JRouter object.
     * @param   JUri     &$uri     JUri object.
     *
     * @return  void
     *
     * @since   3.4
     */
    public function buildRule(&$router, &$uri)
    {
    	if (
    		$uri->getVar('option') === 'com_virtuemart' && $uri->getVar('view') === 'cart'
    		&& in_array($uri->getVar('step'), array('confirmation', 'ordered'))
    	)
    	{
    		$uri->setVar('task', $this->translateStepValue_byPV('step') . '-' . $this->translateStepValue_byPV($uri->getVar('step')));
    		$uri->delVar('step');
    	}
    }
    
    public static function detectExtension_byPV($name)
    {
    	static $cache = array();
    	
    	if (!isset($cache[$name]))
    	{
    		if (!self::isPluginParamEnabled('enable_patch_for_' . $name))
    		{
    			$cache[$name] = FALSE;
    		}
    		else
    		{
		    	$components = array();
		    	$plugins = array();
	
		    	switch ($name)
		    	{
		    		case 'vm_privacy':
		    			
		    			$plugins[] = 'system/vmprivacy';
		    			break;
		    
		    		case 'vm_bonus':
		    			$components[] = 'com_vm_bonus';
		    			$plugins[] = 'system/bonus';
		    			break;
		    
		    		default:
		    			return FALSE;
		    	}
		    		
		    	$db = JFactory::getDbo();
		    
		    	foreach ($components as $element)
		    	{
		    		$where[] = '(`type` = ' . $db->quote('component') . 'AND `element` = ' . $db->quote($element) . ')';
		    	}
		    
		    	foreach ($plugins as $plugin)
		    	{
		    		list($folder, $element) = explode('/', $plugin);
		    		$where[] = '(`type` = ' . $db->quote('plugin') . 'AND `folder` = ' . $db->quote($folder) . ' AND `element` = ' . $db->quote($element) . ')';
		    	}
		    
		    	$db->setQuery($db->getQuery(true)
		    			->select('COUNT(`extension_id`)')
		    			->from('#__extensions')
		    			->where('(' . implode(' OR ', $where) . ')')
		    			->where('`enabled` = 1')
		    	);
		    
		    	$cache[$name] = (((int) $db->loadResult()) === (count($components) + count($plugins)));
    		}
    	}
    	
    	return $cache[$name];
    }
    
    /*** PRIVATE METHODS ***/
    
    private function translateStepValue_byPV($key)
    {
    	static $vmCfgSeoTranslate = NULL;
    	
    	if ($vmCfgSeoTranslate === NULL)
    	{
			if (!class_exists('VmConfig')) {
				require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
		    	VmConfig::loadConfig();
			}
	    	
	    	$vmCfgSeoTranslate = (VmConfig::get('seo_translate', 0) == 1);
	    	
	    	$this->loadLanguage_byPV();
    	}

    	if ($vmCfgSeoTranslate)
    	{
    		$const = 'PLG_SYSTEM_OPC_FOR_VM_BYPV_SEF_' . $key;
    		
    		$value = JText::_($const);
    		if ($value === $const) unset($value);
    		else $value = JFilterInput::getInstance()->clean($value, 'cmd');
    	}
    	
    	return (empty($value) ? $key : $value);
    }
    
    private function initDemoModule_byPV()
    {
    	if (!class_exists('modDemoOPCforVMbyPVHelper', FALSE))
    	{
    		$helper_path = JPATH_BASE . DS . 'modules' . DS . self::DEMO_MODULE . DS . 'helper.php';
    	
    		if (is_file($helper_path))
    		{
    			require_once($helper_path);
    		}
    	}
    	 
    	return class_exists('modDemoOPCforVMbyPVHelper', FALSE);
	}
    
    private function redirectToCart_byPV()
    {
    	$app = JFactory::getApplication();
    	$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE));
    }
    
    const DEFAULT_LANGUAGE = 'en-GB';
    
    private function loadLanguage_byPV()
    {
    	// only once
    	static $loadedLanguage = NULL;
    	
    	$lang = JFactory::getLanguage();
    	
    	if ($loadedLanguage === $lang->getTag())
    	{
    		return;
    	}
    	else
    	{
    		$loadedLanguage = $lang->getTag();
    	}
    	
    	$extension = strtolower('plg_' . $this->_type . '_' . $this->_name);

    	// Load default (en-GB) language
    	$lang->load($extension, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name, self::DEFAULT_LANGUAGE, TRUE, FALSE);
    	// Load default (en-GB) override language (from /languages)
    	$lang->load($extension, JPATH_SITE, self::DEFAULT_LANGUAGE, TRUE, FALSE);

    	if ($loadedLanguage !== self::DEFAULT_LANGUAGE)
    	{
	    	// Load chosen language
	    	$lang->load($extension, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name, null, TRUE, FALSE);
	    	// Load chosen override language (from /languages)
	    	$lang->load($extension, JPATH_SITE, null, TRUE, FALSE);
    	}
    }
    
    public static function isVmVersion($version)
    {
    	return version_compare(vmVersion::$RELEASE, $version, '>=');
    }

    public static function debugJS_byPV($text, $args = NULL)
    {
    	static $loggerCreated = FALSE;
    
    	if ($loggerCreated === FALSE)
    	{
    		JLog::addLogger(
    			array(
    				'text_file' => 'plg_system_opc_for_vm_bypv.log.php'
    			)
    			, JLog::ALL
    			, array('plg_system_opc_for_vm_bypv')
    		);
    			
    		$loggerCreated = TRUE;
    	}
    
    	$args = func_get_args();
    	array_shift($args);
    
    	foreach ($args as &$arg)
    	{
    		if (!is_scalar($arg)) $arg = json_encode($arg);
    	}
    
    	JLog::add(
	    	vsprintf($text, $args),
	    	JLog::DEBUG, 'plg_system_opc_for_vm_bypv'
    	);
    }
}