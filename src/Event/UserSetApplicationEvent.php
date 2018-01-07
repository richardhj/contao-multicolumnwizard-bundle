<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Event;

use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class UserSetApplicationEvent
 * @package Richardhj\ContaoFerienpassBundle\Event
 */
class UserSetApplicationEvent extends Event
{

    const NAME = 'richardhj.ferienpass.user-application.set-attendance';


    /**
     * @var IItem
     */
    protected $offer;


    /**
     * @var IItem
     */
    protected $participant;


    /**
     * UserSetApplicationEvent constructor.
     *
     * @param IItem $offer
     * @param IItem $participant
     */
    public function __construct(IItem $offer, IItem $participant)
    {
        $this->offer = $offer;
        $this->participant = $participant;
    }


    /**
     * @return IItem
     */
    public function getOffer()
    {
        return $this->offer;
    }


    /**
     * @return IItem
     */
    public function getParticipant()
    {
        return $this->participant;
    }
}
