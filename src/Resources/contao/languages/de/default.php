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


/**
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['MSC']['editParticipant'] = 'Teilnehmer bearbeiten';
$GLOBALS['TL_LANG']['MSC']['addNewParticipant'] = 'Einen neuen Teilnehmer erstellen';
$GLOBALS['TL_LANG']['MSC']['noAttendances'] = 'Es liegen keine Anmeldungen vor.';
$GLOBALS['TL_LANG']['MSC']['noParticipants'] = 'Sie müssen vorerst Teilnehmer anlegen. {{link_open::26}}Klicken Sie hier.{{link_close}}';

// User application
$GLOBALS['TL_LANG']['MSC']['user_application']['active'] = 'Dieses Angebot verwendet das Online-Anmeldeverfahren.';
$GLOBALS['TL_LANG']['MSC']['user_application']['inactive'] = 'Dieses Angebot verwendet <strong>nicht</strong> das Online-Anmeldeverfahren.';
$GLOBALS['TL_LANG']['MSC']['user_application']['past'] = 'Dieses Angebot liegt in der Vergangenheit.';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['label'] = 'Teilnehmer auswählen';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['placeholder'] = 'Hier klicken und Teilnehmer auswählen';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['ok'] = '%s';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['already_attending'] = '%s (bereits angemeldet)';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['age_not_allowed'] = '%s (ungeignet für das Alter)';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['limit_reached'] = '%s (Pro-Tag-Limit erreicht)';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['double_booking'] = '%s (Terminüberschneidung mit "%s")';
$GLOBALS['TL_LANG']['MSC']['user_application']['participant']['slabel'] = 'Anmelden';
$GLOBALS['TL_LANG']['MSC']['user_application']['message']['confirmed'] = '%s ist angemeldet für dieses Angebot.';
$GLOBALS['TL_LANG']['MSC']['user_application']['message']['waiting'] = '%s ist vorgemerkt und wartet auf die Zuteilung für dieses Angebot.';
$GLOBALS['TL_LANG']['MSC']['user_application']['message']['waiting-list'] = '%s steht auf der Warteliste für dieses Angebot.';
$GLOBALS['TL_LANG']['MSC']['user_application']['message']['error'] = '%s ist für dieses Angebot nicht angemeldet.';
$GLOBALS['TL_LANG']['MSC']['user_application']['error'] = 'Ein Fehler ist aufgetreten.';
$GLOBALS['TL_LANG']['MSC']['user_application']['vacant_places_label'] = '%d freie Plätze';
$GLOBALS['TL_LANG']['MSC']['user_application']['booking_state'][0] = 'Das Angebot hat keine Teilnehmer-Beschränkung.<br>Sie können sich jetzt für das Angebot anmelden.';
$GLOBALS['TL_LANG']['MSC']['user_application']['booking_state'][1] = 'Es sind noch Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
$GLOBALS['TL_LANG']['MSC']['user_application']['booking_state'][2] = 'Es sind nur noch wenige Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
$GLOBALS['TL_LANG']['MSC']['user_application']['booking_state'][3] = 'Es sind keine freien Plätze mehr verfügbar,<br>aber Sie können sich auf die Warteliste eintragen.';
$GLOBALS['TL_LANG']['MSC']['user_application']['booking_state'][4] = 'Es sind keine freien Plätze mehr verfügbar<br>und die Warteliste ist ebenfalls sehr voll.';
$GLOBALS['TL_LANG']['MSC']['user_application']['high_utilization_text'] = 'Es wollen mehr Kinder teilnehmen, als es Plätze gibt. Die aktuelle Auslastung liegt bei %d %%.';
$GLOBALS['TL_LANG']['MSC']['user_application']['current_application_system']['none'] = 'Anmeldungen sind zur Zeit nicht möglich!';
$GLOBALS['TL_LANG']['MSC']['user_application']['current_application_system']['lot'] = 'Es läuft aktuell das Los-Verfahren. Eine Zusage für die Anmeldung bekommen Sie erst nach dem Stichtag.';
$GLOBALS['TL_LANG']['MSC']['user_application']['current_application_system']['firstcome'] = 'Es läuft aktuell das Windhundprinzip. Das bedeutet, dass Sie sofort auf die Teilnehmerliste geschrieben werden. Die Zusage bekommen Sie sofort im Anschluss.';
$GLOBALS['TL_LANG']['MSC']['user_application']['variants_list_link'] = 'Alternative Termine zum gleichen Termin';

// Add attendee as host
$GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['submit'] = 'Teilnehmer verbindlich hinzufügen';
$GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['confirmation'] = 'Es wurden %u Teilnehmer zu diesem Angebot hinzugefügt.';
$GLOBALS['TL_LANG']['MSC']['document']['export_error'] = 'Ein Fehler beim Export ist aufgetreten';


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

$GLOBALS['TL_LANG']['MSC']['editParticipant'] = 'Bearbeiten';
$GLOBALS['TL_LANG']['MSC']['editOffer'] = 'Bearbeiten';
$GLOBALS['TL_LANG']['MSC']['editHost'] = 'Daten aktualisieren';

$GLOBALS['TL_LANG']['MSC']['host_logo_save']  = 'Logo speichern';
$GLOBALS['TL_LANG']['MSC']['host_logo_reset'][0] = 'Zurücksetzen';
$GLOBALS['TL_LANG']['MSC']['host_logo_reset'][1] = 'Zurücksetzen auf Vorgabe';

$GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'] = [
    'confirmed'  => 'Zusage',
    'waiting'    => 'wartend',
    'waitlisted' => 'Warteliste',
    'error'      => 'abgelehnt',
];

$GLOBALS['TL_LANG']['MSC']['yesno'][0] = 'Nein';
$GLOBALS['TL_LANG']['MSC']['yesno'][1] = 'Ja';
$GLOBALS['TL_LANG']['MSC']['offer_date']['start'][0] = 'Beginn';
$GLOBALS['TL_LANG']['MSC']['offer_date']['end'][0] = 'Ende';

/**
 * Errors
 */
$GLOBALS['TL_LANG']['ERR']['ageInputMissingValues'] = 'Bitte füllen Sie alle notwendigen Werte für die Angabe "%s" aus.';
$GLOBALS['TL_LANG']['ERR']['ageInputReverseAgeRanges'] = 'Ihre eingegeben Altersgrenze <em>%s</em> ist nicht höher als die Altersgrenze <em>%s</em>.';
$GLOBALS['TL_LANG']['ERR']['changedDateOfBirthAfterwards'] = 'Das Geburtsdatum kann nicht mehr verändert werden, nachdem Sie Ihr Kind zu Angeboten angemeldet haben.';
$GLOBALS['TL_LANG']['ERR']['changedAgreementPhotosAfterwards'] = 'Die Einverständniserklärung kann nicht mehr widerrufen werden, nachdem Sie Ihr Kind zu Angeboten angemeldet haben.';
$GLOBALS['TL_LANG']['ERR']['missingHostForMember'] = 'Bitte erstellen Sie vorerst einen Veranstalter und ordnen Sie diesen hier zu.';
