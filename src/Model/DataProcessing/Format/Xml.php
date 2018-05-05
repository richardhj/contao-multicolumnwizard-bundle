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
use Doctrine\DBAL\Connection;
use DOMDocument;
use DOMElement;
use DOMNode;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IFactory;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing as DataProcessingModel;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml\ConvertDomElementToNativeWidgetEvent;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml\Exception\UnsupportedDomElementChangeException;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;
use MetaModels\IItems;
use Symfony\Component\Config\Util\Exception\InvalidXmlException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class Xml
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format
 */
class Xml implements FormatInterface, Format\TwoWaySyncInterface
{

    /**
     * @var IFactory
     */
    private $factory;

    /**
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $kernelProjectDir;

    /**
     * @var DataProcessingModel|null
     */
    private $model;

    /**
     * Xml constructor.
     *
     * @param IFactory                 $factory              The MetaModels factory.
     * @param IRenderSettingFactory    $renderSettingFactory The MetaModels render setting factory.
     * @param Filesystem               $filesystem           The filesystem component.
     * @param Connection               $connection           The database connection.
     * @param EventDispatcherInterface $dispatcher           The event dispatcher.
     * @param string                   $kernelProjectDir     The kernel project dir.
     */
    public function __construct(
        IFactory $factory,
        IRenderSettingFactory $renderSettingFactory,
        Filesystem $filesystem,
        Connection $connection,
        EventDispatcherInterface $dispatcher,
        string $kernelProjectDir
    ) {
        $this->factory              = $factory;
        $this->renderSettingFactory = $renderSettingFactory;
        $this->filesystem           = $filesystem;
        $this->connection           = $connection;
        $this->dispatcher           = $dispatcher;
        $this->kernelProjectDir     = $kernelProjectDir;
    }

    /**
     * @return bool
     */
    public function isCombineVariants(): bool
    {
        return $this->model->combine_variants;
    }

    /**
     * @return bool
     */
    public function isXmlSingleFile(): bool
    {
        return $this->model->xml_single_file;
    }

    /**
     * Process the items and provide the files
     *
     * @param IItems              $items The items to process.
     *
     * @param DataProcessingModel $model
     *
     * @return array
     *
     * @throws IOException
     */
    public function processItems(IItems $items, DataProcessingModel $model): array
    {
        if (0 === $items->getCount()) {
            return [];
        }

        $this->model = $model;

        $files = [];
        foreach ($this->getXml($items) as $id => $xml) {
            $path = sprintf(
                '%s/xml/%s.xml',
                $model->getTmpPath(),
                ($this->isXmlSingleFile() ? 'items' : 'item_'.$id)
            );

            $this->filesystem->dumpFile($this->kernelProjectDir.'/'.$path, $xml);
            $files[] = $path;
        }

        return $files;
    }

    /**
     * @param array  $files
     * @param string $originFileSystem
     */
    public function syncFilesFromRemoteSystem(array $files, string $originFileSystem = 'local'): void
    {
        $this->syncXmlFilesWithModel($files, $originFileSystem);
    }

