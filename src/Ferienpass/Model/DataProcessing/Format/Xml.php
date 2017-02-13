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
use MetaModels\IItem;
use MetaModels\IItems;

class Xml implements FormatInterface
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
    private $offers;


    /**
     * @return array
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
    public function getOffers()
    {
        return $this->offers;
    }


    /**
     * @param IItems $offers
     *
     * @return Xml
     */
    public function setOffers($offers)
    {
        $this->offers = $offers;
        return $this;
    }

    public function __construct($model, $offers)
    {

        $this->model  = $model;
        $this->offers = $offers;
    }

    /**
     * {@inheritdoc}
     */
    public function processOffers()
    {
        $files = [];

        switch ($this->getModel()->scope) {
            case 'full':
                // Fetch files from image folders
                if (null !== ($objOfferImages = \FilesModel::findByPk($this->getModel()->offer_image_path))) {
                    $files[($this->getModel())::EXPORT_OFFER_IMAGES_PATH] = $this->getModel()
                        ->getMountManager()
                        ->listContents('dbafs://' . $objOfferImages->path);
                }
                if (null !== ($objHostLogos = \FilesModel::findByPk($this->getModel()->host_logo_path))) {
                    $files[($this->getModel())::EXPORT_HOST_LOGOS_PATH] = $this->getModel()
                        ->getMountManager()
                        ->listContents('dbafs://' . $objHostLogos->path);
                }
                break;

            case 'single':
                break;
        }

        if (null !== $this->getOffers()) {
            // Walk each offer
            while ($this->getOffers()->next()) {
                $strXml = $this->generateOfferXml($this->getOffers()->getItem());

                if (false !== $strXml) {
                    $directory = sprintf(
                        '%s/offer_%s.xml',
                        $this->getModel()->getTmpPath(),
                        $this->getOffers()->getItem()->get('id')
                    );
                    $this->getModel()->getMountManager()->put('local://' . $directory, $strXml);

                    $files[($this->getModel())::EXPORT_XML_FILES_PATH] = $this->getModel()
                        ->getMountManager()
                        ->listContents('local://' . $this->getModel()->getTmpPath());
                }
            }
        }

        $this->files = $files;

        return $this;
    }

    /**
     * Return the offer's xml as string
     *
     * @param IItem $offer The offer
     *
     * @return string|false
     */
    protected function generateOfferXml($offer)
    {
        $variants = null;

        // If we combine variants, only variant bases will be exported
        if ($this->getModel()->combine_variants) {
            if ($offer->isVariant()) {
                return false;
            }

            $variants = $offer->getVariants(null);
        }

        $renderSetting = $offer->getMetaModel()->getView($this->getModel()->metamodel_view);

        // Create DOM
        $dom = new \DOMDocument('1.0', 'utf-8');

        // Create comment
        $commentTemplate = new \FrontendTemplate('dataprocessing_xml_comment');
        $commentTemplate->setData($this->getModel()->row());
        $dom->appendChild($dom->createComment($commentTemplate->parse()));

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

        $dom->appendChild($root);

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
                $parsed = implode(($this->getModel())::VARIANT_DELIMITER, $parsed);
            } // Default procedure for non-variant attributes
            else {
                // Parse attribute with render setting
                $parsed = $offer->parseAttribute($colName, 'text', $renderSetting);
                $parsed = $parsed['text'];
            }

            // Prepare attribute node by setting attribute id
            $domAttribute = $dom->createElement($this->camelCase($colName));
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

        return $dom->saveXML();
    }

    /**
     * Check whether the parsed attribute string is in XML and import the nodes if so
     *
     * @param string   $attributeParsed
     * @param \DOMNode $attributeNode
     *
     * @return bool True if xml was imported and appended to attribute False if nothing was changed
     */
    protected function importXmlToNode($attributeParsed, $attributeNode)
    {
        if (!$attributeParsed) {
            return false;
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

            return true;
        }

        return false;
    }

    /**
     * Camel Case (with first case uppercase) a column name
     *
     * @param string $value
     *
     * @return string
     */
    private function camelCase($value)
    {
        return preg_replace('/[\s\_\-]/', '', ucwords($value, ' _-'));
    }
}