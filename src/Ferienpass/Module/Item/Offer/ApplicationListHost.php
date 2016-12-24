<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module\Item\Offer;

use Ferienpass\Helper\Message;
use Ferienpass\Helper\Table;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\Document;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\ItemList;


/**
 * Class OfferApplicationListHost
 * @package Ferienpass\Module
 */
class ApplicationListHost extends Item
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_offer_applicationlisthost';


    /**
     * {@inheritdoc}
     * Include permission check
     */
    public function generate($isProtected = true)
    {
        return parent::generate($isProtected);
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        if (!$this->item->get(FerienpassConfig::getInstance()->offer_attribute_applicationlist_active)) {
            Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['inactive']);
            $this->Template->message = Message::generate();

            return;
        }

        $maxParticipants = $this->item->get(
            FerienpassConfig::getInstance()->offer_attribute_applicationlist_max
        );
        $attendances = Attendance::findByOffer($this->item->get('id'));
        $view = Participant::getInstance()->getMetaModel()->getView($this->metamodel_child_list_view);
        $fields = $view->getSettingNames();
        $rows = [];

        if (null !== $attendances) {
            // Create table head
            foreach ($fields as $field) {
                $rows[0][] = Participant::getInstance()->getMetaModel()->getAttribute($field)->get('name');
            }

            $this->fetchOwnerAttribute(Participant::getInstance()->getMetaModel());

            // Walk each attendee
            while ($attendances->next()) {
                $participant = Participant::getInstance()->findById($attendances->participant);
                $values = [];

                // Participant is not existent
                if (null === $participant) {
                    // Delete attendance too
                    $attendances->current()->delete(); # this will sync the entire list

                    continue;
                }

                foreach ($fields as $field) {
                    $value = $participant->parseAttribute($field, null, $view)['text'];

                    // Inherit parent's data
                    if (!strlen($value)) {
                        $value = $participant->get($this->ownerAttribute->getColName())[$field];
                    }

                    $values[] = $value;
                }

                $rows[] = $values;
            }
        }

        if (empty($rows)) {
            Message::addWarning($GLOBALS['TL_LANG']['MSC']['noAttendances']);
        } else {
            $this->useHeader = true;
            $this->max_participants = $maxParticipants;

            // Define row class callback
            $rowClassCallback = function ($j, $arrRows, $objModule) {
                if ($j == ($objModule->max_participants - 1) && $j != count($arrRows) - 1) {
                    return 'last_attendee';
                } elseif ($j >= $objModule->max_participants) {
                    return 'waiting_list';
                }

                return '';
            };

            $this->Template->dataTable = Table::getDataArray($rows, 'application-list', $this, $rowClassCallback);

            // Add download button
            $this->Template->download = $this->document ? sprintf
            (
                '<a href="%1$s" title="%3$s" class="download_list">%2$s</a>',
                $this->addToUrl('action=download_list'),
                $GLOBALS['TL_LANG']['MSC']['downloadList'][0],
                $GLOBALS['TL_LANG']['MSC']['downloadList'][1]
            ) : '';

            if (\Input::get('action') == 'download_list') {
                if (($objDocument = Document::findByPk($this->document)) === null) {
                    Message::addError($GLOBALS['TL_LANG']['MSC']['document']['export_error']);
                } else {
                    $objDocument->outputToBrowser($attendances);
                }
            }
        }

        $this->addRenderedMetaModelToTemplate();

        $this->Template->message = Message::generate();
    }


    /**
     * Add the rendered meta model of this offer to the template
     */
    protected function addRenderedMetaModelToTemplate()
    {
        $itemRenderer = new ItemList();

        $itemRenderer
            ->setMetaModel($this->metamodel, $this->metamodel_rendersettings)
            ->addFilterRule(new StaticIdList([$this->item->get('id')]));

        $this->Template->metamodel = $itemRenderer->render($this->metamodel_noparsing, $this);
    }
}
