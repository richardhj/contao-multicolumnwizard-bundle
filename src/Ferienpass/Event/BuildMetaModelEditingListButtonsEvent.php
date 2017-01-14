<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Event;


use MetaModels\FrontendIntegration\HybridList;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\Event;


class BuildMetaModelEditingListButtonsEvent extends Event
{

    const NAME = 'metamodels.item-list.build-editing-buttons';


    /**
     * @var IItem
     */
    protected $item;


    /**
     * @var array
     */
    protected $buttons;


    /**
     * @var array
     */
    protected $itemData;


    /**
     * @var object
     */
    protected $caller;


    /**
     * BuildMetaModelEditingListButtonsEvent constructor.
     *
     * @param IItem      $item
     * @param array      $buttons
     * @param array      $itemData
     * @param object $caller
     */
    public function __construct(IItem $item, array $buttons, array $itemData, $caller)
    {

        $this->item = $item;
        $this->buttons = $buttons;
        $this->itemData = $itemData;
        $this->caller = $caller;
    }


    /**
     * @return IItem
     */
    public function getItem()
    {
        return $this->item;
    }


    /**
     * @return array
     */
    public function getButtons()
    {
        return $this->buttons;
    }


    /**
     * @param array $buttons
     *
     * @return BuildMetaModelEditingListButtonsEvent
     */
    public function setButtons($buttons)
    {
        $this->buttons = $buttons;

        return $this;
    }


    /**
     * @return array
     */
    public function getItemData()
    {
        return $this->itemData;
    }


    /**
     * @return object
     */
    public function getCaller()
    {
        return $this->caller;
    }
}