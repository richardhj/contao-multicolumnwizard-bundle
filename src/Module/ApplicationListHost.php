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

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Frontend;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Helper\Table;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\Document;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\ItemList;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


/**
 * Class ApplicationListHost
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class ApplicationListHost extends Module
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'mod_offer_applicationlisthost';

    /**
     * @var IItem|null
     */
    private $offer;

    /**
     * @var Participant
     */
    private $participantModel;

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * @var Frontend
     */
    private $frontendUser;

    /**
     * ApplicationListHost constructor.
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
        $this->participantModel = System::getContainer()->get('richardhj.ferienpass.model.participant');
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
        /** @var Connection $connection */
        $connection = System::getContainer()->get('database_connection');
        $statement  = $connection->createQueryBuilder()
            ->select('id')
            ->from('mm_ferienpass')
            ->where('alias=:item')
            ->setParameter('item', Input::get('auto_item'))
            ->execute();

        $id = $statement->fetch(\PDO::FETCH_OBJ)->id;

        return $metaModel->findById($id);
    }

    /**
     * @return string
     * @throws AccessDeniedException
     * @throws PageNotFoundException
     */
    public function generate(): string
    {
        if ($this->scopeMatcher->currentScopeIsBackend()) {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]).' ###';
            $template->title    = $this->headline;
            $template->id       = $this->id;
            $template->link     = $this->name;
            $template->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $template->parse();
        }

        if (null === $this->offer) {
            throw new PageNotFoundException(
                'Item not found: '.ModelId::fromValues(
                    $this->offer->getMetaModel()->getTableName(),
                    $this->offer->get('id')
                )->getSerialized()
            );
        }

        $hostId = $this->offer->get('host')[MetaModelSelect::SELECT_RAW]['id'];

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
     */
    protected function compile()
    {
        if (!$this->offer->get('applicationlist_active')) {
            Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['inactive']);
            $this->Template->message = Message::generate();

            return;
        }

        $maxParticipants = $this->offer->get('applicationlist_max');
        $view            = $this->participantModel->getMetaModel()->getView($this->metamodel_child_list_view);
        /** @var array $fields */
        $fields           = $view->getSettingNames();
        $attendances      = Attendance::findByOffer($this->offer->get('id'));
        $statusConfirmed  = AttendanceStatus::findConfirmed()->id;
        $statusWaitlisted = AttendanceStatus::findWaitlisted()->id;
        $rows             = [];

        if (null !== $attendances) {
            // Create table head
            foreach ($fields as $field) {
                $rows[0][] = $this->participantModel->getAttribute($field)->get('name');
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
                    $value = $participant->parseAttribute($field, null, $view)['text'];

                    // Inherit parent's data
                    if ('' === $value) {
                        $value = $participant->get('pmember')[$field];
                    }

                    $values[] = $value;
                }

                $rows[] = $values;
            }
        }

        if (empty($rows)) {
            Message::addWarning($GLOBALS['TL_LANG']['MSC']['noAttendances']);
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

            $this->Template->dataTable = Table::getDataArray($rows, 'application-list', $this, $rowClassCallback);

            $urlBuilder = UrlBuilder::fromUrl(Environment::get('uri'));
            if ('download_list' === $urlBuilder->getQueryParameter('action')) {
                if (null === ($document = Document::findByPk($this->document))) {
                    Message::addError($GLOBALS['TL_LANG']['MSC']['document']['export_error']);
                } else {
                    $document->outputToBrowser($attendances);
                }
            }

            // Add download button
            $this->Template->download = $this->document ? sprintf(
                '<a href="%1$s" title="%3$s" class="download_list">%2$s</a>',
                $urlBuilder->setQueryParameter('action', 'download_list')->getUrl(),
                $GLOBALS['TL_LANG']['MSC']['downloadList'][0],
                $GLOBALS['TL_LANG']['MSC']['downloadList'][1]
            ) : '';
        }

        $this->addRenderedMetaModelToTemplate();
        $this->Template->message = Message::generate();
    }


    /**
     * Add the rendered meta model of this offer to the template
     */
    protected function addRenderedMetaModelToTemplate()
    {
        $itemRenderer = new ItemList();
        $itemRenderer
            ->setMetaModel($this->metamodel, $this->metamodel_rendersettings)
            ->addFilterRule(new StaticIdList([$this->offer->get('id')]));

        $this->Template->metamodel = $itemRenderer->render($this->metamodel_noparsing, $this);
    }
}
