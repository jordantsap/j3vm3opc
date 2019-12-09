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
 
if (!class_exists('VirtueMartControllerPluginresponse')) require(JPATH_VM_SITE . DS . 'controllers' . DS . 'pluginresponse.php');

class VirtueMartControllerPluginresponse_byPV extends VirtueMartControllerPluginresponse
{
	/*** OVERRIDE ***/
	
	public function __construct($config = array())
	{
		$config['base_path'] = OPC_FOR_VM_BYPV_PLUGIN_PATH;
		parent::__construct($config);

		// HACK: Because VirtueMartControllerPluginresponse::__construct() is not same as JController::__construct() 
		$this->basePath = OPC_FOR_VM_BYPV_PLUGIN_PATH;
		$this->setPath('view', $this->basePath . '/views');
	}
	
	public function getView($name = '', $type = '', $prefix = '', $config = array())
	{
		if (in_array($name, array('cart', 'pluginresponse'))) {
			$name .= '_bypv';
		}
		
		return parent::getView($name, $type, $prefix, $config);
	}
}