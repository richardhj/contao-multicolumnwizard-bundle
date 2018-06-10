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

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\IItem;
use MetaModels\Item;
use Patchwork\Utf8;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\Form\Form;
use MetaModels\Attribute\IAttribute;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class AddAttendeeHost
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class AddAttendeeHost extends Module
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'mod_offer_addattendeehost';

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Participant
     */
    private $participantModel;

    /**
     * @var IItem|null
     */
    private $offer;

    /**
     * @var FrontendUser
     */
    private $frontendUser;

    /**
     * AddAttendeeHost constructor.
     *
     * @param \ModuleModel $module
     * @param string       $column
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(\ModuleModel $module, $column = 'main')
    {
        parent::__construct($module, $column);

        $this->scopeMatcher     = System::getContainer()->get('cca.dc-general.scope-matcher');
        $this->connection       = System::getContainer()->get('database_connection');
        $this->participantModel = System::getContainer()->get('richardhj.ferienpass.model.participant');
        $this->translator       = System::getContainer()->get('translator');
        $this->offer            = $this->fetchOffer();
        $this->frontendUser     = FrontendUser::getInstance();
    }

    /**
     * @return IItem|null
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    private function fetchOffer(): ?IItem
    {
        /** @var Offer $metaModel */
        $metaModel = System::getContainer()->get('richardhj.ferienpass.model.offer');
        $statement = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('mm_ferienpass')
            ->where('alias=:item')
            ->setParameter('item', Input::get('auto_item'))
            ->execute();

        $id = $statement->fetchColumn();
        if (false === $id) {
            return null;
        }

        return $metaModel->findById($id);
    }

    /**
     * @return string
     *
     * @throws AccessDeniedException
     * @throws PageNotFoundException
     */
    public function generate(): string
    {
        if ($this->scopeMatcher->currentScopeIsBackend()) {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]) . ' ###';
            $template->title    = $this->headline;
            $template->id       = $this->id;
            $template->link     = $this->name;
            $template->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $template->parse();
        }

        if (null === $this->offer) {
            throw new PageNotFoundException(
                'Item not found: ' . ModelId::fromValues(
                    $this->offer->getMetaModel()->getTableName(),
                    $this->offer->get('id')
                )->getSerialized()
            );
        }

        $hostData = $this->offer->get('host');
        $hostId   = $hostData[MetaModelSelect::SELECT_RAW]['id'];

        if ($this->frontendUser->ferienpass_host !== $hostId) {
            throw new AccessDeniedException('Access denied');
        }

        if ('' !== $this->customTpl) {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }

    /**
     * Generate the module
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \RuntimeException
     */
    protected function compile(): void
    {
        $form = new Form(
            'tl_add_attendee_host', 'POST', function ($haste) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $haste->getFormId() === Input::post('FORM_SUBMIT');
        }
        );

        /*
         * Fetch participant model attributes
         */
        $columnFieldsDca = [];
        $memberGroups    = deserialize($this->frontendUser->groups);

        $dcaCombine = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_metamodel_dca_combine')
            ->where('fe_group IN (:fegroups)')
            ->andWhere('pid=:pid')
            ->setParameter('fegroups', $memberGroups, Connection::PARAM_INT_ARRAY)
            ->setParameter('pid', $this->participantModel->getMetaModel()->get('id'))
            ->execute();

        // Throw exception if no dca combine setting is set
        if (0 === $dcaCombine->rowCount()) {
            throw new \RuntimeException(
                sprintf(
                    'No dca combine setting found for MetaModel ID %u and member groups %s found',
                    $this->participantModel->getMetaModel()->get('id'),
                    var_export($memberGroups, true)
                )
            );
        }
        $dcaCombine = $dcaCombine->fetch(\PDO::FETCH_OBJ);

        // Get the dca settings
        $dca = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_metamodel_dca')
            ->where('id=:id')
            ->setParameter('id', $dcaCombine->dca_id)
            ->execute()
            ->fetch(\PDO::FETCH_OBJ);

        $dcaSetting = $this->connection->createQueryBuilder()
            ->select('a.colname', 's.*')
            ->from('tl_metamodel_attribute', 'a')
            ->innerJoin('a', 'tl_metamodel_dcasetting', 's', 'a.id=s.attr_id')
            ->where('s.pid=:dca_id')
            ->setParameter('dca_id', $dca->id)
            ->execute();

        // Fetch all dca settings as associative array
        $dcaSettings = array_reduce(
            $dcaSetting->fetchAll(\PDO::FETCH_ASSOC),
            function ($result, $item) {
                $result[$item['colname']] = $item;

                return $result;
            },
            []
        );

        // Exit if a new item creation is not allowed
        if (!$dca->iscreatable) {
            Message::addError($this->translator->trans('MSC.tableClosedInfo', [], 'contao_default'));

            $this->Template->message = Message::generate();

            return;
        }

        // Add all published attributes and override the dca settings in the field definition
        /**
         * @var string     $attributeName
         * @var IAttribute $attribute
         */
        foreach ($this->participantModel->getMetaModel()->getAttributes() as $attributeName => $attribute) {
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

        $form->addSubmitFormField(
            'submit',
            $this->translator->trans('MSC.addAttendeeHost.submit', [], 'contao_default')
        );

        if ($form->validate()) {
            /** @var array $participantsToAdd */
            $participantsToAdd = $form->fetch('attendees');

            // Create a new model for each participant
            /** @var array $participantRow */
            foreach ($participantsToAdd as $participantRow) {
                $this->addParticipant($participantRow);
            }

            Message::addConfirmation(
                sprintf(
                    $this->translator->trans('MSC.addAttendeeHost.confirmation', [], 'contao_default'),
                    \count($participantsToAdd)
                )
            );
            Controller::reload();
        }

        $this->Template->message = Message::generate();
        $this->Template->form    = $form->generate();
    }

    private function addParticipant(array $row): void
    {
        $expr = $this->connection->getExpressionBuilder();

        // Try to find an existing participant
        $statement = $this->connection->createQueryBuilder()
            ->select('p.id')
            ->from('mm_participant', 'p')
            ->leftJoin('p', 'tl_member', 'm', 'p.pmember=m.id')
            ->where(
                $expr->orX(
                    $expr->andX('p.phone<>\'\'', 'p.phone=:phone'),
                    $expr->andX('m.phone<>\'\'', 'm.phone=:phone'),
                    $expr->andX('p.email<>\'\'', 'p.email=:email'),
                    $expr->andX('m.email<>\'\'', 'm.email=:email')
                )
            )
            ->andWhere($expr->andX('p.firstname=:firstname', 'p.lastname=:lastname'))
            ->setParameter('phone', $row['phone'])
            ->setParameter('email', $row['email'])
            ->setParameter('firstname', $row['firstname'])
            ->setParameter('lastname', $row['lastanme'])
            ->execute();

        if (false !== $participantId = $statement->fetchColumn()) {
            $this->createAttendance($participantId);

            return;
        }

        $participant = new Item($this->participantModel->getMetaModel(), $row);

        // Try to find an existing member for this participant
        $statement = $this->connection->createQueryBuilder()
            ->select('m.id')
            ->from('tl_member', 'm')
            ->where(
                $expr->orX(
                    $expr->andX('m.phone<>\'\'', 'm.phone=:phone'),
                    $expr->andX('m.email<>\'\'', 'm.email=:email')
                )
            )
            ->setParameter('phone', $row['phone'])
            ->setParameter('email', $row['email'])
            ->execute();

        if (false !== $memberId = $statement->fetchColumn()) {
            $participant->set('pmember', $memberId);
        }

        // current bug to have the combinedvalues get saved
        $participant->set('name', null);

        $participant->save();

        $this->createAttendance($participant->get('id'));
    }

    /**
     * Create an attendance for this offer and given participant.
     *
     * @param int $participantId
     */
    private function createAttendance(int $participantId): void
    {
        $attendance = new Attendance();

        $attendance->tstamp      = time();
        $attendance->created     = time();
        $attendance->offer       = $this->offer->get('id');
        $attendance->participant = $participantId;
        $attendance->save();
    }
}
