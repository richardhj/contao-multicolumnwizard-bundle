<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\BackendModule;


use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\SimpleQuery;


class EraseMemberData extends \BackendModule
{

    protected $strTemplate = 'dcbe_general_edit';


    /**
     * Generate the module
     * @return string
     */
    public function generate()
    {
        if (!\BackendUser::getInstance()->isAdmin) {
            return sprintf('<p class="tl_gerror">%s</p>', 'keine Berechtigung');
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $output = '';
        $formSubmit = 'erase_member_data';
        $memberGroup = \MemberGroupModel::findByPk('2'); // @todo
        $members = \MemberModel::findByGroups(serialize([$memberGroup->id]));
        $attendances = Attendance::findAll();
        $getParticipantsFilter = function () use ($members) {
            $filter = new Filter(Participant::getInstance()->getMetaModel());
            if (null !== $members) {
                $rule = new SimpleQuery(
                    sprintf(
                        '%1$s WHERE %2$s IN (%3$s) OR %2$s=0',
                        Participant::getInstance()->getMetaModel()->getTableName(),
                        Participant::getInstance()->getOwnerAttribute()->getColName(),
                        implode(',', $members->fetchEach('id'))
                    )
                );

                $filter->addFilterRule($rule);
            }

            return $filter;
        };
        $participants = Participant::getInstance()->getMetaModel()->findByFilter($getParticipantsFilter());

        /** @var \CheckBox|\Widget $confirmCheckbox */
        $confirmCheckbox = new \CheckBox(null);
        $confirmCheckbox->options = [
            [
                'value' => 1,
                'label' => 'Ich bin mir bewusst, dass die Daten unwiderruflich gelöscht werden.',
            ],
        ];
        $confirmCheckbox->name = 'confirm';
        $confirmCheckbox->mandatory = true;


        if ($formSubmit === \Input::post('FORM_SUBMIT')) {
            $confirmCheckbox->validate();

            if (!$confirmCheckbox->hasErrors()) {
                // Truncate attendances
                while (null !== $attendances && $attendances->next()) {
                    $attendances->delete();
                }

                // Truncate participants
                while ($participants->next()) {
                    Participant::getInstance()->getMetaModel()->delete($participants->getItem());
                }

                // Truncate members
                while (null !== $members && $members->next()) {
                    $members->delete();
                }

                \Message::addConfirmation('Löschung wurde erfolgreich ausgeführt');
            }
        }

        $buttons[] = sprintf(
            '<input type="submit" name="start" id="start" class="tl_submit" accesskey="s" value="%s" />',
            'Daten unwiderruflich löschen'
        );

        /** @noinspection PhpUndefinedMethodInspection */
        $output .= '<p>Dieses Tool steht Ihnen zur Verfügung, um alle personenbezogenen Daten der registrierten Eltern zu löschen.</p><h2>Gelöscht werden:</h2>';


        /** @noinspection PhpUndefinedMethodInspection */
        $output .= sprintf(
            <<<'HTML'
<table class="tl_show">
    <tbody>
    <tr>
        <th>Beschreibung</th>
        <th>Tabelle</th>
        <th>Anzahl zu löschender Datensätze</th>
    </tr>
    <tr>
        <td>Die Mitglieder, die sich <em>ausschließlich</em> in der Mitgleidergruppe "%7$s" befinden</td>
        <td>%1$s</td>
        <td>%4$s</td>
    </tr>
    <tr>
        <td>Deren Teilnehmer</td>
        <td>%2$s</td>
        <td>%5$s</td>
    </tr>
    <tr>
        <td>Alle Anmeldungen</td>
        <td>%3$s</td>
        <td>%6$s</td>
    </tr>
    </tbody>
</table>
HTML
            ,
            \MemberModel::getTable(),
            Participant::getInstance()->getMetaModel()->getTableName(),
            Attendance::getTable(),
            (null !== $members) ? $members->count() : 0,
            $participants->getCount(),
            (null !== $attendances) ? $attendances->count() : 0,
            $memberGroup->name
        );

        $output .= $confirmCheckbox->generateWithError();

        $this->Template->subHeadline = 'Personenbezogene Daten löschen';
        $this->Template->table = $formSubmit;
        $this->Template->editButtons = $buttons;
        $this->Template->fieldsets = [
            [
                'class' => 'tl_box',
                'palette' => $output,
            ],
        ];

    }
}
