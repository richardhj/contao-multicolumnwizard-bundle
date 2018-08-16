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

use Contao\Environment;
use DateTime;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
use MetaModels\IItem;
use MetaModels\IItems;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class ICal
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format
 */
class ICal implements FormatInterface
{

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $kernelProjectDir;

    /**
     * ICal constructor.
     *
     * @param Filesystem $filesystem       The filesystem component.
     * @param string     $kernelProjectDir The kernel project directory.
     */
    public function __construct(Filesystem $filesystem, string $kernelProjectDir)
    {
        $this->filesystem       = $filesystem;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * Process the items and provide the files
     *
     * @param IItems         $items The items to process.
     *
     * @param DataProcessing $model
     *
     * @return array
     *
     * @throws IOException
     */
    public function processItems(IItems $items, DataProcessing $model): array
    {
        $path = sprintf(
            '%s/%s.ics',
            $model->getTmpPath(),
            $model->export_file_name
        );

        // Save the iCal in the tmp path
        $this->filesystem->dumpFile($this->kernelProjectDir.'/'.$path, $this->createICal($items, $model));

        return [$path];
    }

    /**
     * Create the iCal for given items
     *
     * @param IItems         $items
     *
     * @param DataProcessing $model
     *
     * @return string
     */
    private function createICal(IItems $items, DataProcessing $model): string
    {
        $calendar       = new Calendar(Environment::get('httpHost'));
        $iCalProperties = deserialize($model->ical_fields);

        /** @var IItem $item */
        foreach ($items as $item) {
            $dateAttribute = ToolboxOfferDate::fetchDateAttribute($item);

            $date = $item->get($dateAttribute->getColName());
            if (null === $date) {
                continue;
            }

            foreach ((array)$date as $period) {
                $event = new Event();

                $dateTime = new DateTime('@'.$period['start']);
                $event->setDtStart($dateTime);
                $dateTime = new DateTime('@'.$period['end']);
                $event->setDtEnd($dateTime);

                /**
                 * @var array $property [ical_field]          The property identifier
                 *                      [metamodel_attribute] The property assigned MetaModel attribute name
                 */
                foreach ((array)$iCalProperties as $property) {
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
