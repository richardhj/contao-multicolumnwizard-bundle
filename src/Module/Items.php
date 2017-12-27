<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\Module;
use Haste\Input\Input;
use MetaModels\Attribute\Select\MetaModelSelect;
use MetaModels\IMetaModel;
use MetaModels\IMetaModelsServiceContainer;


/**
 * Class Items
 * @package  Richardhj\ContaoFerienpassBundle\Module
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
    protected $metaModel;


    /**
     * The MetaModel item
     *
     * @type \MetaModels\IItem
     */
    protected $item;


    /**
     * The database instance
     *
     * @var \Database
     */
    protected $database;


    /**
     * The auto item
     *
     * @var string
     */
    protected $autoItem;


    /**
     * The owner attribute
     *
     * @type \MetaModels\Attribute\IAttribute|null
     */
    protected $ownerAttribute;


    /**
     * Provide MetaModel in object
     *
     * @param \ModuleModel $module
     * @param string       $column
     */
    public function __construct($module, $column = 'main')
    {
        global $container;
        parent::__construct($module, $column);

        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer = $container['metamodels-service-container'];

        // Get MetaModel object
        $this->metaModel = $serviceContainer
            ->getFactory()
            ->getMetaModel(
                $serviceContainer
                    ->getFactory()
                    ->translateIdToMetaModelName($this->metamodel)
            );

        // Throw exception if MetaModel not found
        if (null === $this->metaModel) {
            throw new \RuntimeException(sprintf('MetaModel ID %u not found', $this->metamodel));
        }

        // Get database
        $this->database = $this->metaModel->getServiceContainer()->getDatabase();

        // Import frontend user
        /** @noinspection PhpUndefinedMethodInspection */
        $this->import('FrontendUser', 'User');
    }


    /**
     * Return a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if ('BE' === TL_MODE) {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]).' ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $template->parse();
        }

        // Set a custom template
        if ($this->customTpl != '') {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }


    /**
     * Fetch item by Id or auto_item
     *
     * @param int $id The item id
     *
     * @return bool True if item was found
     */
    protected function fetchItem($id = 0)
    {
        if (0 === $id) {
            $this->autoItem = Input::getAutoItem('items');

            // Fetch alias attribute
            foreach ($this->metaModel->getAttributes() as $attribute) {
                if ($attribute->get('type') == 'alias') {
                    $this->aliasColName = $attribute->getColName();
                    break;
                }
            }

            // Fetch current item by its auto_item
            $itemDatabase = $this->database
                ->prepare(
                    sprintf
                    (
                        'SELECT * FROM %1$s WHERE (id=? OR %2$s=?)',
                        $this->metaModel->getTableName(),
                        $this->aliasColName
                    )
                )
                ->execute(is_int($this->autoItem) ? $this->autoItem : 0, $this->autoItem);

            $id = $itemDatabase->id;
        }

        $this->item = $this->metaModel->findById($id);

        return (null !== $this->item);
    }


    /**
     * Check permission by MetaModel's owner attribute and exit with 403 optionally
     */
    protected function checkPermission()
    {
        $this->fetchOwnerAttribute();

        // todo
        switch ($this->metaModel->getTableName()) {
            case 'mm_ferienpass':
                $hostId = $this->item->get($this->ownerAttribute->getColName())[MetaModelSelect::SELECT_RAW]['id'];

                if ($this->User->ferienpass_host != $hostId) {
                    $this->exitWith403();
                }
                break;
            case 'mm_participant':
                if ($this->User->id != $this->item->get($this->ownerAttribute->getColName())['id']) {
                    $this->exitWith403();
                }
                break;
            default:
                $this->exitWith403();
                break;
        }
    }


    /**
     * Set MetaModel's owner attribute
     *
     * @param IMetaModel $metaModel The MetaModel that will be taken to find the owner attribute
     */
    protected function fetchOwnerAttribute($metaModel = null)
    {
        if (null !== $this->ownerAttribute && null === $metaModel) {
            return;
        }

        $metaModel = (null === $metaModel) ? $this->metaModel : $metaModel;

        $this->ownerAttribute = $metaModel->getAttributeById($metaModel->get('owner_attribute'));

        if (null === $this->ownerAttribute) {
            throw new \RuntimeException('No owner attribute in the MetaModel was found');
        }
    }


    /**
     * Output a 403 page and stop further execution
     */
    protected function exitWith403()
    {
        global $objPage;

        /** @var \PageError403 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY']['error_403']();
        $pageHandler->generate($objPage->id);

        exit;
    }


    /**
     * Output a 404 page and stop further execution
     */
    protected function exitWith404()
    {
        global $objPage;

        /** @var \PageError404 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY']['error_404']();
        $pageHandler->generate($objPage->id);

        exit;
    }
}
