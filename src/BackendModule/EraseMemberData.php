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

namespace Richardhj\ContaoFerienpassBundle\BackendModule;

use Contao\BackendModule;
use Contao\BackendUser;
use Contao\CheckBox;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\MemberGroupModel;
use Contao\MemberModel;
use Contao\System;
use Contao\Widget;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\ReloadEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Message\AddMessageEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ContaoBackendViewTemplate;
use Doctrine\DBAL\Connection;
use MetaModels\IFactory;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class EraseMemberData
 *
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 */
class EraseMemberData extends BackendModule
{

    protected $strTemplate = 'dcbe_general_edit';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var IFactory
     */
    private $factory;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * EraseMemberData constructor.
     *
     * @param DataContainer|null $dataContainer
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(DataContainer $dataContainer = null)
    {
        parent::__construct($dataContainer);

        $this->dispatcher = System::getContainer()->get('event_dispatcher');
        $this->factory    = System::getContainer()->get('metamodels.factory');
        $this->connection = System::getContainer()->get('database_connection');
        $this->translator = System::getContainer()->get('contao.translation.translator');
    }

    /**
     * Generate the module
     *
     * @return string
     */
    public function generate(): string
    {
        if (!BackendUser::getInstance()->isAdmin) {
            return sprintf('<p class="tl_gerror">%s</p>', 'keine Berechtigung');
        }

        return parent::generate();
    }


    /**
     * Generate the module
     *
     * @throws InvalidArgumentException
     */
    protected function compile()
    {
        $output                 = '';
        $formSubmit             = 'erase_member_data';
        $memberGroup            = MemberGroupModel::findByPk('2'); // @todo
        $members                = MemberModel::findBy(['groups=?', 'persist<>1'], [serialize([$memberGroup->id])]);
        $attendances            = Attendance::findAll();
        $participantsMetaModel  = $this->factory->getMetaModel('mm_participant');
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
                    $this->translator->trans('persist', [], 'tl_member')
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
                    $ids = $attendances->fetchEach('id');
                    $this->connection->createQueryBuilder()
                        ->delete(Attendance::getTable())
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                        ->execute();
                }

                // Truncate participants
                if (0 !== $participants->getCount()) {
                    $ids = array_map(
                        function (IItem $item) {
                            return $item->get('id');
                        },
                        iterator_to_array($participants)
                    );

                    $this->connection->createQueryBuilder()
                        ->delete($participantsMetaModel->getTableName())
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                        ->execute();
                }

                // Truncate members
                if (null !== $members) {
                    $ids = $members->fetchEach('id');
                    $this->connection->createQueryBuilder()
                        ->delete('tl_member')
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                        ->execute();
                }

                // Reset persist status
                if ('1' === $checkboxResetPersist->value) {
                    $this->connection->createQueryBuilder()
                        ->update('tl_member')
                        ->set('persist', '')
                        ->where('persist=1')
                        ->andWhere('groups=:groups')
                        ->setParameter('groups', serialize([$memberGroup->id]))
                        ->execute();
                }

                $this->dispatcher->dispatch(
                    ContaoEvents::MESSAGE_ADD,
                    AddMessageEvent::createConfirm('Löschung wurde erfolgreich ausgeführt')
                );
                $this->dispatcher->dispatch(ContaoEvents::CONTROLLER_RELOAD, new ReloadEvent());
            }
        }


        $buttonTemplate = new ContaoBackendViewTemplate('dc_general_button');
        $buttonTemplate->setData(
            [
                'label'      => 'Daten unwiderruflich löschen',
                'attributes' => [
                    'type'      => 'submit',
                    'name'      => 'start',
                    'id'        => 'start',
                    'class'     => 'tl_submit',
                    'accesskey' => 's',
                ],
            ]
        );
        $buttons['save'] = $buttonTemplate->parse();

        $submitButtons = ['toggleIcon' => Image::getHtml('navcol.svg')];
        $editButtons   = $buttons;
        if (array_key_exists('save', $editButtons)) {
            $submitButtons['save'] = $editButtons['save'];
            unset($editButtons['save']);
        }

        if (0 < \count($editButtons)) {
            $submitButtons['buttonGroup'] = $editButtons;
        }

        $submitButtonTemplate = new ContaoBackendViewTemplate('dc_general_submit_button');
        $submitButtonTemplate->setData($submitButtons);

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
        $this->Template->editButtons = preg_replace('/(\s\s+|\t|\n)/', '', $submitButtonTemplate->parse());
        $this->Template->fieldsets   = [
            [
                'class'   => 'tl_box',
                'palette' => $output,
            ],
        ];
    }
}
