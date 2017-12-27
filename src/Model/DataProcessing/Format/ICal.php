<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;

use DateTime;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
use MetaModels\IItem;
use MetaModels\IItems;


/**
 * Class ICal
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format
 */
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
    private $items;

    /**
     * @return DataProcessing|\Model
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
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

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
    public function processItems(): FormatInterface
    {
        $files = [];
        $path  = sprintf(
            '%s/%s.ics',
            $this->getModel()->getTmpPath(),
            $this->getModel()->export_file_name
        );

        // Save the iCal in the tmp path
        $this
            ->getModel()
            ->getMountManager()
            ->put('local://' . $path, $this->createICal());

        $files[] = array_merge(
            $this->getModel()->getMountManager()->getMetadata('local://' . $path),
            [
                'basename' => basename($path)
            ]
        );

        $this->files = $files;

        return $this;
    }

    /**
     * Create the iCal for given items
     *
     * @return string
     */
    protected function createICal(): string
    {
        $calendar       = new Calendar(\Environment::get('httpHost'));
        $iCalProperties = deserialize($this->getModel()->ical_fields);

        /** @var IItem $item */
        foreach ($this->getItems() as $item) {
            $dateAttribute = ToolboxOfferDate::fetchDateAttribute($item);

            $date = $item->get($dateAttribute->getColName());
            if (null === $date) {
                continue;
            }

            foreach ($date as $period) {
                $event = new Event();

                $dateTime = new DateTime('@' . $period['start']);
                $event->setDtStart($dateTime);
                $dateTime = new DateTime('@' . $period['end']);
                $event->setDtEnd($dateTime);

                /**
                 * @var array $property [ical_field]          The property identifier
                 *                      [metamodel_attribute] The property assigned MetaModel attribute name
                 */
                foreach ($iCalProperties as $property) {
                    switch ($property['ical_field']) {
                        case 'summary':
                            $event->setSummary($item->get($property['metamodel_attribute']));
                            break;

                        case 'description':
                            $event->setDescription($item->get($property['metamodel_attribute']));
                            break;
                    }
                }

                $event->setUseTimezone(true);
                $calendar->addComponent($event);
            }
        }

        return $calendar->render();
    }
}
