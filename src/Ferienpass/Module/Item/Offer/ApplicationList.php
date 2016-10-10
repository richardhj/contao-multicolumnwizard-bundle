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

use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use Haste\DateTime\DateTime;
use Haste\Form\Form;


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
//		$state = $this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE)) ? 'active' : 'inactive';
//		$this->Template->al_state = $state;

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

//		$this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList'][$state];

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
            $participantIds = Participant::getInstance()->byParentAndOfferFilter(
                $this->User->id,
                $this->item->get('id')
            )->getMatchingIds();
            $allowedParticipantsIds = [];
            $maxApplicationsPerDay = FerienpassConfig::getInstance()->max_applications_per_day;

            while ($participants->next()) {
                $dateOfBirth = new DateTime(
                    '@'.$participants
                        ->getItem()
                        ->get(FerienpassConfig::getInstance()->participant_attribute_dateofbirth)
                );
                $dateOffer = new DateTime(
                    '@'.$this->item
                        ->get(FerienpassConfig::getInstance()->offer_attribute_date_check_age)
                );

                $age = $dateOfBirth
                    ->getAge($dateOffer); # Use offer's date for diff check

                $isLimitReached = ($maxApplicationsPerDay && Attendance::countByParticipantAndDay(
                        $participants->getItem()->get('id')
                    ) >= $maxApplicationsPerDay) ? true : false;
                $isAttending = (in_array($participants->getItem()->get('id'), $participantIds));
                $isAgeAllowed = in_array(
                    $this->item->get('id'),
                    $this->item->getAttribute(FerienpassConfig::getInstance()->offer_attribute_age)->searchFor($age)
                );

                // Check if a participant is allowed for this offer and set the corresponding language key
                if ($isAttending) {
                    $languageKey = 'already_attending';
                } elseif (!$isAgeAllowed) {
                    $languageKey = 'age_not_allowed';
                } elseif ($isLimitReached) {
                    $languageKey = 'limit_reached';
                } else {
                    $languageKey = 'ok';
                    $allowedParticipantsIds[] = $participants->getItem()->get('id');
                }

                $options[] = [
                    'value'    => $participants->getItem()->get('id'),
                    'label'    => sprintf(
                        $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label'][$languageKey],
                        $participants->getItem()->parseAttribute(
                            FerienpassConfig::getInstance()->participant_attribute_name
                        )['text'] # = parsed participant name
                    ),
                    'disabled' => ($languageKey != 'ok'),
                ];
            }

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
                    'eval'      =>
                        [
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
                    if (in_array($participant, $allowedParticipantsIds) && Attendance::isNotExistent(
                            $participant,
                            $this->item->get('id')
                        )
                    ) {
                        // Set new attendance
                        $attendance = new Attendance();
                        $attendance->tstamp = time();
                        $attendance->offer_id = $this->item->get('id');
                        $attendance->participant_id = $participant;

                        // Fetch status
                        $status = $attendance->getStatus();
                        $attendance->status = $status->id;

                        // Save attendance
                        $attendance->save();

                        $participantName = Participant::getInstance()->findById($participant)->parseAttribute(
                            FerienpassConfig::getInstance()->participant_attribute_name
                        )['text'];

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
