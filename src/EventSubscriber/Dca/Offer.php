<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\EventSubscriber\Dca;


use Contao\MemberModel;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Richardhj\ContaoFerienpassBundle\DcGeneral\View\AttendanceAllocationView;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Haste\DateTime\DateTime;
use MetaModels\IMetaModelsServiceContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Offer
 *
 * @package Richardhj\ContaoFerienpassBundle\Subscriber\Dca
 */
class Offer implements EventSubscriberInterface
{

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            PopulateEnvironmentEvent::NAME => [
                ['enableOfferAttendancesView', DataProviderPopulator::PRIORITY + 50],
            ],
            ModelToLabelEvent::NAME        => [
                ['alterLabelInOfferAttendancesView', -10],
            ],
        ];
    }



    /**
     * Use the AttendanceAllocationView if applicable
     *
     * @param PopulateEnvironmentEvent $event
     */
    public function enableOfferAttendancesView(PopulateEnvironmentEvent $event)
    {
        $environment = $event->getEnvironment();

        // Already populated or not in Backend? Get out then.
        if ($environment->getView() || ('BE' !== TL_MODE)) {
            return;
        }

        $definition = $environment->getDataDefinition();

        // Not attendances for offer MetaModel
        if (!($definition->getName() === Attendance::getTable()
              && 'mm_ferienpass' === $definition->getBasicDefinition()->getParentDataProvider())
            || !$definition->hasBasicDefinition()
        ) {
            return;
        }

        // Set view
        $view = new AttendanceAllocationView();
        $view->setEnvironment($environment);
        $environment->setView($view);

        // Add "attendances" property
        /** @var Contao2BackendViewDefinitionInterface $viewSection */
        $viewSection = $definition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $listing     = $viewSection->getListingConfig();
        $formatter   = $listing->getLabelFormatter($definition->getName());

        $propertyNames   = $formatter->getPropertyNames();
        $propertyNames[] = 'attendances';
        $formatter->setPropertyNames($propertyNames);
    }


    /**
     * Show the participant's overall attendances and show an popup link
     *
     * @param ModelToLabelEvent $event
     */
    public function alterLabelInOfferAttendancesView(ModelToLabelEvent $event)
    {
        // Not attendances for offer MetaModel
        if (!$event->getEnvironment()->getView() instanceof AttendanceAllocationView) {
            return;
        }

        $model = $event->getModel();
        $args  = $event->getArgs();

        // Adjust the label
        foreach ($args as $k => $v) {
            switch ($k) {
                case 'attendances':
                    $args[$k] = sprintf(
                        '<a href="contao/main.php?do=metamodel_mm_participant&amp;table=tl_ferienpass_attendance&amp;pid=mm_participant::%1$u&amp;popup=1&amp;nb=1&amp;rt=%4$s" class="open_participant_attendances" title="%3$s" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%3$s\',\'url\':this.href});return false">%2$s</a>',
                        // Member ID
                        $model->getProperty('participant'),
                        // Link
                        '<i class="fa fa-external-link tl_gray"></i> ' . Attendance::countByParticipant(
                            $model->getProperty('participant')
                        ) . ' Anmeldungen gesamt',
                        // Member edit description
                        sprintf($GLOBALS['TL_LANG']['tl_member']['edit'][1], $model->getProperty('participant')),
                        REQUEST_TOKEN
                    );
                    break;

                case 'participant':
                    global $container;

                    // Wrap current content
                    $args[$k] = sprintf('<span class="name">%s</span>', $v);

                    /** @var IMetaModelsServiceContainer $serviceContainer */
                    $serviceContainer = $container['metamodels-service-container'];
                    $metaModel        = $serviceContainer->getFactory()->getMetaModel('mm_participant');
                    $participant      = $metaModel->findById($model->getProperty('participant'));
                    $dateOfBirth      = new DateTime('@' . $participant->get('dateOfBirth'));
                    $member           = MemberModel::findById($participant->get('pmember'));

                    // Add age
                    $args[$k] .= sprintf(
                        '<span class="age"><span title="%2$s" class="content">%1$s</span> Jahre</span>',
                        $dateOfBirth->getAge(),
                        'Alter zum aktuellen Zeitpunkt'
                    );

                    // Add postal
                    $args[$k] .= sprintf(
                        '<span class="postal">PLZ: <span class="content">%s</span></span>',
                        (null !== $member) ? $member->postal : '-'
                    );

                    break;
            }
        }

        $event->setArgs($args);
    }
}
