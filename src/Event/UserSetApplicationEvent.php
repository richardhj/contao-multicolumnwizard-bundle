<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Event;

use MetaModels\IItem;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class UserSetApplicationEvent
 *
 * @package Richardhj\ContaoFerienpassBundle\Event
 */
class UserSetApplicationEvent extends Event
{

    public const NAME = 'richardhj.ferienpass.user-application.set-attendance';

    /**
     * The offer.
     *
     * @var IItem
     */
    protected $offer;

    /**
     * The participant.
     *
     * @var IItem
     */
    protected $participant;

    /**
     * UserSetApplicationEvent constructor.
     *
     * @param IItem $offer       The offer.
     * @param IItem $participant The participant.
     */
    public function __construct(IItem $offer, IItem $participant)
    {
        $this->offer       = $offer;
        $this->participant = $participant;
    }

    /**
     * @return IItem
     */
    public function getOffer(): IItem
    {
        return $this->offer;
    }

    /**
     * @return IItem
     */
    public function getParticipant(): IItem
    {
        return $this->participant;
    }
}
