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
use Contao\Message;
use Contao\ModuleModel;
use Contao\Template;
use MetaModels\Filter\Setting\FilterSettingFactory;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Event\UserSetApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Offer as OfferModel;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\Form\Form;
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
     * @var IFilterSettingFactory
     */
    private $filterSettingFactory;

    /**
     * @var OfferModel
     */
    private $offerModel;

    /**
     * UserApplication constructor.
     *
     * @param Participant              $participantModel
     * @param EventDispatcherInterface $dispatcher
     * @param TranslatorInterface      $translator
     * @param FilterSettingFactory     $filterSettingFactory
     * @param OfferModel               $offerModel
     */
    public function __construct(
        Participant $participantModel,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator,
        FilterSettingFactory $filterSettingFactory,
        OfferModel $offerModel
    ) {
        $this->participantModel     = $participantModel;
        $this->dispatcher           = $dispatcher;
        $this->translator           = $translator;
        $this->filterSettingFactory = $filterSettingFactory;
        $this->frontendUser         = FrontendUser::getInstance();
        $this->offerModel           = $offerModel;
    }

    /**
     * @param int    $filterId
     * @param string $alias
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
        $offer = $this->fetchOffer((int) $model->metamodel_filtering, \Input::get('auto_item'));

        // Stop if the procedure is not used
        if (!$offer->get('applicationlist_active')) {
            $template->info = $this->translator->trans('MSC.user_application.inactive', [], 'contao_default');

            //$this->tagResponse(['ferienpass.offer.' . $offer->get('id')]);

            return Response::create($template->parse());
        }

        // Stop if the offer is in the past
        if (time() >= ToolboxOfferDate::offerStart($offer)) {
            $template->info = $this->translator->trans('MSC.user_application.past', [], 'contao_default');

            //$this->tagResponse(['ferienpass.offer.' . $offer->get('id')]);

            return Response::create($template->parse());
        }

        $applicationSystem  = $this->offerModel->getApplicationSystem($offer);
        $countParticipants  = Attendance::countParticipants($offer->get('id'));
        $maxParticipants    = $offer->get('applicationlist_max');
        $actualVacantPlaces = $maxParticipants - $countParticipants;
        $vacantPlaces       = max(0, $actualVacantPlaces);
        $utilization        = ($maxParticipants > 0) ? $countParticipants / $maxParticipants : 0;
        $variantBase        = $offer->getVariantBase();
        $variants           = $variantBase->getVariants(null);

        $template->showVacantPlaces             = $applicationSystem instanceof FirstCome && $maxParticipants > 0;
        $template->showUtilization              = $applicationSystem instanceof Lot && $utilization >= 0.8;
        $template->showBookingState             = $applicationSystem instanceof FirstCome;
        $template->maxParticipants              = $maxParticipants;
        $template->utilization                  = $utilization;
        $template->vacantPlaces                 = $vacantPlaces;
        $template->showVariantListLink          = $variants ? $variants->getCount() > 1 : false;
        $template->variantListHref              = $request->getBaseUrl() . '/' . $offer->get('id');
        $template->variantListLink              =
            $this->translator->trans('MSC.user_application.variants_list_link', [], 'contao_default');
        $template->utilizationText              = $this->translator->trans(
            'MSC.user_application.high_utilization_text',
            ['utilization' => round($utilization * 100)],
            'contao_default'
        );
        $template->vacantPlacesLabel            = $this->translator->trans(
            'MSC.user_application.vacant_places_label',
            ['places' => $vacantPlaces],
            'contao_default'
        );
        $template->currentApplicationSystemText = (FE_USER_LOGGED_IN && $this->frontendUser->id)
            ? $this->translator->trans(
                'MSC.user_application.current_application_system.'
                . ((null !== $applicationSystem) ? $applicationSystem->getModel()->type : 'none'),
                [],
                'contao_default'
            )
            : '';

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

        if (FE_USER_LOGGED_IN && $this->frontendUser->id && null !== $applicationSystem) {
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

            $event = new BuildParticipantOptionsForUserApplicationEvent($participants, $offer, $options);
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
                        $offer,
                        $this->participantModel->findById($participant)
                    );
                    $this->dispatcher->dispatch(UserSetApplicationEvent::NAME, $event);
                }

                // Reload page to show confirmation message
                throw new RedirectResponseException($request->getUri());
            }

            // Get the form as string
            $template->form = $form->generate();

            $participantCacheTags = array_map(
                function (IItem $participant) {
                    return 'ferienpass.participant.' . $participant->get('id');
                },
                iterator_to_array($participants)
            );
            $this->tagResponse($participantCacheTags);
        }

        $template->message = Message::generate();

        $maxAge = 0;
        if (null !== $applicationSystem) {
            $this->tagResponse(
                [
                    //'ferienpass.offer.' . $offer->get('id'),
                    'ferienpass.application_system.' . $applicationSystem->getModel()->type
                ]
            );

            $passEditionTask = $applicationSystem->getPassEditionTask();
            $maxAge          = null !== $passEditionTask ? $passEditionTask->getPeriodStop() - time() : null;
        } else {
            // maxAge is next application system starts.
        }

        $response = Response::create($template->parse());
        if ($maxAge) {
            $response->setSharedMaxAge($maxAge);
        }

        return $response;
    }
}
