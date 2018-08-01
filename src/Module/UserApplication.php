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
use MetaModels\IFactory;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;
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
     * @var Offer
     */
    private $offerModel;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    /**
     * UserApplication constructor.
     *
     * @param Participant                $participantModel
     * @param EventDispatcherInterface   $dispatcher
     * @param TranslatorInterface        $translator
     * @param Offer                      $offerModel
     * @param Connection                 $connection
     * @param ApplicationSystemInterface $applicationSystem
     */
    public function __construct(
        Participant $participantModel,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator,
        Offer $offerModel,
        Connection $connection,
        ApplicationSystemInterface $applicationSystem
    ) {
        $this->participantModel  = $participantModel;
        $this->dispatcher        = $dispatcher;
        $this->translator        = $translator;
        $this->offerModel        = $offerModel;
        $this->connection        = $connection;
        $this->offer             = $this->fetchOffer();
        $this->frontendUser      = FrontendUser::getInstance();
        $this->applicationSystem = $applicationSystem;
    }

    /**
     * @return IItem|null
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    private function fetchOffer(): ?IItem
    {
        // Todo use reader filter
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
            $template->info = $this->translator->trans('MSC.user_application.inactive', [], 'contao_default');

            return Response::create($template->parse());
        }

        // Stop if the offer is in the past
        if (time() >= ToolboxOfferDate::offerStart($this->offer)) {
            $template->info = $this->translator->trans('MSC.user_application.past', [], 'contao_default');

            return Response::create($template->parse());
        }

        $countParticipants  = Attendance::countParticipants($this->offer->get('id'));
        $maxParticipants    = $this->offer->get('applicationlist_max');
        $actualVacantPlaces = $maxParticipants - $countParticipants;
        $vacantPlaces       = max(0, $actualVacantPlaces);
        $utilization        = ($maxParticipants > 0) ? $countParticipants / $maxParticipants : 0;

        $template->showVacantPlaces             = $this->applicationSystem instanceof FirstCome && $maxParticipants > 0;
        $template->showUtilization              = $this->applicationSystem instanceof Lot;
        $template->showBookingState             = $this->applicationSystem instanceof FirstCome;
        $template->maxParticipants              = $maxParticipants;
        $template->utilization                  = $utilization;
        $template->vacantPlaces                 = $vacantPlaces;
        $template->variantListHref              = $request->getBaseUrl() . '/' . $this->offer->get('id');
        $template->variantListLink              = $this->translator->trans('MSC.user_application.variants_list_link', [], 'contao_default');
        $template->utilizationText              = $this->translator->trans('MSC.user_application.utilization_text', ['utilization' => round($utilization * 100)], 'contao_default');
        $template->vacantPlacesLabel            = $this->translator->trans('MSC.user_application.vacant_places_label', ['places' => $vacantPlaces], 'contao_default');
        $template->currentApplicationSystemText = $this->translator->trans('MSC.user_application.current_application_system.' . $this->applicationSystem->getModel()->type, [], 'contao_default');

        if ($maxParticipants) {
            switch (true) {
                case $actualVacantPlaces < -10:
                    $template->booking_state_code = 4;
                    break;

                case $actualVacantPlaces < 1:
                    $template->booking_state_code = 3;
                    break;

                case $actualVacantPlaces < 4:
                    $template->booking_state_code = 2;
                    break;

                default:
                    $template->booking_state_code = 1;
                    break;
            }
        } else {
            $template->booking_state_code = 0;
        }

        $template->booking_state_text = $this->translator->trans(
            'MSC.user_application.booking_state.' . $template->booking_state_code,
            [],
            'contao_default'
        );

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
                        'MSC.user_application.participant.label',
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
                            'MSC.user_application.participant.placeholder',
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
                        'MSC.user_application.participant.slabel',
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
