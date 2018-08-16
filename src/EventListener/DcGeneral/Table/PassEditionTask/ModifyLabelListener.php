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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\PassEditionTask;


use Contao\Config;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use Symfony\Component\Translation\TranslatorInterface;

class ModifyLabelListener
{

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ModifyLabelListener constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function handle(ModelToLabelEvent $event): void
    {
        $environment    = $event->getEnvironment();
        $dataDefinition = $environment->getDataDefinition();
        if ('tl_ferienpass_edition_task' !== $dataDefinition->getName()) {
            return;
        }

        $model = $event->getModel();
        $args  = $event->getArgs();

        foreach ($args as $k => $arg) {
            switch ($k) {
                case 'type':
                    switch ($arg) {
                        case 'custom':
                            $args[$k] = $model->getProperty('title');
                            break;

                        case 'application_system':
                            $args[$k] = sprintf(
                                '%s <span class="tl_gray">(%s)</span>',
                                $this->translator->trans(
                                    'tl_ferienpass_edition_task.type_options.' . $arg,
                                    [],
                                    'contao_tl_ferienpass_edition_task'
                                ),
                                $this->translator->trans(
                                    'MSC.application_system.' . $model->getProperty('application_system'),
                                    [],
                                    'contao_default'
                                )
                            );
                            break;

                        default:
                            $args[$k] =
                                $this->translator->trans(
                                    'tl_ferienpass_edition_task.type_options.' . $arg,
                                    [],
                                    'contao_tl_ferienpass_edition_task'
                                );
                            break;
                    }
                    break;

                case 'period_start':
                case 'period_stop':
                    $args[$k] = date(Config::get('datimFormat'), $arg);
                    break;
            }
        }

        $event->setArgs($args);
    }
}
