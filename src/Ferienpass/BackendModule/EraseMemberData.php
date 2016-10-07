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
use Haste\Util\Format;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;


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
        $members = \MemberModel::findBy(['groups=?', 'persist<>1'], [serialize([$memberGroup->id])]);
        $attendances = Attendance::findAll();
        $getParticipantsFilter = function () use ($members) {
            $filter = new Filter(Participant::getInstance()->getMetaModel());
            if (null === $members) {
                $rule = new StaticIdList([]);
            } else {
                $rule = new SimpleQuery(
                    sprintf(
                        'SELECT id FROM %1$s WHERE %2$s IN (%3$s) OR %2$s=0',
                        Participant::getInstance()->getMetaModel()->getTableName(),
                        Participant::getInstance()->getOwnerAttribute()->getColName(),
                        implode(',', $members->fetchEach('id'))
                    )
                );
            }

            $filter->addFilterRule($rule);

            return $filter;
        };
        $participants = Participant::getInstance()->getMetaModel()->findByFilter($getParticipantsFilter());

        /** @var \CheckBox|\Widget $checkboxConfirm */
        $checkboxConfirm = new \CheckBox(null);
        $checkboxConfirm->options = [
            [
                'value' => 1,
                'label' => 'Ich bin mir bewusst, dass die Daten unwiderruflich gelöscht werden.',
            ],
        ];
        $checkboxConfirm->id = $checkboxConfirm->name = 'confirm';
        $checkboxConfirm->mandatory = true;

        /** @var \CheckBox|\Widget $checkboxResetPersist */
        $checkboxResetPersist = new \CheckBox(null);
        /** @noinspection PhpUndefinedMethodInspection */
        $checkboxResetPersist->options = [
            [
                'value' => 1,
                'label' => sprintf(
                    'Den Status "%s" bei diesen Mitglieder zurücksetzen',
                    Format::dcaLabel(\MemberModel::getTable(), 'persist')
                ),
            ],
        ];
        $checkboxResetPersist->id = $checkboxResetPersist->name = 'resetPersist';

        if ($formSubmit === \Input::post('FORM_SUBMIT')) {
            $checkboxConfirm->validate();
            $checkboxResetPersist->validate();

            if (!$checkboxConfirm->hasErrors()) {
                if (null !== $attendances) {
                    // Truncate attendances
                    \Database::getInstance()->query(
                        sprintf(
                            'DELETE FROM %s WHERE id IN (%s)',
                            Attendance::getTable(),
                            implode(',', $attendances->fetchEach('id'))
                        )
                    );
                }

                if (0 !== $participants->getCount()) {
                    // Truncate participants
                    \Database::getInstance()->query(
                        sprintf(
                            'DELETE FROM %s WHERE id IN (%s)',
                            Participant::getInstance()->getMetaModel()->getTableName(),
                            implode(
                                ',',
                                array_map(
                                    function (IItem $item) {
                                        return $item->get('id');
                                    },
                                    iterator_to_array($participants)
                                )
                            )
                        )
                    );
                }

                // Truncate members
                if (null !== $members) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    \Database::getInstance()->query(
                        sprintf(
                            'DELETE FROM %s WHERE id IN (%s)',
                            \MemberModel::getTable(),
                            implode(',', $members->fetchEach('id'))
                        )
                    );
                }

                // Reset persist status
                if ('1' === $checkboxResetPersist) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    \Database::getInstance()
                        ->prepare(
                            sprintf("UPDATE %s SET persist='' WHERE persist=1 AND groups=?", \MemberModel::getTable())
                        )
                        ->execute(serialize([$memberGroup->id]));
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

        $output .= $checkboxResetPersist->generateWithError();
        $output .= $checkboxConfirm->generateWithError();

        $this->Template->subHeadline = 'Personenbezogene Daten löschen';
        $this->Template->table = $formSubmit;
        $this->Template->editButtons = $buttons;
        $this->Template->fieldsets = [
            [
                'class'   => 'tl_box',
                'palette' => $output,
            ],
        ];
    }
}
