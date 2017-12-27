<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\BackendModule;

use Contao\CheckBox;
use Contao\Database;
use Contao\Input;
use Contao\MemberGroupModel;
use Contao\MemberModel;
use Contao\Widget;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\ReloadEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Message\AddMessageEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Haste\Util\Format;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use MetaModels\IMetaModelsServiceContainer;


/**
 * Class EraseMemberData
 *
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 */
class EraseMemberData extends \BackendModule
{

    protected $strTemplate = 'dcbe_general_edit';


    /**
     * Generate the module
     *
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
        global $container;

        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer       = $container['metamodels-service-container'];
        $eventDispatcher        = $serviceContainer->getEventDispatcher();
        $output                 = '';
        $formSubmit             = 'erase_member_data';
        $memberGroup            = MemberGroupModel::findByPk('2'); // @todo
        $members                = MemberModel::findBy(['groups=?', 'persist<>1'], [serialize([$memberGroup->id])]);
        $attendances            = Attendance::findAll();
        $participantsMetaModel  = $serviceContainer->getFactory()->getMetaModel('mm_participant');
        $participantsFilter     = new Filter($participantsMetaModel);
        $participantsFilterRule = (null === $members)
            ? new StaticIdList([])
            : new SimpleQuery(
                sprintf(
                    'SELECT id FROM %1$s WHERE %2$s IN (%3$s) OR %2$s=0',
                    $participantsMetaModel->getTableName(),
                    'pmember',
                    implode(',', $members->fetchEach('id'))
                )
            );
        $participantsFilter->addFilterRule($participantsFilterRule);
        $participants = $participantsMetaModel->findByFilter($participantsFilter);

        /** @var CheckBox|Widget $checkboxConfirm */
        $checkboxConfirm            = new CheckBox(null);
        $checkboxConfirm->id        = $checkboxConfirm->name = 'confirm';
        $checkboxConfirm->mandatory = true;
        $checkboxConfirm->options   = [
            [
                'value' => 1,
                'label' => 'Ich bin mir bewusst, dass die Daten unwiderruflich gelöscht werden.',
            ],
        ];

        /** @var CheckBox|Widget $checkboxResetPersist */
        $checkboxResetPersist          = new CheckBox(null);
        $checkboxResetPersist->id      = $checkboxResetPersist->name = 'resetPersist';
        $checkboxResetPersist->options = [
            [
                'value' => 1,
                'label' => sprintf(
                    'Den Status "%s" bei diesen Mitglieder zurücksetzen',
                    Format::dcaLabel(MemberModel::getTable(), 'persist')
                ),
            ],
        ];

        /** @var CheckBox|Widget $checkboxPreserveAttendances */
        $checkboxPreserveAttendances          = new CheckBox(null);
        $checkboxPreserveAttendances->id      = $checkboxPreserveAttendances->name = 'preserveAttendances';
        $checkboxPreserveAttendances->options = [
            [
                'value' => 1,
                'label' => 'Anmeldungen nicht löschen',
            ],
        ];

        if ($formSubmit === Input::post('FORM_SUBMIT')) {
            $checkboxConfirm->validate();
            $checkboxResetPersist->validate();
            $checkboxPreserveAttendances->validate();

            if (!$checkboxConfirm->hasErrors()) {
                // Truncate attendances
                if ('1' !== $checkboxPreserveAttendances->value && null !== $attendances) {
                    Database::getInstance()->query(
                        sprintf(
                            'DELETE FROM %s WHERE id IN (%s)',
                            Attendance::getTable(),
                            implode(',', $attendances->fetchEach('id'))
                        )
                    );
                }

                // Truncate participants
                if (0 !== $participants->getCount()) {
                    Database::getInstance()->query(
                        sprintf(
                            'DELETE FROM %s WHERE id IN (%s)',
                            $participantsMetaModel->getTableName(),
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
                    Database::getInstance()->query(
                        sprintf(
                            'DELETE FROM %s WHERE id IN (%s)',
                            MemberModel::getTable(),
                            implode(',', $members->fetchEach('id'))
                        )
                    );
                }

                // Reset persist status
                if ('1' === $checkboxResetPersist->value) {
                    Database::getInstance()
                        ->prepare(
                            sprintf("UPDATE %s SET persist='' WHERE persist=1 AND groups=?", MemberModel::getTable())
                        )
                        ->execute(serialize([$memberGroup->id]));
                }

                $eventDispatcher->dispatch(
                    ContaoEvents::MESSAGE_ADD,
                    AddMessageEvent::createConfirm('Löschung wurde erfolgreich ausgeführt')
                );
                $eventDispatcher->dispatch(ContaoEvents::CONTROLLER_RELOAD, new ReloadEvent());
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
            MemberModel::getTable(),
            $participantsMetaModel->getTableName(),
            Attendance::getTable(),
            (null !== $members) ? $members->count() : 0,
            $participants->getCount(),
            (null !== $attendances) ? $attendances->count() : 0,
            $memberGroup->name
        );

        $output .= $checkboxPreserveAttendances->generateWithError();
        $output .= $checkboxResetPersist->generateWithError();
        $output .= $checkboxConfirm->generateWithError();

        $this->Template->subHeadline = 'Personenbezogene Daten löschen';
        $this->Template->table       = $formSubmit;
        $this->Template->editButtons = $buttons;
        $this->Template->fieldsets   = [
            [
                'class'   => 'tl_box',
                'palette' => $output,
            ],
        ];
    }
}
