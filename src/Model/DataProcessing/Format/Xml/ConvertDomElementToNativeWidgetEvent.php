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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml;


use DOMElement;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;

/**
 * Class ConvertDomElementToNativeWidgetEvent
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml
 */
class ConvertDomElementToNativeWidgetEvent
{

    /**
     * The DOM element.
     *
     * @var DOMElement
     */
    private $domElement;

    /**
     * The attribute.
     *
     * @var IAttribute
     */
    private $attribute;

    /**
     * The item.
     *
     * @var IItem
     */
    private $item;

    /**
     * The value.
     * @var mixed
     */
    private $value;

    /**
     * Event name
     */
    public const NAME = 'richardhj.ferienpass.data-processing.xml.convert-dom-to-widget';

    /**
     * ConvertDomElementToNativeWidgetEvent constructor.
     *
     * @param DOMElement $domElement The DOM element.
     * @param IAttribute $attribute The attribute.
     * @param IItem      $item The item.
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
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }
}
