<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 12.08.18
 * Time: 12:04
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\Backend;


use Doctrine\Common\Persistence\ManagerRegistry;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Richardhj\ContaoFerienpassBundle\Entity\PassEditionTask;
use Symfony\Component\Templating\EngineInterface;

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
     * WelcomeGanttChartListener constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param EngineInterface $templating
     */
    public function __construct(ManagerRegistry $doctrine, EngineInterface $templating)
    {
        $this->doctrine   = $doctrine;
        $this->templating = $templating;
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

        $year         = date('Y');
        $numberDays   = date('z', mktime(0, 0, 0, 12, 31, $year)) + 1;
        $numberMonths = 12;

        $header = [];
        for ($month = 1; $month <= $numberMonths; $month++) {
            $time = mktime(0, 0, 0, $month, 1, $year);

            $spanStart = $header[$month - 2]['column']['stop'] ?? 1;

            $header[] = [
                'column' => [
                    'start' => $spanStart,
                    'stop'  => $spanStart + date('t', $time)
                ],
                'label'  => date('M', $time)
            ];
        }

        $marker = [
            'column' => [
                'start' => date('z', mktime(0, 0, 0, date('n'), 0, $year)) + 2,
                'stop'  => date('z')
            ]
        ];

        $time = time();

        $elements = [];
        foreach ($this->doctrine->getRepository(PassEdition::class)->findAll() as $passEdition) {
            $tasks = [];
            /** @var PassEditionTask $task */
            foreach ($passEdition->getTasks() as $task) {
                $tasks[] = [
                    'label'         => $task->getDisplayTitle(),
                    'start'         => $task->getPeriodStart(),
                    'stop'          => $task->getPeriodStop(),
                    'isPast'        => $task->getPeriodStop() < $time,
                    'style'         => [
                        'background' => $task->getColor() ?: null,
                        'color'      => $this->readableColor($task->getColor())
                    ],
                    'column'        => [
                        'start' => date('z', $task->getPeriodStart()),
                        'stop'  => date('z', $task->getPeriodStop())
                    ],
                ];
            }

            $elements[$passEdition->getTitle()] = $tasks;
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
