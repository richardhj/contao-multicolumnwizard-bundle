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

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Message;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Event\UserSetApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\Form\Form;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class UserApplication
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserApplication extends AbstractFrontendModuleController
{

    /**
     * @var string
     */
    protected $strTemplate = 'mod_offer_applicationlist';

    /**
     * @var IItem|null
     */
    private $offer;

    /**
     * @var Participant
     */
    private $participantModel;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FrontendUser
     */
    private $frontendUser;

    /**
     * UserApplication constructor.
     *
     * @param Participant              $participantModel
     * @param EventDispatcherInterface $dispatcher
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        Participant $participantModel,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ) {
        $this->participantModel = $participantModel;
        $this->dispatcher       = $dispatcher;
        $this->translator       = $translator;
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
        $metaModel = $this->get('richardhj.ferienpass.model.offer');
        /** @var Connection $connection */
        $connection = $this->get('database_connection');
        $statement  = $connection->createQueryBuilder()
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

        // Stop if the procedure is not used
        if (!$this->offer->get('applicationlist_active')) {
            $template->info = $this->translator->trans('MSC.applicationList.inactive', [], 'contao_default');

            return Response::create($template->parse());
        }

        // Stop if the offer is in the past
        if (time() >= ToolboxOfferDate::offerStart($this->offer)) {
            $template->info = $this->translator->trans('MSC.applicationList.past', [], 'contao_default');

            return Response::create($template->parse());
        }

        $countParticipants = Attendance::countParticipants($this->offer->get('id'));
        $maxParticipants   = $this->offer->get('applicationlist_max');

        $availableParticipants = $maxParticipants - $countParticipants;

        if ($maxParticipants) {
            if ($availableParticipants < -10) {
                $template->booking_state_code = 4;
                $template->booking_state_text =
                    'Es sind keine Plätze mehr verfügbar<br>und die Warteliste ist ebenfalls voll.';
            } elseif ($availableParticipants < 1) {
                $template->booking_state_code = 3;
                $template->booking_state_text =
                    'Es sind keine freien Plätze mehr verfügbar,<br>aber Sie können sich auf die Warteliste eintragen.';
            } elseif ($availableParticipants < 4) {
                $template->booking_state_code = 2;
                $template->booking_state_text =
                    'Es sind nur noch wenige Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
            } else {
                $template->booking_state_code = 1;
                $template->booking_state_text =
                    'Es sind noch Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
            }
        } else {
            $template->booking_state_code = 0;
            $template->booking_state_text =
                'Das Angebot hat keine Teilnehmer-Beschränkung.<br>Sie können sich jetzt für das Angebot anmelden.';
        }


        if (FE_USER_LOGGED_IN && $this->frontendUser->id) {
            $participants = $this->participantModel->findByParent($this->frontendUser->id);

            if (0 === $participants->getCount()) {
                Message::addInfo($this->translator->trans('MSC.noParticipants', [], 'contao_default'));
            }

            // Build options
            $options = [];

            while ($participants->next()) {
                $options[] = [
                    'value' => $participants->getItem()->get('id'),
                    'label' => $participants->getItem()->parseAttribute('name')['text'],
                ];
            }

            $event = new BuildParticipantOptionsForUserApplicationEvent($participants, $this->offer, $options);
            $this->dispatcher->dispatch(BuildParticipantOptionsForUserApplicationEvent::NAME, $event);

            $options = $event->getResult();

            // Create form instance
            $form = new Form(
                'al' . $model->id, 'POST', function ($haste) {
                /** @noinspection PhpUndefinedMethodInspection */
                return $haste->getFormId() === \Input::post('FORM_SUBMIT');
            }
            );

            $form->addFormField(
                'participant',
                [
                    'label'     => $this->translator->trans(
                        'MSC.applicationList.participant.label',
                        [],
                        'contao_default'
                    ),
                    'inputType' => 'select_disabled_options',
                    'eval'      => [
                        'options'     => $options,
                        'multiple'    => true,
                        'mandatory'   => true,
                        'chosen'      => true,
                        'placeholder' => $this->translator->trans(
                            'MSC.applicationList.participant.placeholder',
                            [],
                            'contao_default'
                        )
                    ],
                ]
            );

            // Let's add  a submit button
            $form->addFormField(
                'submit',
                array(
                    'label'     => $this->translator->trans(
                        'MSC.applicationList.participant.slabel',
                        [],
                        'contao_default'
                    ),
                    'inputType' => 'submit'
                )
            );

            // Validate the form
            if ($form->validate()) {
                // Process new applications
                foreach ((array) $form->fetch('participant') as $participant) {
                    // Trigger event and let the application system set the attendance
                    $event = new UserSetApplicationEvent(
                        $this->offer,
                        $this->participantModel->findById($participant)
                    );
                    $this->dispatcher->dispatch(UserSetApplicationEvent::NAME, $event);
                }

                // Reload page to show confirmation message
                throw new RedirectResponseException($request->getUri());
            }

            // Get the form as string
            $template->form = $form->generate();
        }

        $template->message = Message::generate();

        return Response::create($template->parse());
    }
}
