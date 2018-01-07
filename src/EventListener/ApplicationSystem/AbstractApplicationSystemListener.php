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

namespace Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem;


use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;

abstract class AbstractApplicationSystemListener
{

    /**
     * @var ApplicationSystemInterface
     */
    protected $applicationSystem;

    /**
     * AbstractApplicationSystemListener constructor.
     *
     * @param ApplicationSystemInterface $applicationSystem The application system.
     */
    public function __construct(ApplicationSystemInterface $applicationSystem)
    {
        $this->applicationSystem = $applicationSystem;
    }
}
