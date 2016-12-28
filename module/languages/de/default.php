<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/**
 * Miscellaneous
 */
// User attendances and application list
$GLOBALS['TL_LANG']['MSC']['noAttendances'] = 'Es liegen keine Anmeldungen vor.';
$GLOBALS['TL_LANG']['MSC']['noParticipants'] = 'Sie müssen vorerst Teilnehmer anlegen. {{link_open::26}}Klicken Sie hier.{{link_close}}';
$GLOBALS['TL_LANG']['MSC']['applicationList']['active'] = 'Dieses Angebot verwendet das Online-Anmeldeverfahren.';
$GLOBALS['TL_LANG']['MSC']['applicationList']['inactive'] = 'Dieses Angebot verwendet <strong>nicht</strong> das Online-Anmeldeverfahren.';
$GLOBALS['TL_LANG']['MSC']['applicationList']['past'] = 'Dieses Angebot liegt in der Vergangenheit.';
$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['label'] = 'Teilnehmer auswählen';
$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['ok'] = '%s';
$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['already_attending'] = '%s (bereits angemeldet)';
$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['age_not_allowed'] = '%s (ungeignet für das Alter)';
$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['limit_reached'] = '%s (Pro-Tag-Limit erreicht)';
$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['slabel'] = 'Anmelden';
$GLOBALS['TL_LANG']['MSC']['applicationList']['message']['confirmed'] = '%s ist angemeldet für dieses Angebot.';
$GLOBALS['TL_LANG']['MSC']['applicationList']['message']['waiting'] = '%s steht auf der Warteliste für dieses Angebot';
$GLOBALS['TL_LANG']['MSC']['applicationList']['message']['error'] = '%s ist für dieses Angebot nicht angemeldet.';
$GLOBALS['TL_LANG']['MSC']['applicationList']['error'] = 'Ein Fehler ist aufgetreten.';
// Add attendee as host
$GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['submit'] = 'Teilnehmer verbindlich hinzufügen';
$GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['confirmation'] = 'Es wurden %u Teilnehmer zu diesem Angebot hinzugefügt.';
$GLOBALS['TL_LANG']['MSC']['document']['export_error'] = 'Ein Fehler beim Export ist aufgetreten';

// Offers management
// * buttons
$GLOBALS['TL_LANG']['MSC']['detailsLink'][0] = 'Ansehen';
$GLOBALS['TL_LANG']['MSC']['detailsLink'][1] = 'Das Angebot in der Vorschau ansehen';
$GLOBALS['TL_LANG']['MSC']['editLink'][0] = 'Bearbeiten';
$GLOBALS['TL_LANG']['MSC']['editLink'][1] = 'Dieses Angebot bearbeiten';
$GLOBALS['TL_LANG']['MSC']['applicationlistLink'][0] = 'Teilnehmerliste';
$GLOBALS['TL_LANG']['MSC']['applicationlistLink'][1] = 'Die Teilnehmerliste dieses Angebotes ansehen';
$GLOBALS['TL_LANG']['MSC']['deleteLink'][0] = 'Löschen';
$GLOBALS['TL_LANG']['MSC']['deleteLink'][1] = 'Dieses Angebotes löschen';

$GLOBALS['TL_LANG']['MSC']['downloadList'][0] = 'Teilnehmerliste downloaden';
$GLOBALS['TL_LANG']['MSC']['downloadList'][1] = 'Die Teilnehmerliste zu diesem Angebot als PDF herunterladen';

$GLOBALS['TL_LANG']['MSC']['itemConfirmDeleteLink'] = 'Wollen Sie das Angebot %s wirklich löschen?';
$GLOBALS['TL_LANG']['MSC']['itemDeleteConfirmation'] = 'Das Angebot wurde erfolgreicht gelöscht.';
$GLOBALS['TL_LANG']['MSC']['attendanceConfirmDeleteLink'] = 'Möchten Sie die Anmeldung für %s (%s) wirklich zurückziehen?';
$GLOBALS['TL_LANG']['MSC']['attendanceDeletedConfirmation'] = 'Die Anmeldung wurde erfolgreicht zurückgezogen.';

$GLOBALS['TL_LANG']['MSC']['al-states']['confirmed'] = 'angemeldet';
$GLOBALS['TL_LANG']['MSC']['al-states']['waiting-list'] = 'auf Warteliste';
$GLOBALS['TL_LANG']['MSC']['al-states']['error'] = 'nicht angemeldet';

$GLOBALS['TL_LANG']['MSC']['state'] = 'Status';
$GLOBALS['TL_LANG']['MSC']['recall'] = 'Zurückziehen';

$GLOBALS['TL_LANG']['MSC']['enableVariantsOptions']['n'] = 'Ich biete das Angebot einmalig an';
$GLOBALS['TL_LANG']['MSC']['enableVariantsOptions']['y'] = 'Ich biete das Angebot mehrmals (an mehreren Terminen) an';

if (null !== ($objMetaModel = \Ferienpass\Model\Participant::getInstance()->getMetaModel())) # MetaModel is null if BE user not logged in
{
	$GLOBALS['TL_LANG']['MSC'][$objMetaModel->getTableName()]['details'] = 'Bearbeiten';
}

$GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'] = [
    'confirmed'  => 'Zusage',
    'waiting'    => 'wartend',
    'waitlisted' => 'Warteliste',
    'error'      => 'abgelehnt',
];

/**
 * Errors
 */
$GLOBALS['TL_LANG']['ERR']['ageInputMissingValues'] = 'Bitte füllen Sie alle notwendigen Werte für die Angabe "%s" aus.';
$GLOBALS['TL_LANG']['ERR']['ageInputReverseAgeRanges'] = 'Ihre eingegeben Altersgrenze <em>%s</em> ist nicht höher als die Altersgrenze <em>%s</em>.';
$GLOBALS['TL_LANG']['ERR']['changedDateOfBirthAfterwards'] = 'Das Geburtsdatum kann nicht mehr verändert werden, nachdem Sie Ihr Kind zu Angeboten angemeldet haben.';
$GLOBALS['TL_LANG']['ERR']['changedAgreementPhotosAfterwards'] = 'Die Einverständniserklärung kann nicht mehr widerrufen werden, nachdem Sie Ihr Kind zu Angeboten angemeldet haben.';
