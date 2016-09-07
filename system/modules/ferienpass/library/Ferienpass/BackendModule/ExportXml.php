<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\BackendModule;

use Contao\SelectMenu;
use Contao\System;


class ExportXml extends \BackendModule
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_fp_exportXml';


	/**
	 * Generate the module
	 * @return string
	 */
	public function generate()
	{
		System::loadLanguageFile('tl_ferienpass_exportXml');

		if (!\BackendUser::getInstance()->isAdmin) //@todo
		{
			return '<p class="tl_gerror">' . $GLOBALS['TL_LANG']['tl_ferienpass_exportXml']['permission'] . '</p>';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		//@todo select menu with order labels (red, blue, green) as options
		$objWidget = new SelectMenu($this->prepareForWidget($GLOBALS['TL_DCA']['tl_iso_orders']['fields']['recipient_select'], 'recipient_select'));



		$this->Template->action = \Environment::get('request');
		$this->Template->back = str_replace('&mod=exportXml', '', \Environment::get('request'));
	}
}
