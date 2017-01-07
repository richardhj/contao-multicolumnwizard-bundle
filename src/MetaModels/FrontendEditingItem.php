<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace MetaModels;


/**
 * Class FrontendEditingItem
 * @package MetaModels
 */
class FrontendEditingItem extends Item
{

	/**
	 * Override this function as we want to make sure that all attributes get notified by saving the item.
	 * 
	 * @param string $strAttributeName
	 *
	 * @return true
	 */
	public function isAttributeSet($strAttributeName)
	{
		return true;
	}
}
