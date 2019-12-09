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

JFormHelper::loadFieldClass('list');

class JFormFieldVMShopperFields extends JFormFieldList
{
	/**
	 * The field type.
	 *
	 * @var string
	 */
	protected $type = 'VMShopperFields';

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		static $stylesDefined = FALSE;
		
		// Styles for the configuration of the plugin
		// TODO: Create a new field for this...
		if ($stylesDefined === FALSE)
		{
			if (version_compare(JVERSION, '3.0.0', '>='))
			{
				JFactory::getDocument()->addStyleDeclaration(
					'.form-horizontal .control-label { max-width: 170px; }' .
					'.form-horizontal .controls .editor iframe { max-height: 15em; }'
				);
			}
			else
			{
				if (!class_exists('VmConfig')) {
					require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
				}
				
				// Fix for older VM with undefined constant
				if (!defined('VM_VERSION'))
				{
					define('VM_VERSION', (version_compare(vmVersion::$RELEASE, '2.9.0', '>=') ? 3 : 2));
				}
				
				if (VM_VERSION === 3)
				{
					vmJsApi::jQuery(TRUE);
					vmJsApi::chosenDropDowns();
				}
				else
				{
					vmJsApi::js('jquery', FALSE, '', TRUE);
					vmJsApi::js('jquery.noConflict');
					vmJsApi::js('chosen.jquery.min');
				}
				
				if (method_exists('vmJsApi', 'writeJS'))
				{
					vmJsApi::writeJS();
				}

				vmJsApi::css('chosen');
				
				// Chosen settings
				$options = array();
				$options['disable_search_threshold'] = 10;
				$options['select_some_options_text'] = JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_SELECT_SOME_OPTIONS');
// 				$options['placeholder_text_multiple'] = isset($options['placeholder_text_multiple']) ? $options['placeholder_text_multiple']: JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_SELECT_SOME_OPTIONS');
				$options['no_results_text'] = JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_SELECT_NO_RESULTS_MATCH');
				
				// Options array to json options string
				$options_str = json_encode($options);
				
				JFactory::getDocument()->addScriptDeclaration(
<<<JS
	jQuery(document).ready(function () {
		jQuery('select[multiple]').chosen($options_str).change(function() { $(this.id + '_chzn').removeClass('chzn-container-active'); } );
	});
JS
				);
				
				JFactory::getDocument()->addStyleDeclaration(
					'fieldset .chzn-container.chzn-container-multi { float: left; width: 50% !important; }' .
					'fieldset .chzn-container.chzn-container-multi .chzn-drop { width: 100% !important; }' .
					'fieldset .chzn-container.chzn-container-multi .chzn-drop .chzn-results { max-height: 120px; }' .
					'fieldset .chzn-container.chzn-container-multi.chzn-container-active .chzn-drop { position: relative; top: 1px !important; }' .
					'fieldset .chzn-container-multi .chzn-choices { background: #fff; border: 1px solid silver; }' .
					'fieldset .chzn-container-multi .chzn-choices .search-choice { border-radius: 0; color: inherit; font-size: 0.909em; border: 1px solid silver; background: #fff; }' .
					'fieldset .chzn-container-multi .chzn-choices .search-field input.default { width: auto !important; }' .
					'div.pane-slider.content { overflow: hidden !important; }' .
					'fieldset.panelform label { max-width: 145px; }'
				);
			}
			
			$stylesDefined = TRUE;
		}
		
		if ($this->value === (string) $this->element['default'])
		{
			$this->value = explode('|', (string) $this->element['default']);
		}
		
		return
			'<input type="hidden" name="' . $this->name . '" value="" />'
			.
			parent::getInput();
	}

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return array An array of JHtml options.
	 */
	protected function getOptions()
	{
		if (!class_exists('VmConfig')) {
			require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
		}
		
		if (!class_exists('VmModel'))
		{
			require(JPATH_VM_ADMINISTRATOR . '/helpers/vmmodel.php');
		}

		if (VM_VERSION < 3)
		{
			vmLanguage::loadJLang('com_virtuemart_shoppers', TRUE);
		}

		vmLanguage::loadJLang('com_virtuemart', FALSE);

		$userFieldsModel = VmModel::getModel('userfields');
		$userFields = $userFieldsModel->getUserFields('', array('published' => TRUE, 'delimiters' => TRUE), array('address_type'));
		
		$options = array();
		
		foreach ($userFields as $userField)
		{
			$options[] = JHtml::_('select.option', $userField->name, JText::_($userField->title));
		}
		
		$options = array_merge(parent::getOptions(), $options);
		
		return $options;
	}
}