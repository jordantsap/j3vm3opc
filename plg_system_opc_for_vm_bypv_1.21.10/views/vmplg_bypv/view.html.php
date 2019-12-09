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

if (!class_exists('VirtueMartViewVmplg')) require(VMPATH_SITE . DS . 'views' . DS . 'vmplg' . DS . 'view.html.php');

class VirtueMartViewVmplg_byPV extends VirtueMartViewVmplg
{
	/*** OVERRIDE ***/
	
	public function __construct($config = array())
	{
		$config['base_path'] = OPC_FOR_VM_BYPV_PLUGIN_PATH;
	
		parent::__construct($config);
	
		// Preserve VirtueMart Cart path for non-checkout templates.
		$app = JFactory::getApplication();
		$this->_path['template'][] = JPATH_THEMES . DS . $app->getTemplate() . DS . 'html' . DS . 'com_virtuemart' . DS . 'vmplg';
		$this->_path['template'][] = VMPATH_SITE . DS . 'views' . DS . 'vmplg' . DS . 'tmpl';
	}
}

