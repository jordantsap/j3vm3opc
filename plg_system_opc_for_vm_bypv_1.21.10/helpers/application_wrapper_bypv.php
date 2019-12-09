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
 
class ApplicationWrapper_OPC_for_VM_byPV
{
	private static $application = NULL;
	private static $application_wrapper = NULL;
	
	private static $override_methods = array();
	
	public static function attach($override_methods = array('redirect'))
	{
		self::detach();
		
		self::$application = JFactory::getApplication();
		self::$application_wrapper = new ApplicationWrapper_OPC_for_VM_byPV();
		JFactory::$application = self::$application_wrapper;
		
		self::$override_methods = $override_methods;
	}

	public static function detach()
	{
		if (is_object(self::$application))
		{
			JFactory::$application = self::$application;
			self::$application = NULL;
		}
	}
	
	private static function getApplication_byPV(&$name)
	{
		if (empty(self::$application))
		{
			$application = JFactory::getApplication();
		}
		else
		{
			if (in_array($name, self::$override_methods) && method_exists(self::$application_wrapper, $name . '_byPV'))
			{
				$application = self::$application_wrapper;
				$name .= '_byPV';
			}
			else
			{
				$application = self::$application;
			}
		}
		
		return $application;
	}

	/*** MAGIC METHODS ***/
	
	public function __call($name, $arguments)
	{
		$application = self::getApplication_byPV($name);
		return call_user_func_array(array($application, $name), $arguments);
	}
	
	// As of PHP 5.3.0
	public static function __callStatic($name, $arguments)
	{
		$application = self::getApplication_byPV($name);
		return call_user_func_array(array($application, $name), $arguments);
	}
	
	public function __set($name, $value)
	{
		$application = self::getApplication_byPV($name);
		$application->$name = $value;
	}
	
	public function __get($name)
	{
		$application = self::getApplication_byPV($name);
		return $application->$name;
	}

	/**  As of PHP 5.1.0  */
	public function __isset($name)
	{
		$application = self::getApplication_byPV($name);
		return isset($application->$name);
	}

	/**  As of PHP 5.1.0  */
	public function __unset($name)
	{
		$application = self::getApplication_byPV($name);
		unset($application->$name);
	}

	/*** byPV METHODS ***/
	
	/**
	 * Overrided method for redirect().
	 * 
	 * @param string $url
	 * @param string[optional] $msg
	 * @param string[optional] $msgType
	 * @param bool[optional] $moved
	 */
	public function redirect_byPV($url, $msg = '', $msgType = 'message', $moved = false)
	{
		throw new RedirectException_byPV($msg, $msgType);
	}

	/**
	 * Overrided method for enqueueMessage().
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  void
	 */
	public function enqueueMessage_byPV($msg, $type = 'message')
	{
	}
	
}

class RedirectException_byPV extends RuntimeException
{
	protected $message_type = NULL;
	
	public function __construct($message, $message_type)
	{
		$message = trim($message);
		if (empty($message)) $message = NULL;
		
		$this->message_type = $message_type;
		
		parent::__construct($message);
	}
	
	public function isMessage()
	{
		return !empty($this->message);
	}
	
	public function getMessageType()
	{
		return $this->message_type;
	}
}
