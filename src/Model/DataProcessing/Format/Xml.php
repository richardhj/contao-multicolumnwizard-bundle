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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;


use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use DOMDocument;
use DOMElement;
use DOMNode;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml\ConvertDomElementToNativeWidgetEvent;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
use League\Flysystem\MountManager;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\IMetaModelsServiceContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class Xml
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format
 */
class Xml implements FormatInterface, Format\TwoWaySyncInterface
{

    /**
     * @var array
     */
    private $files;

    /**
     * @var DataProcessing|\Model
     */
    private $model;

    /**
     * @var IItems
     */
    private $items;

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return DataProcessing
     */
    public function getModel(): DataProcessing
    {
        return $this->model;
    }

    /**
     * @return IItems
     */
    public function getItems(): IItems
    {
        return $this->items;
    }

    /**
     * @param IItems $items
     *
     * @return FormatInterface
     */
    public function setItems(IItems $items): FormatInterface
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCombineVariants(): bool
    {
        return $this->getModel()->combine_variants;
    }

    /**
     * @return bool
     */
    public function isXmlSingleFile(): bool
    {
        return $this->getModel()->xml_single_file;
    }

    /**
     * @param IAttribute $attribute
     *
     * @return string
     */
    public function getVariantDelimiter(IAttribute $attribute): string
    {
        $delimiter = '';

        $delimiterConfigs = deserialize($this->getModel()->variant_delimiters, true);
        foreach ($delimiterConfigs as $delimiterConfig) {
            if ($delimiterConfig['metamodel_attribute'] === $attribute->getColName()
                || $delimiterConfig['metamodel_attribute'] === ''
            ) {
                $delimiter = sprintf(
                    '%2$s%1$s%3$s',
                    $delimiterConfig['delimiter'],
                    'before' === $delimiterConfig['newline_position'] ? PHP_EOL : '',
                    'after' === $delimiterConfig['newline_position'] ? PHP_EOL : ''
                );
                break;
            }
        }

        return $delimiter;
    }

    /**
     * {@inheritdoc}
     */
    public function processItems(): FormatInterface
    {
        if (null === $this->getItems()) {
            $this->files = [];
            return $this;
        }

        foreach ($this->getXml() as $k => $xml) {
            $path = sprintf(
                '%s/xml/%s.xml',
                $this->getModel()->getTmpPath(),
                ($this->isXmlSingleFile() ? 'items' : 'item_' . $k)
            );

            // Save xml in tmp path
            $this
                ->getModel()
                ->getMountManager()
                ->put('local://' . $path, $xml);
        }

        // Fetch all files in xml tmp path
        $this->files = $this
            ->getModel()
            ->getMountManager()
            ->listContents(sprintf('local://%s/xml', $this->getModel()->getTmpPath()));

        return $this;
    }

    /**
     * @param array  $files
     * @param string $originFileSystem
     */
    public function backSyncFiles(array $files, string $originFileSystem = 'local')
    {
        $this->syncXmlFilesWithModel($files, $originFileSystem);
    }

    /**
     * @return array The xml contents as array in the format ['item_id'=>'xml'] or simply ['xml'] when creating a
     *               single xml file
     */
    protected function getXml(): array
    {
        $return = [];

        // Create DOM
        $dom = new DOMDocument('1.0', 'utf-8');

        // Add comment
        $commentTemplate = new \FrontendTemplate('dataprocessing_xml_comment');
        $commentTemplate->setData($this->getModel()->row());
        $dom->appendChild($dom->createComment($commentTemplate->parse()));

        if ($this->isXmlSingleFile()) {
            $root = $dom->createElement('Items');

            foreach ($this->getItems() as $item) {
                $domItem = $this->itemAsDomNode($item, $dom);
                if (null !== $domItem) {
                    $root->appendChild($domItem);
                }
            }

            $dom->appendChild($root);

            $return[] = $dom->saveXML();
        } else {
            foreach ($this->getItems() as $item) {
                $domClone = clone $dom;
                $domItem  = $this->itemAsDomNode($item, $domClone);
                if (null !== $domItem) {
                    $domClone->appendChild($domItem);
                    $return[$item->get('id')] = $domClone->saveXML();
                }
            }
        }

        return $return;
    }

