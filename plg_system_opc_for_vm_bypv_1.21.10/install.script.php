<?php defined('_JEXEC') or die('Restricted access');

/**
 * Install script for Joomla! CMS to clean update sites from byPV.org
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

class plgSystemOPC_for_VM_byPVInstallerScript
{
	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	*/
	public function update(JAdapterInstance $adapter)
	{
		$manifest = $adapter->getParent()->getManifest();
		
		$updateservers	= $manifest->updateservers;
		
		if ($manifest->updateservers && $manifest->updateservers->server)
		{
			// Our extensions have always only one UpdateServer.
			$this->fixUpdateSite($adapter, $manifest->updateservers->server[0]);
		}
		else
		{
			// Drop UpdateSites
			$this->fixUpdateSite($adapter, NULL);
		}
		
		return TRUE;
	}

	/**
	 * Get ID of extension by Manifest values 
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	private function getExtensionId(JAdapterInstance $adapter)
	{
		$manifest = $adapter->getParent()->getManifest();
	
		// Element
	
		$type = (string) $manifest->attributes()->type;
	
		if (count($manifest->files->children()))
		{
			foreach ($manifest->files->children() as $file)
			{
				if ((string) $file->attributes()->$type)
				{
					$element = (string) $file->attributes()->$type;
					break;
				}
			}
		}
	
		// Group
	
		$group = (string) $manifest->attributes()->group;
	
		// Extension ID
	
		$dbo = JFactory::getDBO();
		$query = $dbo->getQuery(true);
	
		$query
			->select($query->quoteName('extension_id'))
				->from($query->quoteName('#__extensions'))
				->where($query->quoteName('folder') . ' = ' . $query->quote($group))
				->where($query->quoteName('element') . ' = ' . $query->quote($element));
	
		$dbo->setQuery($query);
	
		return $dbo->loadResult();
	}
	
	/**
	 * Fix an update site
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 * @param	string	The URI for the site
	 */
	private function fixUpdateSite($adapter, $server)
	{
		$extension_id = $this->getExtensionId($adapter);
		
		if (empty($extension_id)) return TRUE;
		
		$dbo = JFactory::getDBO();
		$query = $dbo->getQuery(true);

		// Select UpdateSites assigned to this Extension.
		
		$query
			->select($query->quoteName('update_site_id'))
				->from($query->quoteName('#__update_sites_extensions'))
				->where($query->quoteName('extension_id') .' = '. $query->quote($extension_id));

		$update_site_ids = $dbo->setQuery($query)->loadColumn();

		if (empty($update_site_ids)) return TRUE;

		// Update the ONE UpdateSite assigned to this Extension.
		
		if ($server instanceof SimpleXMLElement) {
			$query->clear();
			$query
				->update($query->quoteName('#__update_sites'))
					->set($query->quoteName('name') . '=' . $query->quote($server['name']))
					->set($query->quoteName('type') . '=' . $query->quote($server['type']))
					->set($query->quoteName('location') . '=' . $query->quote($server))
					->where($query->quoteName('update_site_id') . ' = ' . $query->quote(array_pop($update_site_ids)));
			
			$dbo->setQuery($query)->execute();
		}
		
		if (!empty($update_site_ids))
		{
			// Drop others rows from #__update_sites
			
			$query->clear();
			$query
				->delete($query->quoteName('#__update_sites'))
					->where($query->quoteName('update_site_id') . ' IN (' . implode(', ', $update_site_ids) . ')');
	
			$dbo->setQuery($query)->execute();

			// Drop others rows from #__update_sites_extensions

			$query->clear();
			$query
				->delete($query->quoteName('#__update_sites_extensions'))
					->where($query->quoteName('extension_id') . '=' . $query->quote($extension_id))
					->where($query->quoteName('update_site_id') . ' IN (' . implode(', ', $update_site_ids) . ')');
	
			$dbo->setQuery($query)->execute();
		}
				
		return TRUE;
	}
	
	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		if (strtolower($route) === 'update')
		{
			$installer = $adapter->getParent();
			$manifest = $installer->getManifest();
			
			$version = (string) $manifest->version;
			$joomlaRootPath = JPATH_ROOT;
			$extensionRootPath = $installer->getPath('extension_root');
			
			$removedFiles = [
				'1.21.0' => [
					$joomlaRootPath . '/media/plg_system_opc_for_vm_bypv/css/layout_vertical_responsive.css',
				],
			];
			
			foreach ($removedFiles as $minVersion => $files)
			{
				if (version_compare($version, $minVersion, '>='))
				{
					foreach ($files as $file)
					{
						if (JFile::exists($file))
						{
							JFile::delete($file);
						}
					}
				}
			}
		}
	}
}