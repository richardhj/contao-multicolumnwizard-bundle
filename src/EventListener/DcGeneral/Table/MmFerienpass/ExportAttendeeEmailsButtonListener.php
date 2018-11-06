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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;


use Contao\StringUtil;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetSelectModeButtonsEvent;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ExportAttendeeEmailsButtonListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass
 */
class ExportAttendeeEmailsButtonListener
{

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ExportAttendeeEmailsButtonListener constructor.
     *
     * @param TranslatorInterface $translator The translator.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Remove the select button when in list view and override/edit all is active.
     *
     * @param GetSelectModeButtonsEvent $event The event.
     *
     * @return void
     */
    public function handle(GetSelectModeButtonsEvent $event): void
    {
        if (('mm_ferienpass' !== $event->getEnvironment()->getDataDefinition()->getName())
            || ('select' !== $event->getEnvironment()->getInputProvider()->getParameter('act'))
        ) {
            return;
        }

        $buttons = $event->getButtons();


        $confirmMessage = htmlentities(
            \sprintf(
                '<h2 class="tl_error">%s</h2>' .
                '<p></p>' .
                '<div class="tl_submit_container">' .
                '<input type="submit" name="close" class="%s" value="%s" onclick="%s">' .
                '</div>',
                StringUtil::specialchars(
                    $this->translator->trans(
                        'MSC.nothingSelect',
                        [],
                        'contao_' . $event->getEnvironment()->getDataDefinition()->getName()
                    )
                ),
                'tl_submit',
                StringUtil::specialchars(
                    $this->translator->trans(
                        'MSC.close',
                        [],
                        'contao_' . $event->getEnvironment()->getDataDefinition()->getName()
                    )
                ),
                'this.blur(); BackendGeneral.hideMessage(); return false;'
            ),
            ENT_QUOTES | ENT_HTML5
        );

        $onClick = 'BackendGeneral.confirmSelectOverrideEditAll(this, \'models[]\', \'' . $confirmMessage
                   . '\'); return false;';

        $buttons['export_attendee_emails'] =
            '<input type="submit" name="export_attendee_emails" class="tl_submit" value="E-Mail-Adressen von Teilnehmern exportieren" onclick="'
            . $onClick . '">';

        $event->setButtons($buttons);
    }
}
