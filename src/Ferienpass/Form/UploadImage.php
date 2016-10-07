<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 *
 * Ferienpass Web Interface
 *
 * @copyright Richard Henkenjohann 2015
 * @author    Richard Henkenjohann <richard@henkenjohann.me>
 */


namespace Ferienpass\Form;

/**
 * Class UploadImage
 *
 * @copyright  Richard Henkenjohann 2015
 * @author     Richard Henkenjohann <richard@henkenjohann.me>
 */
class UploadImage extends \UploadPreviewFieldFE
{

	/**
	 * Set upload folder for attribute
	 *
	 * @param array|null $attributes
	 */
	public function __construct($attributes=null)
	{
		parent::__construct
		(
			array_merge
			(
				[
					'uploadFolder' => '7a56a80f-f055-11e4-b330-ce2f81f95ce0', //@todo
					'renameFile'   => 'angebot_##id##_' . substr(md5(uniqid(mt_rand())), 0, 6) //@todo
                ],
				(array)$attributes
			)
		);
	}
}  
