<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;

use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Ferienpass\Helper\Message;
use Ferienpass\Helper\Table;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Document;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IMetaModelsServiceContainer;
use MetaModels\ItemList;


/**
 * Class ApplicationListHost
 *
 * @package Ferienpass\Module
 */
class ApplicationListHost extends Item
{

    /**
     * Template
     *
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
        global $container;

        if (!$this->item->get('applicationlist_active')) {
            Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['inactive']);
            $this->Template->message = Message::generate();

            return;
        }

        /** @var IMetaModelsServiceContainer $metaModelsServiceContainer */
        $metaModelsServiceContainer = $container['metamodels-service-container'];
        $participantsMetaModel      = $metaModelsServiceContainer->getFactory()->getMetaModel('mm_participant');

        $maxParticipants  = $this->item->get('applicationlist_max');
        $view             = $participantsMetaModel->getView($this->metamodel_child_list_view);
        $fields           = $view->getSettingNames();
        $attendances      = Attendance::findByOffer($this->item->get('id'));
        $statusConfirmed  = AttendanceStatus::findConfirmed()->id;
        $statusWaitlisted = AttendanceStatus::findWaitlisted()->id;
        $rows             = [];

        if (null !== $attendances) {
            // Create table head
            foreach ($fields as $field) {
                $rows[0][] = $participantsMetaModel->getAttribute($field)->get('name');
            }

            $this->fetchOwnerAttribute($participantsMetaModel);

            // Walk each attendee
            while ($attendances->next()) {
                $values      = [];
                $participant = $participantsMetaModel->findById($attendances->participant);

                if (!in_array($attendances->status, [$statusConfirmed, $statusWaitlisted])) {
                    continue;
                }
                if (null === $participant) {
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
            $this->useHeader        = true;
            $this->max_participants = $maxParticipants;

            // Define row class callback
            $rowClassCallback = function ($j, $rows, $module) {
                if ($j === ($module->max_participants - 1) && $j !== count($rows) - 1) {
                    return 'last_attendee';
                } elseif ($j >= $module->max_participants) {
                    return 'waiting_list';
                }

                return '';
            };

            $this->Template->dataTable = Table::getDataArray($rows, 'application-list', $this, $rowClassCallback);

            $urlBuilder = UrlBuilder::fromUrl(\Environment::get('uri'));
            if ('download_list' === $urlBuilder->getQueryParameter('action')) {
                if (null === ($document = Document::findByPk($this->document))) {
                    Message::addError($GLOBALS['TL_LANG']['MSC']['document']['export_error']);
                } else {
                    $document->outputToBrowser($attendances);
                }
            }

            // Add download button
            $this->Template->download = $this->document ? sprintf(
                '<a href="%1$s" title="%3$s" class="download_list">%2$s</a>',
                $urlBuilder->setQueryParameter('action', 'download_list')->getUrl(),
                $GLOBALS['TL_LANG']['MSC']['downloadList'][0],
                $GLOBALS['TL_LANG']['MSC']['downloadList'][1]
            ) : '';
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