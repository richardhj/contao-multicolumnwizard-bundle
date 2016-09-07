<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Helper;

use Contao\Controller;


/**
 * Class Message
 * @package Ferienpass\Helper
 */
class Message extends Controller
{
	/**
	 * Add an error message
	 *
	 * @param string $strMessage The error message
	 */
	public static function addError($strMessage)
	{
		static::add($strMessage, 'FP_ERROR');
	}


	/**
	 * Add a confirmation message
	 *
	 * @param string $strMessage The confirmation message
	 */
	public static function addConfirmation($strMessage)
	{
		static::add($strMessage, 'FP_CONFIRMATION');
	}


	/**
	 * Add a warning message
	 *
	 * @param string $strMessage The warning message
	 */
	public static function addWarning($strMessage)
	{
		static::add($strMessage, 'FP_WARNING');
	}


	/**
	 * Add an info message
	 *
	 * @param string $strMessage The info message
	 */
	public static function addInformation($strMessage)
	{
		static::add($strMessage, 'FP_INFORMATION');
	}


	/**
	 * Add a preformatted message
	 *
	 * @param string $strMessage The preformatted message
	 */
	public static function addRaw($strMessage)
	{
		static::add($strMessage, 'FP_RAW');
	}


	/**
	 * Add a message
	 *
	 * @param string $strMessage The message text
	 * @param string $strType    The message type
	 *
	 * @throws \Exception If $strType is not a valid message type
	 */
	public static function add($strMessage, $strType)
	{
		if ($strMessage == '')
		{
			return;
		}

		if (!in_array($strType, static::getTypes()))
		{
			throw new \Exception("Invalid message type $strType");
		}

		if (!is_array($_SESSION[$strType]))
		{
			$_SESSION[$strType] = array();
		}

		$_SESSION[$strType][] = $strMessage;
	}


	/**
	 * Return all messages as HTML
	 *
	 * @param boolean $blnNoWrapper If true, there will be no wrapping DIV
	 *
	 * @return string The messages HTML markup
	 */
	public static function generate($blnNoWrapper=false)
	{
		$strMessages = '';

		// Regular messages
		foreach (static::getTypes() as $strType)
		{
			if (!is_array($_SESSION[$strType]))
			{
				continue;
			}

			$strClass = strtolower(substr($strType, 3)); // Remove prefix
			$_SESSION[$strType] = array_unique($_SESSION[$strType]);

			foreach ($_SESSION[$strType] as $strMessage)
			{
				if ($strType == 'TL_RAW')
				{
					$strMessages .= $strMessage;
				}
				else
				{
					$strMessages .= sprintf('<p class="%s">%s</p>%s', $strClass, $strMessage, "\n");
				}
			}

			if (!$_POST)
			{
				$_SESSION[$strType] = array();
			}
		}

		$strMessages = trim($strMessages);

		// Wrapping container
		if (!$blnNoWrapper && $strMessages != '')
		{
			$strMessages = sprintf('<div class="messages">%s%s%s</div>', "\n", $strMessages, "\n");
		}

		return $strMessages;
	}


	/**
	 * Reset the message system
	 */
	public static function reset()
	{
		foreach (static::getTypes() as $strType)
		{
			$_SESSION[$strType] = array();
		}
	}


	/**
	 * Return all available message types
	 *
	 * @return array An array of message types
	 */
	public static function getTypes()
	{
		return array('FP_ERROR', 'FP_CONFIRMATION', 'FP_WARNING', 'FP_INFORMATION', 'FP_RAW');
	}
}
