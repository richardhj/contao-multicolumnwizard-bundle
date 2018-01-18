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

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\Controller;
use Contao\System;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Event\UserSetApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\Form\Form;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class UserApplication
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserApplication extends Item
{

    /**
     * @var string
     */
    protected $strTemplate = 'mod_offer_applicationlist';


    /**
     * Generate the module
     */
    protected function compile()
    {
        $container = System::getContainer();
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container->get('event_dispatcher');
        /** @var Participant $participantsModel */
        $participantsModel = $container->get('richardhj.ferienpass.model.participant');

        // Stop if the procedure is not used
        if (!$this->item->get('applicationlist_active')) {
            $this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList']['inactive'];

            return;
        }

        // Stop if the offer is in the past
        if (time() >= ToolboxOfferDate::offerStart($this->item)) {
            $this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList']['past'];

            return;
        }

        $countParticipants = Attendance::countParticipants($this->item->get('id'));
        $maxParticipants = $this->item->get('applicationlist_max');

        $availableParticipants = $maxParticipants - $countParticipants;

        if ($maxParticipants) {
            if ($availableParticipants < -10) {
                $this->Template->booking_state_code = 4;
                $this->Template->booking_state_text = 'Es sind keine Plätze mehr verfügbar<br>und die Warteliste ist ebenfalls voll.';
            } elseif ($availableParticipants < 1) {
                $this->Template->booking_state_code = 3;
                $this->Template->booking_state_text = 'Es sind keine freien Plätze mehr verfügbar,<br>aber Sie können sich auf die Warteliste eintragen.';
            } elseif ($availableParticipants < 4) {
                $this->Template->booking_state_code = 2;
                $this->Template->booking_state_text = 'Es sind nur noch wenige Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
            } else {
                $this->Template->booking_state_code = 1;
                $this->Template->booking_state_text = 'Es sind noch Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
            }
        } else {
            $this->Template->booking_state_code = 0;
            $this->Template->booking_state_text = 'Das Angebot hat keine Teilnehmer-Beschränkung.<br>Sie können sich jetzt für das Angebot anmelden.';
        }


        if (FE_USER_LOGGED_IN && $this->User->id) {
            $participants = $participantsModel->findByParent($this->User->id);

            if (0 === $participants->getCount()) {
                Message::addWarning($GLOBALS['TL_LANG']['MSC']['noParticipants']);
            }

            // Build options
            $options = [];

            while ($participants->next()) {
                $options[] = [
                    'value' => $participants->getItem()->get('id'),
                    'label' => $participants->getItem()->parseAttribute('name')['text'],
                ];
            }

            $event = new BuildParticipantOptionsForUserApplicationEvent($participants, $this->item, $options);
            $dispatcher->dispatch(BuildParticipantOptionsForUserApplicationEvent::NAME, $event);

            $options = $event->getResult();

            // Create form instance
            $form = new Form(
                'al'.$this->id, 'POST', function ($haste) {
                /** @noinspection PhpUndefinedMethodInspection */
                return $haste->getFormId() === \Input::post('FORM_SUBMIT');
            }
            );

            $form->addFormField(
                'participant',
                [
                    'label'     => $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['label'],
                    'inputType' => 'select_disabled_options',
                    'eval'      => [
                        'options'     => $options,
                        'addSubmit'   => true,
                        'slabel'      => $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['slabel'],
                        'multiple'    => true,
                        'mandatory'   => true,
                        'chosen'      => true,
                        'placeholder' => 'Hier klicken und Teilnehmer auswählen' //@todo lang
                    ],
                ]
            );

            // Validate the form
            if ($form->validate()) {
                // Process new applications
                foreach ((array)$form->fetch('participant') as $participant) {
                    // Trigger event and let the application system set the attendance
                    $event = new UserSetApplicationEvent(
                        $this->item,
                        $participantsModel->findById($participant)
                    );
                    $dispatcher->dispatch(UserSetApplicationEvent::NAME, $event);
                }

                // Reload page to show confirmation message
                Controller::reload();
            }

            // Get the form as string
            $this->Template->form = $form->generate();
        }

        $this->Template->message = Message::generate();
    }
}
