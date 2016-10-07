<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Form;


class Age extends \Ferienpass\Widget\Age
{

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_fpage';


	/**
	 * Error message
	 *
	 * @var string
	 */
	protected $strError = '';


	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-age';

}
