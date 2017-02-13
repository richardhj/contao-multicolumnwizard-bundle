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


use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Ferienpass\Model\DataProcessing;
use Ferienpass\Model\DataProcessing\FormatInterface;
use Haste\DateTime\DateTime;
use MetaModels\IItems;

class ICal implements FormatInterface
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

    public function __construct($model, $offers)
    {
        $this->model  = $model;
        $this->offers = $offers;
    }


    /**
     * @return DataProcessing|\Model
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
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * {@inheritdoc}
     */
    public function processOffers()
    {
        $path = sprintf('%s/%s.ics', $this->getModel()->getTmpPath(), $this->getModel()->export_file_name);

        $this->getModel()->getMountManager()
            ->put('local://' . $path, $this->createICal());

        $arrToExport[] = array_merge(
            $this->getModel()->getMountManager()->getMetadata('local://' . $path),
            [
                'basename' => basename($path)
            ]
        );

        return $this;
    }


    protected function createICal()
    {
        $vCalendar = new Calendar(\Environment::get('httpHost'));

        $arrICalProperties = deserialize($this->getModel()->ical_fields);

        // Walk each offer
        while (null !== $this->getOffers() && $this->getOffers()->next()) {
            // Process published offers exclusively
            if (!$this->getOffers()->getItem()->get('published')) {
                continue;
            }

            // filter by host
            //@todo real quick'n'dirty
            if ($this->getModel()->id == 7 && $this->getOffers()->getItem()->get('host')['id'] != 132) {
                continue;
            }

            $vEvent = new Event();

            /** @var array $arrProperty [ical_field] The property identifier
             *                          [metamodel_attribute] The property assigned MetaModel attribute name */
            foreach ($arrICalProperties as $arrProperty) {
                switch ($arrProperty['ical_field']) {
                    case 'dtStart':
                        try {
                            $objDate = new DateTime(
                                '@' . $this->getOffers()->getItem()->get($arrProperty['metamodel_attribute'])
                            );
                            $vEvent->setDtStart($objDate);
                        } catch (\Exception $e) {
                            continue 3;
                        }
                        break;

                    case 'dtEnd':
                        try {
                            $objDate = new DateTime(
                                '@' . $this->getOffers()->getItem()->get($arrProperty['metamodel_attribute'])
                            );
                            $vEvent->setDtEnd($objDate);
                        } catch (\Exception $e) {
                            continue 3;
                        }
                        break;

                    case 'summary':
                        $vEvent->setSummary($this->getOffers()->getItem()->get($arrProperty['metamodel_attribute']));
                        break;

                    case 'description':
                        $vEvent->setDescription(
                            $this->getOffers()->getItem()->get($arrProperty['metamodel_attribute'])
                        );
                        break;
                }
            }

            // skip events that pollute the calendar
            //@todo really quick'n'dirty
            $objDateStart = new DateTime('@' . $this->getOffers()->getItem()->get('date'));
            $objDateEnd   = new DateTime('@' . $this->getOffers()->getItem()->get('date_end'));
            if ($objDateEnd->diff($objDateStart)->d > 1) {
                continue;
            }

            $vEvent->setUseTimezone(true);
            $vCalendar->addComponent($vEvent);
        }

        return $vCalendar->render();
    }


}