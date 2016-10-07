<?php

namespace Ferienpass\Helper;

use Ferienpass\Helper\Config as FerienpassConfig;


class InsertTags
{
	/**
	 * @param string $tag
	 *
	 * @return string|false
	 */
	public function replaceInsertTags($tag)
	{
		$elements = trimsplit('::', $tag);

		if ($elements[0] == 'ferienpass')
		{
			switch ($elements[1])
			{
				case 'max_applications_per_day':
					return FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MAX_APPLICATIONS_PER_DAY);
				break;
			}
		}

		return false;
	}
}
