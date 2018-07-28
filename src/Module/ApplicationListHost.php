<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Template;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Doctrine\DBAL\Connection;
use Haste\DateTime\DateTime;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\IItem;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Richardhj\ContaoFerienpassBundle\ApplicationList\Document;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Helper\Table;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\ItemList;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class ApplicationListHost
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class ApplicationListHost extends AbstractFrontendModuleController
{

    /**
     * @var IItem|null
     */
    private $offer;

    /**
     * @var Participant
     */
    private $participantModel;

    /**
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Offer
     */
    private $offerModel;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var FrontendUser
     */
    private $frontendUser;

    /**
     * ApplicationListHost constructor.
     *
     * @param Participant           $participantModel
     * @param IRenderSettingFactory $renderSettingFactory
     * @param TranslatorInterface   $translator
     * @param Offer                 $offerModel
     * @param Connection            $connection
     */
    public function __construct(
        Participant $participantModel,
        IRenderSettingFactory $renderSettingFactory,
        TranslatorInterface $translator,
        Offer $offerModel,
        Connection $connection
    ) {
        $this->participantModel     = $participantModel;
        $this->renderSettingFactory = $renderSettingFactory;
        $this->translator           = $translator;
        $this->offerModel           = $offerModel;
        $this->connection           = $connection;
        $this->offer                = $this->fetchOffer();
        $this->frontendUser         = FrontendUser::getInstance();
    }

    /**
     * Returns the response.
     *
     * @param Template|object $template
     * @param ModuleModel     $model
     * @param Request         $request
     *
     * @return Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        if (null === $this->offer) {
            throw new PageNotFoundException('Item not found.');
        }

        $hostData = $this->offer->get('host');
        $hostId   = $hostData[MetaModelSelect::SELECT_RAW]['id'];

        if ($this->frontendUser->ferienpass_host !== $hostId) {
            throw new AccessDeniedException('Access denied');
        }

        if (!$this->offer->get('applicationlist_active')) {
            Message::addError($this->translator->trans('MSC.applicationList.inactive', [], 'contao_default'));
            $template->message = Message::generate();

            return Response::create($template->parse());
        }

        $maxParticipants = $this->offer->get('applicationlist_max');

        $view = $this->renderSettingFactory->createCollection(
            $this->participantModel->getMetaModel(),
            $model->metamodel_child_list_view
        );

        $fields           = $view->getSettingNames();
        $attendances      = Attendance::findByOffer($this->offer->get('id'));
        $statusConfirmed  = AttendanceStatus::findConfirmed()->id;
        $statusWaitlisted = AttendanceStatus::findWaitlisted()->id;
        $rows             = [];

        if (null !== $attendances) {
            // Create table head
            foreach ($fields as $field) {
                if ('dateOfBirth' === $field) {
                    $rows[0][] = 'Alter';
                } else {
                    $rows[0][] = $this->participantModel->getMetaModel()->getAttribute($field)->get('name');
                }
            }

            // Walk each attendee
            while ($attendances->next()) {
                $values      = [];
                $participant = $this->participantModel->findById($attendances->participant);

                if (!\in_array($attendances->status, [$statusConfirmed, $statusWaitlisted], false)) {
                    continue;
                }
                if (null === $participant) {
                    $attendances->current()->delete(); # this will sync the entire list

                    continue;
                }

                foreach ($fields as $field) {
                    $parentMember = $participant->get('pmember');

                    if ($field === 'dateOfBirth') {
                        $date  = new DateTime('@' . $participant->get($field));
                        $value = $date->getAge();
                    } else {
                        $value = $participant->parseAttribute($field, 'text', $view)['text'];
                    }

                    // Inherit parent's data
                    if ('' === $value) {
                        $value = $parentMember[$field];
                    }

                    if ($field === 'phone' && '' !== $parentMember['mobile']) {
                        $value .= ' / ' . $parentMember['mobile'];

                        $value = ltrim($value, '/ ');
                    }

                    $values[] = $value;
                }

                $rows[] = $values;
            }
        }

        if (empty($rows)) {
            Message::addWarning($this->translator->trans('MSC.noAttendances', [], 'contao_default'));
        } else {
            $this->useHeader        = true;
            $this->max_participants = $maxParticipants;

            // Define row class callback
            $rowClassCallback = function ($j, $rows, $module) {
                if ($j === ($module->max_participants - 1) && $j !== \count($rows) - 1) {
                    return 'last_attendee';
                }

                if ($j >= $module->max_participants) {
                    return 'waiting_list';
                }

                return '';
            };

            $template->dataTable = Table::getDataArray($rows, 'application-list', $model, $rowClassCallback);

            $urlBuilder = UrlBuilder::fromUrl($request->getUri());
            if ('download_list' === $urlBuilder->getQueryParameter('action')) {
                $document = new Document($this->offer);

                $document->outputToBrowser();
            }

            // Add download button
            $template->download = sprintf(
                '<a href="%1$s" title="%3$s" class="download_list">%2$s</a>',
                $urlBuilder->setQueryParameter('action', 'download_list')->getUrl(),
                $this->translator->trans('MSC.downloadList.0', [], 'contao_default'),
                $this->translator->trans('MSC.downloadList.1', [], 'contao_default')
            );
        }

        $this->addRenderedMetaModelToTemplate($template, $model);
        $template->message = Message::generate();

        return Response::create($template->parse());
    }

    /**
     * @return IItem|null
     */
    private function fetchOffer(): ?IItem
    {
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

        return $this->offerModel->findById($id);
    }

    /**
     * Add the rendered meta model of this offer to the template
     *
     * @param Template    $template
     * @param ModuleModel $model
     */
    protected function addRenderedMetaModelToTemplate(Template $template, ModuleModel $model): void
    {
        $itemRenderer = new ItemList();
        $itemRenderer
            ->setMetaModel($model->metamodel, $model->metamodel_rendersettings)
            ->addFilterRule(new StaticIdList([$this->offer->get('id')]));

        $template->metamodel = $itemRenderer->render($template->metamodel_noparsing, $this);
    }
}
