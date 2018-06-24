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

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\NoOp;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

class SystemMessagesListener
{

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * SystemMessagesListener constructor.
     *
     * @param ApplicationSystemInterface $applicationSystem
     * @param TranslatorInterface        $translator
     */
    public function __construct(ApplicationSystemInterface $applicationSystem, TranslatorInterface $translator)
    {
        $this->applicationSystem = $applicationSystem;
        $this->translator        = $translator;
    }

    /**
     * Display the current application system at the back end start page
     *
     * @return string
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function onGetSystemMessages(): string
    {
        if (!$this->applicationSystem instanceof NoOp) {
            $type = $this->applicationSystem->getModel()->type;
            $name = $this->translator->trans('MSC.ferienpass.application-system.' . $type, [], 'contao_default');

            return sprintf('<p class="tl_info">Es läuft aktuell das Anmeldesystem <strong>%s</strong></p>', $name);
        }

        return '<p class="tl_error">Es läuft aktuell <strong>kein</strong> Anmeldesystem. Anmeldungen sind <strong>nicht möglich.</strong></p>';
    }
}
