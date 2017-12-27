<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\MetaModels;

use MetaModels\Item;


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
