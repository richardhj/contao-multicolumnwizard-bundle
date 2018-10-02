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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\PassEditionTask;


use Contao\Config;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ModifyLabelListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\PassEditionTask
 */
class ModifyLabelListener
{

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ModifyLabelListener constructor.
     *
     * @param TranslatorInterface $translator The translator.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param ModelToLabelEvent $event The event.
     */
    public function handle(ModelToLabelEvent $event): void
    {
        $environment    = $event->getEnvironment();
        $dataDefinition = $environment->getDataDefinition();
        if ('tl_ferienpass_edition_task' !== $dataDefinition->getName()) {
            return;
        }

        $model = $event->getModel();
        $args  = $event->getArgs();

        switch ($model->getProperty('type')) {
            case 'custom':
                $args['type'] = $model->getProperty('title');
                break;

            case 'application_system':
                $args['type'] = sprintf(
                    '%s <span class="tl_gray">(%s)</span>',
                    $args['type'],
                    $this->translator->trans(
                        'MSC.application_system.' . $model->getProperty('application_system'),
                        [],
                        'contao_default'
                    )
                );
                break;
        }

        $event->setArgs($args);
    }
}
