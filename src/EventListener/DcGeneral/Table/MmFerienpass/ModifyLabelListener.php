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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;


use Contao\MemberModel;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ParentView;
use Haste\DateTime\DateTime;
use MetaModels\Factory as MetaModelsFactory;
use Richardhj\ContaoFerienpassBundle\DcGeneral\View\AttendanceAllocationView;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;

class ModifyLabelListener
{

    /**
     * @var Participant
     */
    private $participantsModel;

    /**
     * ModifyLabelListener constructor.
     *
     * @param Participant $participantsModel The participants model.
     */
    public function __construct(Participant $participantsModel)
    {
        $this->participantsModel = $participantsModel;
    }

    /**
     * Show the participant's overall attendances and show an popup link
     *
     * @param ModelToLabelEvent $event The event.
     */
    public function handle(ModelToLabelEvent $event): void
    {
        $environment    = $event->getEnvironment();
        $dataDefinition = $environment->getDataDefinition();
        $view           = $environment->getView();

        if (!($view instanceof ParentView && 'tl_ferienpass_attendance' === $dataDefinition->getName())) {
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
                    // Wrap current content
                    $args[$k] = sprintf('<span class="name">%s</span>', $v);

                    $participant = $this->participantsModel->findById($model->getProperty('participant'));
                    if (null === $participant) {
                        return;
                    }

                    $dateOfBirth = new DateTime('@' . $participant->get('dateOfBirth'));
                    $member      = MemberModel::findById($participant->get('pmember'));

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