    /**
     * @param IItems $items
     *
     * @return array The xml contents as array in the format ['item_id'=>'xml'] or simply ['xml'] when creating a
     *               single xml file
     */
    protected function getXml(IItems $items): array
    {
        $return = [];

        // Create DOM
        $dom = new DOMDocument('1.0', 'utf-8');

        // Add comment
        $commentTemplate = new \FrontendTemplate('dataprocessing_xml_comment');
        $commentTemplate->setData($this->model->row());
        $dom->appendChild($dom->createComment($commentTemplate->parse()));

        if ($this->isXmlSingleFile()) {
            $root = $dom->createElement('Items');

            foreach ($items as $item) {
                $domItem = $this->itemAsDomNode($item, $dom);
                if (null !== $domItem) {
                    $root->appendChild($domItem);
                }
            }

            $dom->appendChild($root);

            $return[] = $dom->saveXML();
        } else {
            foreach ($items as $item) {
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
     *
     * @throws \RuntimeException
     */
    protected function itemAsDomNode(IItem $item, DOMDocument $dom): ?DOMElement
    {
        $items = null;

        if ($this->isCombineVariants()) {
            if ($item->isVariant()) {
                // If we combine variants, only variant bases will be exported
                return null;
            }

            // Fetch variants including varbase
            $variantsFilter = $this->model->getFilter()->createCopy();

            $idList = $this->connection->createQueryBuilder()
                ->select('id')
                ->from('mm_ferienpass')
                ->where('vargroup=:id')
                ->setParameter('id', $item->get('id'))
                ->execute()
                ->fetchAll(\PDO::FETCH_COLUMN);

            $variantsFilter->addFilterRule(new StaticIdList($idList));
            $items = $item->getMetaModel()->findByFilter($variantsFilter);
        }

        $renderSetting =
            $this->renderSettingFactory->createCollection($item->getMetaModel(), $this->model->metamodel_view);

        $root = $dom->createElement('Item');
        $root->setAttribute('item_id', $item->get('id'));

        if ($items->getCount() > 1) {
            $root->setAttribute(
                'variant_ids',
                implode(
                    ',',
                    array_map(
                        function (IItem $variant) {
                            return $variant->get('id');
                        },
                        array_filter(
                            iterator_to_array($items),
                            function (IItem $variant) {
                                return $variant->isVariant();
                            }
                        )
                    )
                )
            );

            $items->reset();
        }

        foreach ($renderSetting->getSettingNames() as $colName) {
            $attribute    = $item->getAttribute($colName);
            $domAttribute = $dom->createElement(static::camelCase($colName));
            $domAttribute->setAttribute('attr_id', $attribute->get('id'));

            if (!($this->isCombineVariants() && $items->getCount() > 1 && $attribute->get('isvariant'))) {
                $parsed = $item->parseAttribute($colName, 'text', $renderSetting);
                $this->addParsedToDomAttribute($parsed['text'], $domAttribute);
            } else {
                while ($items->next()) {
                    $domVariantValue = $dom->createElement('_variantValue');
                    $domVariantValue->setAttribute('item_id', $items->getItem()->get('id'));

                    $parsed = $items->getItem()->parseAttribute($colName, 'text', $renderSetting);
                    $this->addParsedToDomAttribute($parsed['text'], $domVariantValue);
                    $domAttribute->appendChild($domVariantValue);

                    if ($items->offsetExists($items->key() + 1)) {
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
        } catch (InvalidXmlException $e) {
            $domAttribute->nodeValue =
                htmlspecialchars(html_entity_decode($parsed), ENT_QUOTES | ENT_XML1) ?: ' '; // Prohibit empty string
        }
    }

    /**
     * Take an XML string and import the nodes as children to a given node
     *
     * @param string  $attributeParsed
     * @param DOMNode $attributeNode
     *
     * @throws InvalidXmlException
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
            throw new InvalidXmlException('Invalid xml passed');
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
//        /** @var MountManager $mountManager */
//        $mountManager = $this->getModel()->getMountManager($filesystem);
//        $metaModel    = $this->factory->getMetaModel($this->tableName);
//
//        foreach ($files as $file) {
//            if ('application/xml' !== $file['mimetype']) {
//                continue;
//            }
//
//            // Load XML document.
//            $dom = new DOMDocument('1.0', 'utf-8');
//            $dom->loadXML($mountManager->read($filesystem.'://'.$file['path']));
//
//            /** @type DOMElement $root */
//            $root = $dom->getElementsByTagName('Item')->item(0);
//            $item = $metaModel->findById($root->getAttribute('item_id'));
//
//            $itemHasVariants = $this->isCombineVariants() && !empty(trimsplit(',', $root->getAttribute('variant_ids')));
//
//            /** @var DOMElement $element */
//            foreach ($root->getElementsByTagName('*') as $element) {
//                if (!$element->hasAttribute('attr_id')) {
//                    // Child nodes irrespectively node hierarchy are here too, skip them.
//                    continue;
//                }
//
//                $attribute = $item->getMetaModel()->getAttributeById($element->getAttribute('attr_id'));
//                if (null === $attribute) {
//                    continue;
//                }
//
//                if ($itemHasVariants && $attribute->get('isvariant')) {
//                    /** @var DOMElement $variantValue */
//                    foreach ($element->getElementsByTagName('_variantValue') as $variantValue) {
//                        $variant = $metaModel->findById($variantValue->getAttribute('item_id'));
//
//                        // Check for a proper variant
//                        if ($variant->get('vargroup') !== $item->get('id')) {
//                            $this->dispatcher->dispatch(
//                                ContaoEvents::SYSTEM_LOG,
//                                new LogEvent(
//                                    sprintf(
//                                        'Item ID %u is not a proper variant of item ID %u. Rough changes between database and xml file make the processing unable to synchronize attribute ID %u. Data processing ID %u',
//                                        $variant->get('id'),
//                                        $item->get('id'),
//                                        $attribute->get('id'),
//                                        $this->getModel()->id
//                                    ),
//                                    __METHOD__,
//                                    TL_ERROR
//                                )
//                            );
//
//                            continue;
//                        }
//
//                        $this->trackAttributeChange($variantValue, $attribute, $variant);
//                    }
//                } else {
//                    $this->trackAttributeChange($element, $attribute, $item);
//                }
//            }
//        }
    }

    /**
     * Check whether an attribute dom node differs from the attribute's database value, track change and save item
     *
     * @param DOMElement $domAttribute
     * @param IAttribute $attribute
     * @param IItem      $item
     */
    protected function trackAttributeChange(DOMElement $domAttribute, IAttribute $attribute, IItem $item)
    {
        $view   =
            $this->renderSettingFactory->createCollection($item->getMetaModel(), $this->model->metamodel_view);
        $parsed = $item->parseAttribute(
            $attribute->getColName(),
            'text',
            $view
        );

        try {
            $widget = $this->domElementToNativeWidget($domAttribute, $attribute, $item);

            if ($widget === $parsed['raw']) {
                // No change detected.
                return;
            }

            // Update changed data.
            $item->set($attribute->getColName(), $widget);
            $item->save();

            $this->dispatcher->dispatch(
                ContaoEvents::SYSTEM_LOG,
                new LogEvent(
                    sprintf(
                        'Attribute "%s" for item variant ID %u was synced from xml file. Data processing ID %u',
                        $attribute->getColName(),
                        $item->get('id'),
                        $this->model->id
                    ),
                    __METHOD__,
                    TL_GENERAL
                )
            );
        } catch (UnsupportedDomElementChangeException $e) {
            // Check for the attribute data being changed.
            $testDom     = new DOMDocument('1.0', 'utf-8');
            $testElement = $testDom->createElement($domAttribute->nodeName);
            try {
                $this->importXmlToNode($parsed['text'], $testElement);
            } catch (InvalidXmlException $e) {
                $testElement->nodeValue = $parsed['text'];
            }

            if ($domAttribute->nodeValue !== $testElement->nodeValue) {
                $this->dispatcher->dispatch(
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
                            $this->model->id
                        ),
                        __METHOD__,
                        TL_ERROR
                    )
                );
            }
        }
    }

    /**
     * Try to convert the DOMElement's content to a widget's raw data by the widget type
     *
     * @param DOMElement $element
     * @param IAttribute $attribute
     * @param IItem      $item
     *
     * @return mixed The attribute's data in the same format as the attribute's "raw" data
     *
     * @throws UnsupportedDomElementChangeException
     */
    private function domElementToNativeWidget(DOMElement $element, IAttribute $attribute, IItem $item)
    {
        $event = new ConvertDomElementToNativeWidgetEvent($element, $attribute, $item);
        $this->dispatcher->dispatch($event::NAME, $event);

        if (null !== $event->getValue()) {
            return $event->getValue();
        }

        throw new UnsupportedDomElementChangeException(
            sprintf(
                'The DOMElement cannot be converted back to native attribute data (Attribute type %s).',
                $attribute->get('type')
            )
        );
    }

    /**
     * @param IAttribute $attribute
     *
     * @return string
     */
    private function getVariantDelimiter(IAttribute $attribute): string
    {
        $delimiter = '';

        $delimiterConfigs = deserialize($this->model->variant_delimiters, true);
        foreach ((array)$delimiterConfigs as $delimiterConfig) {
            if ('' === $delimiterConfig['metamodel_attribute']
                || $attribute->getColName() === $delimiterConfig['metamodel_attribute']
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
