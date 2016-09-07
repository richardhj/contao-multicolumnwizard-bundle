<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;


/**
 * Class Offer
 * @package Ferienpass\Module\SingleOffer
 */
abstract class Item extends Items
{

	/**
	 * Provide MetaModel item in object
	 *
	 * @param bool $blnIsProtected Do permission check if true
	 *
	 * @return string
	 */
	public function generate($blnIsProtected=false)
	{
		$this->fetchItem();

		if (TL_MODE == 'FE')
		{
			// Generate 404 if item not found
			if ($this->objItem === null)
			{
				$this->exitWith404();
			}

			// Check permission
			if ($blnIsProtected)
			{
				$this->checkPermission();
			}
		}

		return parent::generate();
	}
}
