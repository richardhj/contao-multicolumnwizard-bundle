<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Module\Item\Offer;


use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use Haste\Form\Form;
use MetaModels\Attribute\IAttribute;
use MetaModels\FrontendEditingItem;


class AddAttendeeHost extends Item
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_offer_addattendeehost';


    /**
     * {@inheritdoc}
     * Include permission check
     */
    public function generate()
    {
        return parent::generate(true);
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $form = new Form(
            'tl_add_attendee_host', 'POST', function ($haste) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $haste->getFormId() === \Input::post('FORM_SUBMIT');
        }
        );

        /*
         * Fetch participant model attributes
         */
        $columnFieldsDca = [];
        $memberGroups = deserialize($this->User->groups);

        $dcaCombine = $this->database
            ->prepare(
                "SELECT * FROM tl_metamodel_dca_combine WHERE fe_group IN(".implode(',', $memberGroups).") AND pid=?"
            )
            ->limit(1)
            ->execute(Participant::getInstance()->getMetaModel()->get('id'));

        // Throw exception if no dca combine setting is set
        if (!$dcaCombine->numRows) {
            throw new \RuntimeException(
                sprintf(
                    'No dca combine setting found for MetaModel ID %u and member groups %s found',
                    Participant::getInstance()->getMetaModel()->get('id'),
                    var_export($memberGroups, true)
                )
            );
        }

        // Get the dca settings
        $dcaDatabase = $this->database
            ->prepare("SELECT * FROM tl_metamodel_dca WHERE id=?")
            ->execute($dcaCombine->dca_id);

        $dcaSettingDatabase = $this->database
            ->prepare(
                "SELECT a.colname,s.* FROM tl_metamodel_attribute a INNER JOIN tl_metamodel_dcasetting s ON a.id=s.attr_id WHERE s.pid=?"
            )
            ->execute($dcaDatabase->id);

        // Fetch all dca settings as associative array
        $dcaSettings = array_reduce(
            $dcaSettingDatabase->fetchAllAssoc(),
            function ($result, $item) {
                $result[$item['colname']] = $item;

                return $result;
            },
            []
        );

        // Exit if a new item creation is not allowed
        if (!$dcaDatabase->iscreatable) {
            Message::addError($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);

            $this->Template->message = Message::generate();

            return;
        }

        // Add all published attributes and override the dca settings in the field definition
        /**
         * @var string     $attributeName
         * @var IAttribute $attribute
         */
        foreach (Participant::getInstance()->getMetaModel()->getAttributes() as $attributeName => $attribute) {
            if (!$dcaSettings[$attributeName]['published']) {
                continue;
            }

            $columnFieldsDca[$attributeName] = $attribute->getFieldDefinition($dcaSettings[$attributeName]);
        }

        $form->addFormField(
            'attendees',
            [
                'inputType' => 'multiColumnWizard',
                'eval'      => [
                    'mandatory'    => true,
                    'columnFields' => $columnFieldsDca,
                ],
            ]
        );

        $form->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['submit']);

        if ($form->validate()) {
            $participantsToAdd = $form->fetch('attendees');

            // Create a new model for each participant
            foreach ($participantsToAdd as $participantRow) {
                $participant = new FrontendEditingItem(Participant::getInstance()->getMetaModel(), []);

                // Set each attribute in participant model
                foreach ($participantRow as $attributeName => $value) {
                    $participant->set($attributeName, $value);
                }

                $participant->save();

                // Create an attendance for this participant and offer
                $attendance = new Attendance();
                $attendance->tstamp = time();
                $attendance->offer_id = $this->item->get('id');
                $attendance->participant_id = $participant->get('id');
                $attendance->status = $attendance->getStatus()->id;
                $attendance->save();

            }

            Message::addConfirmation(
                sprintf($GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['confirmation'], count($participantsToAdd))
            );
            \Controller::reload();
        }

        $this->Template->message = Message::generate();
        $this->Template->form = $form->generate();
    }
}
