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
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\MetaModels\FrontendEditingItem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\Form\Form;
use MetaModels\Attribute\IAttribute;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


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

        $id = $statement->fetch(\PDO::FETCH_OBJ)->id;

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
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \RuntimeException
     */
    protected function compile()
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
            Message::addError($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);

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

        $form->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['submit']);

        if ($form->validate()) {
            /** @var array $participantsToAdd */
            $participantsToAdd = $form->fetch('attendees');

            // Create a new model for each participant
            /** @var array $participantRow */
            foreach ($participantsToAdd as $participantRow) {
                $participant = new FrontendEditingItem($this->participantModel->getMetaModel(), []);

                // Set each attribute in participant model
                foreach ($participantRow as $attributeName => $value) {
                    $participant->set($attributeName, $value);
                }

                $participant->save();

                // Create an attendance for this participant and offer
                $attendance              = new Attendance();
                $attendance->tstamp      = time();
                $attendance->created     = time();
                $attendance->offer       = $this->offer->get('id');
                $attendance->participant = $participant->get('id');
                $attendance->save();
            }

            Message::addConfirmation(
                sprintf($GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['confirmation'], \count($participantsToAdd))
            );
            Controller::reload();
        }

        $this->Template->message = Message::generate();
        $this->Template->form    = $form->generate();
    }
}