    /**
     * Get the dom node for a particular item
     *
     * @param IItem       $item
     * @param DOMDocument $dom
     *
     * @return DOMElement|null
     */
    protected function itemAsDomNode(IItem $item, DOMDocument $dom)
    {
        $variants = null;

        if ($this->isCombineVariants()) {
            if ($item->isVariant()) {
                // If we combine variants, only variant bases will be exported
                return null;
            }

            // Fetch variants by using the filter to apply sorting
            $variants = $item->getVariants($this->getModel()->getFilter());
        }

        $renderSetting = $item->getMetaModel()->getView($this->getModel()->metamodel_view);

        $root = $dom->createElement('Item');
        $root->setAttribute('item_id', $item->get('id'));

        if (null !== $variants && $variants->getCount()) {
            $root->setAttribute(
                'variant_ids',
                implode(
                    ',',
                    array_map(
                        function (IItem $variant) {
                            return $variant->get('id');
                        },
                        iterator_to_array($variants)
                    )
                )
            );
            $variants->reset();
        }

        foreach ($renderSetting->getSettingNames() as $colName) {
            $attribute    = $item->getAttribute($colName);
            $domAttribute = $dom->createElement(static::camelCase($colName));
            $domAttribute->setAttribute('attr_id', $attribute->get('id'));

            if (!($this->isCombineVariants() && $variants->getCount() && $attribute->get('isvariant'))) {
                $parsed = $item->parseAttribute($colName, 'text', $renderSetting);
                $this->addParsedToDomAttribute($parsed['text'], $domAttribute);
            } else {
                while ($variants->next()) {
                    $domVariantValue = $dom->createElement('_variantValue');
                    $domVariantValue->setAttribute('item_id', $variants->getItem()->get('id'));

                    $parsed = $variants->getItem()->parseAttribute($colName, 'text', $renderSetting);
                    $this->addParsedToDomAttribute($parsed['text'], $domVariantValue);
                    $domAttribute->appendChild($domVariantValue);

                    if ($variants->offsetExists($variants->key() + 1)) {
                        $domAttribute->appendChild(
                            $dom->createElement('_variantDelimiter', $this->getVariantDelimiter($attribute))
                        );
                    }
                }
            }

            $root->appendChild($domAttribute);
        }

        return $root;
    }


    /**
     * Try to import a parsed attribute to a dom node if it is a valid xml string or set the dom node value otherwise
     *
     * @param string     $parsed
     * @param DOMElement $domAttribute
     */
    protected function addParsedToDomAttribute(string $parsed, DOMElement $domAttribute)
    {
        try {
            $this->importXmlToNode($parsed, $domAttribute);
        } catch (\RuntimeException $e) {
            $domAttribute->nodeValue = htmlspecialchars(
                html_entity_decode($parsed),
                ENT_XML1
            ) ?: ' '; // Prohibit empty string
        }
    }


