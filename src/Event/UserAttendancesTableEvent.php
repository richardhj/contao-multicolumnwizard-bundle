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

use Symfony\Component\EventDispatcher\Event;


/**
 * Class UserAttendancesTableEvent
 *
 * @package Richardhj\ContaoFerienpassBundle\Event
 */
class UserAttendancesTableEvent extends Event
{

    public const NAME = 'richardhj.ferienpass.user-application.table-rows';

    /**
     * @var array
     */
    private $items;

    /**
     * UserAttendancesTableEvent constructor.
     *
     * @param array $items
     */
    public function __construct(array $items)
    {

        $this->items = $items;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
