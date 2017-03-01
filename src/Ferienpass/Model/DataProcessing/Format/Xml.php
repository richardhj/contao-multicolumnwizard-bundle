<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


namespace Ferienpass\Model\DataProcessing\Format;


use Ferienpass\Model\DataProcessing;
use Ferienpass\Model\DataProcessing\FormatInterface;
use Ferienpass\Model\Offer;
use League\Flysystem\MountManager;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;
use MetaModels\IItems;

class Xml implements FormatInterface
{
    const VARIANT_DELIMITER = ', ';
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
    public function __construct(DataProcessing $model, IItems $items)
    {
        $this->model = $model;
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return DataProcessing
     */
    public function getModel()
    {
        return $this->model;
    }


    /**
     * @return IItems
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function processItems()
    {
        if (null === $this->getItems()) {
            $this->files = [];
            return $this;
        }

        foreach ($this->getXml() as $i => $xml) {
            $path = ($this->getModel()->xml_single_file)
                ? $this->getModel()->getTmpPath() . '/xml/offers.xml'
                : sprintf(
                    '%s/xml/offer_%s.xml',
                    $this->getModel()->getTmpPath(),
                    $i
                );

            // Save xml in tmp path
            $this
                ->getModel()
                ->getMountManager()
                ->put('local://' . $path, $xml);
        }

        // Fetch all files in xml tmp path
        $this->files = $this->getModel()
            ->getMountManager()
            ->listContents(sprintf('local://%s/xml', $this->getModel()->getTmpPath()));

        return $this;
    }

    /**
     * Camel Case (with first case uppercase) a column name
     *
     * @param string $value
     *
     * @return string
     */
    public static function camelCase($value)
    {
        return preg_replace('/[\s\_\-]/', '', ucwords($value, ' _-'));
    }

    /**
     * @return array
     */
    protected function getXml()
    {
        $return = [];

        // Create DOM
        $dom = new \DOMDocument('1.0', 'utf-8');

        // Create comment
        $commentTemplate = new \FrontendTemplate('dataprocessing_xml_comment');
        $commentTemplate->setData($this->getModel()->row());
        $dom->appendChild($dom->createComment($commentTemplate->parse()));


        if ($this->getModel()->xml_single_file) {
            $root = $dom->createElement('Offers');

            foreach ($this->getItems() as $offer) {
                $domOffer = $this->offerAsDomNode($offer, $dom);
                $root->appendChild($domOffer);
            }

            $dom->appendChild($root);
        } else {
            foreach ($this->getItems() as $offer) {
                $domClone = clone $dom;
                $domOffer = $this->offerAsDomNode($offer, $domClone);
                $domClone->appendChild($domOffer);

                $return[$offer->get('id')] = $domClone->saveXML();
            }
        }

        $return[] = $dom->saveXML();

        return $return;
    }

    /**
     * Get the dom node for a particular offer
     *
     * @param IItem        $offer
     * @param \DOMDocument $dom
     *
     * @return \DOMElement|null
     */
    protected function offerAsDomNode(IItem $offer, \DOMDocument $dom)
    {
        $variants = null;

        // If we combine variants, only variant bases will be exported
        if ($this->getModel()->combine_variants) {
            if ($offer->isVariant()) {
                return null;
            }

            $variants = $offer->getVariants(null);
        }

        $renderSetting = $offer->getMetaModel()->getView($this->getModel()->metamodel_view);


        $root = $dom->createElement('Offer');
        $root->setAttribute('id', $offer->get('id'));

        // Add variant ids (order will be important for following processings)
        if (null !== $variants && $variants->getCount()) {
            $variantIds = [];

            while ($variants->next()) {
                $variantIds[] = $variants->getItem()->get('id');
            }

            $root->setAttribute('variant_ids', implode(',', $variantIds));
        }

        // Walk each attribute in render setting
        foreach ($renderSetting->getSettingNames() as $colName) {
            $attribute = $offer->getAttribute($colName);

            // It is a variant attribute
            if ($this->getModel()->combine_variants && $variants->getCount() && $attribute->get('isvariant')) {
                // Fetch variants
                $parsed = [];
                $variants->reset();

                // Parse each attribute with render setting
                while ($variants->next()) {
                    $parsed[] = $variants->getItem()->parseAttribute($colName, 'text', $renderSetting)['text'];
                }

                // Combine variant attributes
                $parsed = implode(self::VARIANT_DELIMITER, $parsed);
            } // Default procedure for non-variant attributes
            else {
                // Parse attribute with render setting
                $parsed = $offer->parseAttribute($colName, 'text', $renderSetting);
                $parsed = $parsed['text'];
            }

            // Prepare attribute node by setting attribute id
            $domAttribute = $dom->createElement(static::camelCase($colName));
            $domAttribute->setAttribute('attr_id', $attribute->get('id'));

            // Set the attribute node's value
            $domAttribute->nodeValue = htmlspecialchars(
                html_entity_decode($parsed),
                ENT_XML1
            ) ?: ' '; // Prohibit empty string

            // Check if parsed attribute is an xml to import
            // This will override the nodeValue defined before
            $this->importXmlToNode($parsed, $domAttribute);

            $root->appendChild($domAttribute);
        }

        return $root;
    }


    /**
     * Check whether the parsed attribute string is in XML and import the nodes if so
     *
     * @param string   $attributeParsed
     * @param \DOMNode $attributeNode
     */
    protected function importXmlToNode($attributeParsed, $attributeNode)
    {
        if (!$attributeParsed) {
            return;
        }

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->loadXML($attributeParsed);
        $errors = libxml_get_errors();

        libxml_clear_errors();

        // It is a xml string
        if (empty($errors)) {
            // Reset node
            $attributeNode->nodeValue = '';

            foreach ($dom->childNodes as $element) {
                $node = $attributeNode->ownerDocument->importNode($element, true);
                $attributeNode->appendChild($node);
            }
        }
    }


    /**
     * Synchronize given xml files with the MetaModel
     *
     * @param array  $files      The xml files. An array formatted like Filesystem->listContents() does
     * @param string $filesystem The filesystem the xml files come from
     */
    public function syncXmlFilesWithModel($files, $filesystem = 'local')
    {
        /** @var MountManager $manager */
        $manager = $this->getModel()->getMountManager($filesystem);

        // Skip if no files are handed over
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            // Only process xml files
            if ('application/xml' !== $file['mimetype']) {
                continue;
            }

            $changed = false;

            // Load xml document
            $dom = new \DOMDocument('1.0', 'utf-8');
            $dom->loadXML($manager->read($filesystem . '://' . $file['path']));

            /** @type \DOMElement $root */
            $root = $dom->getElementsByTagName('Offer')->item(0);

            $offer    = Offer::getInstance()->findById($root->getAttribute('id'));
            $variants = null;

            // Fetch possible variants
            if ($this->getModel()->combine_variants) {
                if ($root->hasAttribute('variant_ids')) {
                    $variants = Offer::getInstance()->findMultipleByIds(
                        trimsplit(',', $root->getAttribute('variant_ids'))
                    );
                }
            }

            // Existing offer was edited
            if ($file['timestamp'] < $offer->get('tstamp')) {
                // Override foreign xml from database
                $manager->put(
                    $filesystem . '://' . $file['path'],
                    $this->generateOfferXml($offer->get('id'))
                );

                \System::log(
                    sprintf(
                        'Could not sync XML file "%s" because offer ID %u was edited afterwards',
                        $file['path'],
                        $offer->get('id')
                    ),
                    __METHOD__,
                    TL_ERROR
                );

                continue;
            }

            /** @var \DOMElement $element */
            foreach ($root->getElementsByTagName('*') as $element) {
                // Child nodes are passed too
                // We only want nodes parsed by an attribute here
                if (!$element->hasAttribute('attr_id')) {
                    continue;
                }

                $attribute = $offer
                    ->getMetaModel()
                    ->getAttributeById((int) $element->getAttribute('attr_id'));

                if (null === $attribute) {
                    continue;
                }

                // Attribute is variant attribute
                if ($this->getModel()->combine_variants && $variants !== null && $attribute->get('isvariant')) {
                    $variantValues = trimsplit(static::VARIANT_DELIMITER, $element->nodeValue);

                    if ($variants->getCount() !== count($variantValues)) {
                        \System::log(
                            sprintf(
                                'Cannot import attribute "%s" (type "%s") for offer ID %u as the delimited variant values are not assignable. Variant IDs by xml attribute: %s. Resolved variant values: %s. Data processing ID %u',
                                $attribute->getColName(),
                                $attribute->get('type'),
                                $offer->get('id'),
                                $element->nodeValue,
                                var_export(trimsplit(',', $root->getAttribute('variant_ids')), true),
                                var_export($variantValues, true),
                                $this->getModel()->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );

                        continue;
                    }

                    $variants->reset();

                    foreach ($variants as $i => $variant) {
                        $changedVariant = false;

                        // Check for a proper variant
                        if ($variant->get('vargroup') !== $offer->get('id')) {
                            \System::log(
                                sprintf(
                                    'Offer ID %u is not a proper variant of offer ID %u. Rough changes between database and xml file make the processing unable to synchronize attribute ID %u. Data processing ID %u',
                                    $variant->get('id'),
                                    $offer->get('id'),
                                    $attribute->get('id'),
                                    $this->id
                                ),
                                __METHOD__,
                                TL_ERROR
                            );

                            continue;
                        }

                        $parsed = $variant->parseAttribute(
                            $attribute->getColName(),
                            'text',
                            $variant->getMetaModel()->getView($this->getModel()->metamodel_view)
                        );

                        // Check for change
                        if ($variantValues[$i] !== $parsed['text']) {
                            //@todo whats with date?
                            $changedVariant = true;

                            $variant->set(
                                $attribute->getColName(),
                                $attribute->widgetToValue($variantValues[$i], $variant->get('id'))
                            );
                        }

                        if ($changedVariant) {
                            $variant->save();

                            \System::log(
                                sprintf(
                                    'Attribute "%s" for offer variant ID %u was synced from xml file "%s". Data processing ID %u',
                                    $attribute->getColName(),
                                    $variant->get('id'),
                                    $file['path'],
                                    $this->getModel()->id
                                ),
                                __METHOD__,
                                TL_GENERAL
                            );
                        }
                    }
                } // Attribute is not a variant attribute
                else {
                    // $widget will contain the data in the same format as attribute's 'raw' data will
                    try {
                        $widget = $this->domElementToNativeWidget(
                            $element,
                            $attribute,
                            $offer->get('id')
                        );
                    } catch (\RuntimeException $e) {
                        \System::log(
                            sprintf
                            (
                                'Could not sync XML file "%s" for offer ID %u. Error message: %s. Data processing ID %u',
                                $file['path'],
                                $offer->get('id'),
                                $e->getMessage(),
                                $this->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );

                        continue;
                    }

                    $parsed = $offer->parseAttribute(
                        $attribute->getColName(),
                        'text',
                        $offer->getMetaModel()->getView($this->getModel()->metamodel_view)
                    );

                    // Widget can not be converted back because of its attribute type
                    if (null === $widget) {
                        // Do an approximative check
                        $testDom     = new \DOMDocument('1.0', 'utf-8');
                        $testElement = $testDom->createElement($element->nodeName, $element->nodeValue);
                        $this->importXmlToNode($element->nodeValue, $testElement);

                        if ($element->nodeValue != $testElement->nodeValue) {
                            \System::log(
                                sprintf
                                (
                                    'Attribute "%s" (type "%s") for offer ID %u can not be updated although it was changed. XML value: "%s". Xml parsed database value: "%s". Raw database value: "%s". Data processing ID %u',
                                    $attribute->getColName(),
                                    $attribute->get('type'),
                                    $offer->get('id'),
                                    $element->nodeValue,
                                    $testElement->nodeValue,
                                    var_export($parsed['raw'], true),
                                    $this->id
                                ),
                                __METHOD__,
                                TL_ERROR
                            );
                        }
                    } // Check for change
                    elseif ($widget !== $parsed['raw']) {
                        $changed = true;

                        $offer->set(
                            $attribute->getColName(),
                            $widget
                        );
                    }
                }
            }

            if ($changed) {
                $offer->save();

                \System::log(
                    sprintf(
                        'Offer ID %u was synced from xml file "%s". Data processing ID %u',
                        $offer->get('id'),
                        $file['path'],
                        $this->getModel()->id
                    ),
                    __METHOD__,
                    TL_GENERAL
                );

                // Trigger sync for other linked dropboxes
                /** @var DataProcessing|\Model\Collection $objProcessings */
                $objProcessings = ($this->getModel())::findBy(
                    [
                        'filesystem=?',
                        'sync=1',
                        'id<>?',
                    ],
                    [
                        'dropbox',
                        $this->getModel()->id,
                    ]
                );

                while (null !== $objProcessings && $objProcessings->next()) {
                    $objProcessings->current()->run(
                        array_merge
                        (
                            [$offer->get('id')],
                            array_map(
                                function ($variant) {
                                    /** @noinspection PhpUndefinedMethodInspection */
                                    return $variant->get('id');
                                },
                                $variants
                            ) ?: []
                        )
                    );
                }
            }
        }
    }


    /**
     * Try to convert the DOMElement's content to a widget's raw data by the widget type
     *
     * @param \DOMElement $element
     * @param IAttribute  $attribute
     * @param integer     $itemId
     *
     * @return mixed|null The attribute's data in the same format as the attribute's "raw" data
     */
    protected function domElementToNativeWidget($element, $attribute, $itemId)
    {
        switch ($attribute->get('type')) {
            case 'alias':
            case 'combinedvalues':
            case 'decimal':
            case 'longtext':
            case 'numeric':
            case 'text':
            case 'url':
                // These attributes can easily adopted
                $widget = $element->nodeValue;
                break;

            case 'file':
                $widget = [];

                /** @type \DOMElement $fileDom */
                foreach ($element->getElementsByTagName('Link') as $fileDom) {
                    // Replace remote path with local path
                    $path = preg_replace(
                        '/^file:\/\/[\.\/]*/',
                        '',
                        $fileDom->getAttribute('href')
                    );

                    $file = \FilesModel::findByPath(urldecode($path));

                    // Local file does not exist therefore the remote file was not uploaded
                    if (null === $file) {
                        throw new \RuntimeException(
                            sprintf(
                                'File "%s" does not exist on local system. Sync files beforehand.',
                                $path
                            )
                        );
                    }

                    $widget[] = $file->uuid;
                }
                break;

            case 'tabletext':
                $widget = [];

                /** @type \DOMElement $element */
                $element = $element
                    ->getElementsByTagName('Tabletext')
                    ->item(0);

                $cc = $element->getAttribute('aid:tcols');
                $r  = 0;
                $c  = 0;

                /** @type \DOMElement $cell */
                foreach ($element->getElementsByTagName('Cell') as $cell) {
                    if ($c == $cc) {
                        $c = 0;
                        $r++;
                    }

                    $widget[$r]['col_' . $c] = $cell->nodeValue;

                    $c++;
                }
                break;

            case 'timestamp':
                //@todo import timestamp depended on render format
            default:
                // The attribute type is not supported to convert back
                return null;
                break;
        }

        // Convert the widget value to native MetaModel data
        return $attribute->widgetToValue($widget, $itemId);
    }
}