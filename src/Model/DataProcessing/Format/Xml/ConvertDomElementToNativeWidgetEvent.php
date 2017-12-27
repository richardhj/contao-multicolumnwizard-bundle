<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml;


use DOMElement;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;

class ConvertDomElementToNativeWidgetEvent
{

    /**
     * @var DOMElement
     */
    private $domElement;

    /**
     * @var IAttribute
     */
    private $attribute;

    /**
     * @var IItem
     */
    private $item;

    /**
     * @var
     */
    private $value;

    /**
     * Event name
     */
    const NAME = 'ferienpass.data-processing.xml.convert-dom-to-widget';

    /**
     * ConvertDomElementToNativeWidgetEvent constructor.
     *
     * @param DOMElement $domElement
     * @param IAttribute $attribute
     * @param IItem      $item
     */
    public function __construct(DOMElement $domElement, IAttribute $attribute, IItem $item)
    {
        $this->domElement = $domElement;
        $this->attribute  = $attribute;
        $this->item       = $item;
    }

    /**
     * @return mixed
     */
    public function getDomElement(): DOMElement
    {
        return $this->domElement;
    }

    /**
     * @return mixed
     */
    public function getAttribute(): IAttribute
    {
        return $this->attribute;
    }

    /**
     * @return mixed
     */
    public function getItem(): IItem
    {
        return $this->item;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