    /**
     * Take an XML string and import the nodes as children to a given node
     *
     * @param string  $attributeParsed
     * @param DOMNode $attributeNode
     *
     * @throws \RuntimeException If $attributeParsed is not a valid xml string
     */
    protected function importXmlToNode(string $attributeParsed, DOMNode $attributeNode)
    {
        if ('' === $attributeParsed) {
            return;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->loadXML($attributeParsed);
        $errors = libxml_get_errors();

        libxml_clear_errors();

        if (!empty($errors)) {
            throw new \RuntimeException('Invalid xml passed');
        }

        foreach ($dom->childNodes as $element) {
            $node = $attributeNode->ownerDocument->importNode($element, true);
            $attributeNode->appendChild($node);
        }
    }


    /**
     * Synchronize given xml files with the MetaModel
     *
     * @param array  $files      The xml files. An array formatted like Filesystem->listContents() does
     * @param string $filesystem The filesystem the xml files originate from
     */
    protected function syncXmlFilesWithModel(array $files, string $filesystem)
    {
        global $container;

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container['event-dispatcher'];
        /** @var MountManager $mountManager */
        $mountManager = $this->getModel()->getMountManager($filesystem);
        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer = $container['metamodels-service-container'];
        $metaModel        = $serviceContainer->getFactory()->getMetaModel('mm_ferienpass');

        foreach ($files as $file) {
            if ('application/xml' !== $file['mimetype']) {
                continue;
            }

            // Load xml document
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->loadXML($mountManager->read($filesystem . '://' . $file['path']));

            /** @type DOMElement $root */
            $root            = $dom->getElementsByTagName('Item')->item(0);
            $item            = $metaModel->findById($root->getAttribute('item_id'));
            $itemHasVariants = $this->isCombineVariants() && !empty(trimsplit(',', $root->getAttribute('variant_ids')));

//            // Existing offer was edited
//            if ($file['timestamp'] < $offer->get('tstamp')) {
//                $a = $this->setItems($metaModel->findById($offer->get('id')))->processItems()->getFiles();
//                // Override foreign xml from database
//                $mountManager->put(
//                    $filesystem . '://' . $file['path'],
//                    $this->generateOfferXml($offer->get('id'))
//                );
//
//                \System::log(
//                    sprintf(
//                        'Could not sync XML file "%s" because offer ID %u was edited afterwards',
//                        $file['path'],
//                        $offer->get('id')
//                    ),
//                    __METHOD__,
//                    TL_ERROR
//                );
//
//                continue;
//            }

            /** @var DOMElement $element */
            foreach ($root->getElementsByTagName('*') as $element) {
                // Child nodes irrespectively hierarchy are here too, skip them
                if (!$element->hasAttribute('attr_id')) {
                    continue;
                }

                $attribute = $item
                    ->getMetaModel()
                    ->getAttributeById($element->getAttribute('attr_id'));
                if (null === $attribute) {
                    continue;
                }

                if (!($itemHasVariants && $attribute->get('isvariant'))) {
                    $this->trackAttributeChange($element, $attribute, $item, $file);
                } else {
                    /** @var DOMElement $variantValue */
                    foreach ($element->getElementsByTagName('_variantValue') as $variantValue) {
                        $variant = $metaModel->findById($variantValue->getAttribute('item_id'));

                        // Check for a proper variant
                        if ($variant->get('vargroup') !== $item->get('id')) {
                            $dispatcher->dispatch(
                                ContaoEvents::SYSTEM_LOG,
                                new LogEvent(
                                    sprintf(
                                        'Item ID %u is not a proper variant of item ID %u. Rough changes between database and xml file make the processing unable to synchronize attribute ID %u. Data processing ID %u',
                                        $variant->get('id'),
                                        $item->get('id'),
                                        $attribute->get('id'),
                                        $this->getModel()->id
                                    ),
                                    __METHOD__,
                                    TL_ERROR
                                )
                            );

                            continue;
                        }

                        $this->trackAttributeChange($variantValue, $attribute, $variant, $file);
                    }
                }
            }
        }
    }


    /**
     * Check whether an attribute dom node differs from the attribute's database value, track change and save item
     *
     * @param DOMElement $domAttribute
     * @param IAttribute $attribute
     * @param IItem      $item
     * @param array      $file
     */
    protected function trackAttributeChange(DOMElement $domAttribute, IAttribute $attribute, IItem $item, array $file)
    {
        $widget = $this->domElementToNativeWidget($domAttribute, $attribute, $item);
        $parsed = $item->parseAttribute(
            $attribute->getColName(),
            'text',
            $item->getMetaModel()->getView($this->getModel()->metamodel_view)
        );

        // Widget can not be converted back because of its attribute type
        if (null === $widget) {
            // Do a check by approximation
            $testDom     = new DOMDocument('1.0', 'utf-8');
            $testElement = $testDom->createElement($domAttribute->nodeName);
            try {
                $this->importXmlToNode($parsed['text'], $testElement);
            } catch (\RuntimeException $e) {
                $testElement->nodeValue = $parsed['text'];
            }

            if ($domAttribute->nodeValue != $testElement->nodeValue) {
                $attribute->getMetaModel()->getServiceContainer()->getEventDispatcher()->dispatch(
                    ContaoEvents::SYSTEM_LOG,
                    new LogEvent(
                        sprintf(
                            'Attribute "%s" (type "%s") for item ID %u can not be updated although it was changed. XML value: "%s". Xml parsed database value: "%s". Raw database value: "%s". Data processing ID %u',
                            $attribute->getColName(),
                            $attribute->get('type'),
                            $item->get('id'),
                            $domAttribute->nodeValue,
                            $testElement->nodeValue,
                            var_export($parsed['raw'], true),
                            $this->getModel()->id
                        ),
                        __METHOD__,
                        TL_ERROR
                    )
                );
            }
        } // Check for change
        elseif ($widget !== $parsed['raw']) {
            $item->set(
                $attribute->getColName(),
                $widget
            );
            $item->save();

            $attribute->getMetaModel()->getServiceContainer()->getEventDispatcher()->dispatch(
                ContaoEvents::SYSTEM_LOG,
                new LogEvent(
                    sprintf(
                        'Attribute "%s" for item variant ID %u was synced from xml file "%s". Data processing ID %u',
                        $attribute->getColName(),
                        $item->get('id'),
                        $file['path'],
                        $this->getModel()->id
                    ),
                    __METHOD__,
                    TL_GENERAL
                )
            );
        }
    }

    /**
     * Try to convert the DOMElement's content to a widget's raw data by the widget type
     *
     * @param DOMElement $element
     * @param IAttribute $attribute
     * @param IItem      $item
     *
     * @return mixed|null The attribute's data in the same format as the attribute's "raw" data
     */
    protected function domElementToNativeWidget(DOMElement $element, IAttribute $attribute, IItem $item)
    {
        global $container;

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container['event-dispatcher'];
        $dispatcher->addSubscriber(new Format\Xml\ConvertSubscriber());

        $event = new ConvertDomElementToNativeWidgetEvent($element, $attribute, $item);
        $dispatcher->dispatch($event::NAME, $event);

        return $event->getValue();
    }


    /**
     * Camel Case (with first case uppercase) a column name
     *
     * @param string $value
     *
     * @return string
     */
    public static function camelCase($value): string
    {
        return preg_replace('/[\s\_\-]/', '', ucwords($value, ' _-'));
    }
}
