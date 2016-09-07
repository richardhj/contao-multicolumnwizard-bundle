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

use Contao\Module;
use Haste\Input\Input;
use MetaModels\Factory;
use MetaModels\IMetaModel;


/**
 * Class Items
 * @package  Ferienpass\Module
 * @property integer       $metamodel
 * @property \FrontendUser $User
 * @property string        $aliasColName
 */
abstract class Items extends Module
{

	/**
	 * The MetaModel object
	 *
	 * @type \MetaModels\IMetaModel
	 */
	protected $objMetaModel;


	/**
	 * The MetaModel item
	 *
	 * @type \MetaModels\IItem
	 */
	protected $objItem;


	/**
	 * The database instance
	 *
	 * @var \Database
	 */
	protected $objDatabase;


	/**
	 * The auto item
	 *
	 * @var string
	 */
	protected $strAutoItem;


	/**
	 * The owner attribute
	 *
	 * @type \MetaModels\Attribute\IAttribute|null
	 */
	protected $objOwnerAttribute;


	/**
	 * Return a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set a custom template
		if ($this->customTpl != '')
		{
			$this->strTemplate = $this->customTpl;
		}

		return parent::generate();
	}


	/**
	 * Provide MetaModel in object
	 *
	 * @param \ModuleModel $objModule
	 * @param string       $strColumn
	 */
	public function __construct($objModule, $strColumn = 'main')
	{
		parent::__construct($objModule, $strColumn);

		// Get MetaModel object
		$objFactory = Factory::getDefaultFactory();
		$this->objMetaModel = $objFactory->getMetaModel($objFactory->translateIdToMetaModelName($this->metamodel));

		// Throw exception if MetaModel not found
		if ($this->objMetaModel === null)
		{
			throw new \RuntimeException(sprintf('MetaModel ID %u not found', $this->metamodel));
		}

		// Get database
		$this->objDatabase = $this->objMetaModel->getServiceContainer()->getDatabase();

		// Import frontend user
		/** @noinspection PhpUndefinedMethodInspection */
		$this->import('FrontendUser', 'User');
	}


	/**
	 * Fetch item by Id or auto_item
	 *
	 * @param int $intId The item id
	 *
	 * @return bool True if item was found
	 */
	protected function fetchItem($intId = 0)
	{
		if (!$intId)
		{
			$this->strAutoItem = Input::getAutoItem('items');

			// Fetch alias attribute
			foreach ($this->objMetaModel->getAttributes() as $attribute)
			{
				if ($attribute->get('type') == 'alias')
				{
					$this->aliasColName = $attribute->getColName();
				}
			}

			// Fetch current item by its auto_item
			$objDatabaseItem = $this->objDatabase
				->prepare(sprintf
				(
					'SELECT * FROM %1$s WHERE (id=? OR %2$s=?)',
					$this->objMetaModel->getTableName(),
					$this->aliasColName
				))
				->execute(is_int($this->strAutoItem) ? $this->strAutoItem : 0, $this->strAutoItem);

			$intId = $objDatabaseItem->id;
		}

		$this->objItem = $this->objMetaModel->findById($intId);

		return ($this->objItem !== null);
	}


	/**
	 * Set MetaModel's owner attribute
	 *
	 * @param IMetaModel $objMetaModel The MetaModel that will be taken to find the owner attribute
	 */
	protected function fetchOwnerAttribute($objMetaModel = null)
	{
		if ($this->objOwnerAttribute !== null && $objMetaModel === null)
		{
			return;
		}

		$objMetaModel = ($objMetaModel === null) ? $this->objMetaModel : $objMetaModel;

		$this->objOwnerAttribute = $objMetaModel->getAttributeById($objMetaModel->get('owner_attribute'));

		if (null === $this->objOwnerAttribute)
		{
			throw new \RuntimeException('No owner attribute in the MetaModel was found');
		}
	}


	/**
	 * Check permission by MetaModel's owner attribute and exit with 403 optionally
	 */
	protected function checkPermission()
	{
		$this->fetchOwnerAttribute();

		$blnCallback = false;

		// HOOK: add custom permission check
		if (isset($GLOBALS['METAMODEL_HOOKS']['editingPermissionCheck']) && is_array($GLOBALS['METAMODEL_HOOKS']['editingPermissionCheck']))
		{
			foreach ($GLOBALS['METAMODEL_HOOKS']['editingPermissionCheck'] as $callback)
			{
				if (\Controller::importStatic($callback[0])->$callback[1]($this->objMetaModel, $this->objItem, $this->objOwnerAttribute, $this->strAutoItem) === true)
				{
					$blnCallback = true;
					break;
				}
			}
		}

		if (!$blnCallback && $this->User->id != $this->objItem->get($this->objOwnerAttribute->getColName())['id'])
		{
			$this->exitWith403();
		}
	}


	/**
	 * Output a 404 page and stop further execution
	 */
	protected function exitWith404()
	{
		global $objPage;

		/** @var \PageError404 $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['error_404']();
		$objHandler->generate($objPage->id);

		exit;
	}


	/**
	 * Output a 403 page and stop further execution
	 */
	protected function exitWith403()
	{
		global $objPage;

		/** @var \PageError403 $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['error_403']();
		$objHandler->generate($objPage->id);

		exit;
	}
}
