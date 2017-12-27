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

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;

use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class AbstractApplicationSystem
 * @package Richardhj\ContaoFerienpassBundle\ApplicationSystem
 */
abstract class AbstractApplicationSystem implements EventSubscriberInterface
{

    /**
     * @var ApplicationSystem
     */
    private $model;


    /**
     * @param ApplicationSystem $model
     *
     * @return self
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }


    /**
     * @return ApplicationSystem
     */
    public function getModel()
    {
        return $this->model;
    }


    /**
     * @param IItem $offer
     * @param IItem $participant
     */
    protected function setNewAttendanceInDatabase(IItem $offer, IItem $participant)
    {
        // Check if participant id allowed here and attendance not existent yet
        if (Attendance::isNotExistent($participant->get('id'), $offer->get('id'))) {
            $attendance = new Attendance();
            $attendance->tstamp = time();
            $attendance->created = time();
            $attendance->offer = $offer->get('id');
            $attendance->participant = $participant->get('id');
            $attendance->save();

        } // Attendance already exists
        else {
            Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['error']);
        }
    }
}
