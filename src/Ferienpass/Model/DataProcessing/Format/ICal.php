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
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

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
    public function processItems(): FormatInterface
    {
        $files = [];
        $path = sprintf(
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
     * @param array  $files
     * @param string $filesystem
     */
    public function backSyncFiles(array $files, string $filesystem): void
    {
        // Back sync is not allowed. Nothing to do here.
    }

    /**
     * Create the iCal for given items
     *
     * @return string
     */
    protected function createICal(): string
    {
        $vCalendar = new Calendar(\Environment::get('httpHost'));

        $iCalProperties = deserialize($this->getModel()->ical_fields);

        // Walk each item
        while (null !== $this->getItems() && $this->getItems()->next()) {
            $vEvent = new Event();

            /** @var array $arrProperty [ical_field] The property identifier
             *                          [metamodel_attribute] The property assigned MetaModel attribute name */
            foreach ($iCalProperties as $arrProperty) {
                switch ($arrProperty['ical_field']) {
                    //TODO #4 this most likely will not work for the date_period attribute
                    case 'dtStart':
                        try {
                            $objDate = new DateTime(
                                '@' . $this->getItems()->getItem()->get($arrProperty['metamodel_attribute'])
                            );
                            $vEvent->setDtStart($objDate);
                        } catch (\Exception $e) {
                            continue 3;
                        }
                        break;

                    case 'dtEnd':
                        try {
                            $objDate = new DateTime(
                                '@' . $this->getItems()->getItem()->get($arrProperty['metamodel_attribute'])
                            );
                            $vEvent->setDtEnd($objDate);
                        } catch (\Exception $e) {
                            continue 3;
                        }
                        break;

                    case 'summary':
                        $vEvent->setSummary($this->getItems()->getItem()->get($arrProperty['metamodel_attribute']));
                        break;

                    case 'description':
                        $vEvent->setDescription(
                            $this->getItems()->getItem()->get($arrProperty['metamodel_attribute'])
                        );
                        break;
                }
            }

            $vEvent->setUseTimezone(true);
            $vCalendar->addComponent($vEvent);
        }

        return $vCalendar->render();
    }
}
