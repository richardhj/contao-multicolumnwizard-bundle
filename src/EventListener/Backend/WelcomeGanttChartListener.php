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

namespace Richardhj\ContaoFerienpassBundle\EventListener\Backend;


use Contao\Date;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Richardhj\ContaoFerienpassBundle\Entity\PassEditionTask;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WelcomeGanttChartListener
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * WelcomeGanttChartListener constructor.
     *
     * @param ManagerRegistry     $doctrine
     * @param EngineInterface     $templating
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EngineInterface $templating,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->doctrine   = $doctrine;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->router     = $router;
    }

    /**
     * Add the gantt chart to the welcome screen.
     *
     * @return string
     */
    public function onGetSystemMessages(): string
    {
        $GLOBALS['TL_CSS'][] = 'bundles/richardhjcontaoferienpass/gantt.scss|static';
        $GLOBALS['TL_CSS'][] = 'https://use.fontawesome.com/releases/v5.2.0/css/all.css';

        $numberMonths = 10;

        $startTime = strtotime('-3 months');
        $startTime = mktime(0, 0, 0, date('n', $startTime), 1, date('Y', $startTime));

        $stopTime = strtotime('+' . $numberMonths . ' months', $startTime);
        $stopTime = mktime(0, 0, 0, date('n', $stopTime), 1, date('Y', $stopTime));

        $numberDays = (new DateTime('@' . $stopTime))->diff(new DateTime('@' . $startTime))->format('%a');

        $header = [];
        for ($i = 0; $i < $numberMonths; $i++) {
            $time = strtotime('+' . $i . ' months', $startTime);

            $spanStart = $header[$i - 1]['column']['stop'] ?? 1;

            $header[] = [
                'column' => [
                    'start' => $spanStart,
                    'stop'  => $spanStart + date('t', $time),
                ],
                'label'  => Date::parse('M', $time)
            ];
        }

        $marker = [
            'column' => [
                'start' => ((int) (new DateTime('@' . mktime(0, 0, 0, date('n'), 0, date('Y'))))
                        ->diff(new DateTime('@' . $startTime))
                        ->format('%a')) + 1,
                'stop'  => ((int) (new DateTime())
                        ->diff(new DateTime('@' . $startTime))
                        ->format('%a')) + 2,
            ]
        ];

        $time = time();

        $elements = [];
        foreach ($this->doctrine->getRepository(PassEdition::class)->findAll() as $passEdition) {
            $passEditionTasks = $passEdition->getTasks();
            $criteria         = Criteria::create()->where(Criteria::expr()->gte('periodStop', $startTime));
            $passEditionTasks = $passEditionTasks->matching($criteria);

            $tasks = [];
            foreach ($passEditionTasks as $task) {
                $tasks[] = [
                    'id'          => $task->getId(),
                    'label'       => $task->getDisplayTitle(),
                    'start'       => $task->getPeriodStart(),
                    'description' => $this->getDescription($task),
                    'period'      => sprintf(
                        '%s — %s',
                        Date::parse('D, d. M Y H:i', $task->getPeriodStart()),
                        Date::parse('D, d. M Y H:i', $task->getPeriodStop())
                    ),
                    'editLink'    => [
                        'link'  => $this->translator->trans(
                            'tl_ferienpass_edition_task.edit.0',
                            [],
                            'contao_tl_ferienpass_edition_task'
                        ),
                        'title' => $this->translator->trans(
                            'tl_ferienpass_edition_task.edit.1',
                            [$task->getId()],
                            'contao_tl_ferienpass_edition_task'
                        ),
                        'href'  => $this->router->generate(
                            'contao_backend',
                            [
                                'do'    => 'ferienpass_editions',
                                'table' => 'tl_ferienpass_edition_task',
                                'pid'   => 'tl_ferienpass_edition::' . $passEdition->getId(),
                                'act'   => 'edit',
                                'id'    => 'tl_ferienpass_edition_task::' . $task->getId()
                            ]
                        ),
                    ],
                    'stop'        => $task->getPeriodStop(),
                    'isPast'      => $task->getPeriodStop() < $time,
                    'isUpcoming'  => $task->getPeriodStart() > $time,
                    'isActive'    => $task->getPeriodStart() <= $time && $task->getPeriodStop() >= $time,
                    'isCutLeft'   => $task->getPeriodStart() < $startTime,
                    'isCutRight'  => $task->getPeriodStop() > $stopTime,
                    'style'       => [
                        'background' => $this->getColor($task),
                        'color'      => $this->readableColor($task->getColor())
                    ],
                    'column'      => [
                        'start' => ((int) (new DateTime('@' . $task->getPeriodStart()))
                                ->diff(new DateTime('@' . $startTime))
                                ->format('%a')) + 1,
                        'stop'  => ((int) (new DateTime('@' . $task->getPeriodStop()))
                                ->diff(new DateTime('@' . $startTime))
                                ->format('%a')) + 2
                    ],
                ];
            }

            if (!$passEditionTasks->isEmpty()) {
                $elements[$passEdition->getTitle()] = $tasks;
            }
        }

        return $this->templating->render(
            'RichardhjContaoFerienpassBundle::Backend/be_welcome_gantt.html.twig',
            [
                'header'        => $header,
                'grid_columns'  => $numberDays,
                'marker'        => $marker,
                'elements'      => $elements,
                'notifications' => $notifications
            ]
        );
    }

    private function getDescription(PassEditionTask $task): string
    {
        if ($task->getType() === 'custom') {
            return (string) $task->getDescription();
        }

        if ('application_system' === $task->getType()) {
            return $this->translator->trans(
                'MSC.welcome_gantt.task_description.application_system.' . $task->getApplicationSystem(),
                [],
                'contao_default'
            );
        }

        return $this->translator->trans(
            'MSC.welcome_gantt.task_description.' . $task->getType(),
            [],
            'contao_default'
        );
    }

    private function getColor(PassEditionTask $task): string
    {
        if ('holiday' === $task->getType()) {
            return 'dea949';
        }

        if ('host_editing_stage' === $task->getType()) {
            return '119898';
        }

        if ('application_system' === $task->getType()) {
            return 'e87f89';
        }

        return $task->getColor();
    }

    private function readableColor($hexColor): string
    {
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        $squaredContrast = (
            $r * $r * .299 +
            $g * $g * .587 +
            $b * $b * .114
        );

        if ($squaredContrast > (130 ** 2)) {
            return '000000';
        }

        return 'ffffff';
    }
}
