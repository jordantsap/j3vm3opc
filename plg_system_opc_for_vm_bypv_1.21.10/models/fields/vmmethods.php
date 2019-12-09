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

class JFormFieldVMMethods extends JFormFieldList
{
	/**
	 * The field type.
	 *
	 * @var string
	 */
	protected $type = 'VMMethods';
	
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
		VmConfig::loadConfig();
		
		if (!class_exists('VmModel'))
		{
			require(JPATH_VM_ADMINISTRATOR . '/helpers/vmmodel.php');
		}

		$methodType = (string) $this->element['methodtype'];
		
		switch ($methodType)
		{
			case 'shipment':
				$methodModel = VmModel::getModel('shipmentmethod');
				$methods = $methodModel->getShipments();
				
				$methodPropId = 'virtuemart_shipmentmethod_id';
				$methodPropName = 'shipment_name';
				break;

			case 'payment':
				$methodModel = VmModel::getModel('paymentmethod');
				$methods = $methodModel->getPayments();
				
				$methodPropId = 'virtuemart_paymentmethod_id';
				$methodPropName = 'payment_name';
				break;
				
			default:
				return NULL;
		}
		
		$options = array();
		
		foreach ($methods as $method)
		{
			$options[] = JHtml::_('select.option', $method->$methodPropId, $method->$methodPropName);
		}
		
		$options = array_merge(parent::getOptions(), $options);
		
		return $options;
	}
}