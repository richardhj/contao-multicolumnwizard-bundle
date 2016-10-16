<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module\Item\Offer;

use Ferienpass\Event\ApplicationListSubscriber;
use Ferienpass\Event\CreateParticipantOptionsForApplicationListEvent;
use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use Haste\Form\Form;
use Symfony\Component\EventDispatcher\EventDispatcher;


/**
 * Class OfferApplicationList
 * @package Ferienpass\Module\SingleOffer
 */
class ApplicationList extends Item
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
        global $container;
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container['event-dispatcher'];

        // Stop if the procedure is not used
        if (!$this->item->get(FerienpassConfig::getInstance()->offer_attribute_applicationlist_active)) {
            $this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList']['inactive'];

            return;
        }

        // Stop if the offer is in the past
        if (time() >= $this->item->get(FerienpassConfig::getInstance()->offer_attribute_date_check_age)) {
            $this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList']['past'];

            return;
        }

        $countParticipants = Attendance::countParticipants($this->item->get('id'));
        $maxParticipants = $this->item->get(FerienpassConfig::getInstance()->offer_attribute_applicationlist_max);

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
            $participants = Participant::getInstance()->findByParent($this->User->id);

            if (0 === $participants->getCount()) {
                Message::addWarning($GLOBALS['TL_LANG']['MSC']['noParticipants']);
            }

            // Build options
            $options = [];

            while ($participants->next()) {
                $options[] = [
                    'value' => $participants->getItem()->get('id'),
                    'label' => $participants
                        ->getItem()
                        ->parseAttribute(FerienpassConfig::getInstance()->participant_attribute_name)['text'],
                ];
            }

            // Subscriber will disable participants if they are not allowed for this offer
            $dispatcher->addSubscriber(new ApplicationListSubscriber());

            $event = new CreateParticipantOptionsForApplicationListEvent($participants, $this->item, $options);
            $dispatcher->dispatch(CreateParticipantOptionsForApplicationListEvent::NAME, $event);

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
                    // Check if participant id allowed here and attendance not existent yet
                    if (Attendance::isNotExistent($participant, $this->item->get('id'))) {
                        // Set new attendance
                        $attendance = new Attendance();
                        $attendance->tstamp = time();
                        $attendance->offer = $this->item->get('id');
                        $attendance->participant = $participant;

                        // Fetch status
                        $status = $attendance->fetchStatus();
                        $attendance->status = $status->id;

                        // Save attendance
                        $attendance->save();

                        $participantName = Participant::getInstance()
                            ->findById($participant)
                            ->parseAttribute(FerienpassConfig::getInstance()->participant_attribute_name)
                        ['text'];

                        // Add message corresponding to attendance's status
                        switch ($status->type) {
                            case 'confirmed':
                                Message::addConfirmation(
                                    sprintf(
                                        $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                                        $participantName
                                    )
                                );
                                break;

                            case 'waiting':
                                Message::addWarning(
                                    sprintf(
                                        $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                                        $participantName
                                    )
                                );
                                break;

                            case 'error':
                                Message::addError(
                                    sprintf(
                                        $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                                        $participantName
                                    )
                                );
                                break;
                        }
                    } // Attendance already exists
                    else {
                        Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['error']);

                        return;
                    }
                }

                // Reload page to show confirmation message
                \Controller::reload();
            }

            // Get the form as string
            $this->Template->form = $form->generate();
        }

        $this->Template->message = Message::generate();
    }
}
