<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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

    const NAME = 'ferienpass.user-application.set-attendance';


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
