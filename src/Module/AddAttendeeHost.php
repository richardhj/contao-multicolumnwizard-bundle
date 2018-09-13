<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IItem;
use MetaModels\Item;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\Form\Form;
use MetaModels\Attribute\IAttribute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class AddAttendeeHost
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class AddAttendeeHost extends AbstractFrontendModuleController
{

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * The participant model.
     *
     * @var Participant
     */
    private $participantModel;

    /**
     * The authenticated frontend user.
     *
     * @var FrontendUser
     */
    private $frontendUser;

    /**
     * The MetaModels filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    private $filterSettingFactory;

    /**
     * AddAttendeeHost constructor.
     *
     * @param Connection            $connection           The database connection.
     * @param TranslatorInterface   $translator           The translator.
     * @param Participant           $participantModel     The participant model.
     * @param IFilterSettingFactory $filterSettingFactory The MetaModels filter setting factory.
     */
    public function __construct(
        Connection $connection,
        TranslatorInterface $translator,
        Participant $participantModel,
        IFilterSettingFactory $filterSettingFactory
    ) {
        $this->connection           = $connection;
        $this->translator           = $translator;
        $this->participantModel     = $participantModel;
        $this->filterSettingFactory = $filterSettingFactory;
        $this->frontendUser         = FrontendUser::getInstance();
    }

    /**
     * Returns the response.
     *
     * @param Template|object $template The template.
     * @param ModuleModel     $model    The module model.
     * @param Request         $request  The request.
     *
     * @return Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $offer = $this->fetchOffer((int) $model->metamodel_filtering, \Input::get('auto_item'));
        if (null === $offer) {
            throw new PageNotFoundException('Item not found.');
        }

        if (!$offer->get('applicationlist_active')) {
            return Response::create('');
        }

        $hostData = $offer->get('host');
        $hostId   = $hostData[MetaModelSelect::SELECT_RAW]['id'];

        if ($this->frontendUser->ferienpass_host !== $hostId) {
            throw new AccessDeniedException('Access denied');
        }

        $form = new Form(
            'tl_add_attendee_host', 'POST', function (Form $haste) {
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

            $template->message = Message::generate();

            return Response::create($template->parse());
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
                $this->addParticipant($participantRow, $offer);
            }

            Message::addConfirmation(
                sprintf(
                    $this->translator->trans('MSC.addAttendeeHost.confirmation', [], 'contao_default'),
                    \count($participantsToAdd)
                )
            );
            throw new RedirectResponseException($request->getUri());
        }

        $template->message = Message::generate();
        $template->form    = $form->generate();

        return Response::create($template->parse());
    }

    /**
     * @param int    $filterId The filter ID to fetch the item by alias.
     * @param string $alias    The item alias.
     *
     * @return IItem
     */
    private function fetchOffer(int $filterId, string $alias): IItem
    {
        $filterCollection = $this->filterSettingFactory->createCollection($filterId);
        $metaModel        = $filterCollection->getMetaModel();
        $filter           = $metaModel->getEmptyFilter();

        $filterCollection->addRules($filter, ['auto_item' => $alias]);
        $items = $metaModel->findByFilter($filter);
        if (0 === $items->getCount()) {
            throw new PageNotFoundException('Offer not found (filter ID: ' . $filterId . ').');
        }

        if ($items->getCount() > 1) {
            throw new \RuntimeException('Offer ambiguous (filter ID: ' . $filterId . ').');
        }

        return $items->getItem();
    }

    /**
     * @param array $row   The data row.
     * @param IItem $offer The participant.
     */
    private function addParticipant(array $row, IItem $offer): void
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
            $this->createAttendance($participantId, $offer->get('id'));

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
            $memberAttribute = $offer->getAttribute('pmember');

            $participant->set($memberAttribute->getColName(), $memberAttribute->widgetToValue($memberId, $offer));
        }

        // current bug to have the combinedvalues get saved
        $participant->set('name', null);

        $participant->save();

        $this->createAttendance($participant->get('id'), $offer->get('id'));
    }

    /**
     * Create an attendance for this offer and given participant.
     *
     * @param int $participantId The participant id.
     * @param int $offerId       The offer ID.
     */
    private function createAttendance(int $participantId, int $offerId): void
    {
        $attendance = new Attendance();

        $attendance->tstamp      = time();
        $attendance->created     = time();
        $attendance->offer       = $offerId;
        $attendance->participant = $participantId;

        $attendance->save();
    }
}
