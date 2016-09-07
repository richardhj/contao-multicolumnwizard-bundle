<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;

use Ferienpass\Model\DataProcessing;

class Ajax
{

	public function handleDropboxWebhook()
	{
		if (!\Environment::get('isAjaxRequest') || \Input::get('action') != 'dropbox-webhook')
		{
			return;
		}

		/** @type \Model\Collection $objProcessings */
		$objProcessings = DataProcessing::findBy
		(
			array
			(
				'dropbox_uid=?',
				'sync=1'
			),
			array
			(
				\Input::get('uid')
			)
		);

		while ($objProcessings->next())
		{
			/** @var DataProcessing $objProcessings ->current() */
			$objProcessings->current()->syncFromRemoteDropbox();
		}
	}
}
