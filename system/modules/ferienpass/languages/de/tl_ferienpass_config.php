<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */
use Ferienpass\Helper\Config as FerienpassConfig;


/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ferienpass_config']['models_legend'] = 'MetaModels';
$GLOBALS['TL_LANG']['tl_ferienpass_config']['attributes_legend'] = 'MetaModels-Attribute';
$GLOBALS['TL_LANG']['tl_ferienpass_config']['restrictions_legend'] = 'Restriktionen';


/**
 * Fields
 */
// Models
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_MODEL][0] = 'Angebote-MetaModel';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_MODEL][1] = 'Der MetaModel, welches die Angebote beinhaltet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_MODEL][0] = 'Teilnehmer-MetaModel';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_MODEL][1] = 'Der MetaModel, welches die Teilnehmer beinhaltet.';
// Attributes
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_NAME][0] = 'Name des Angebotes';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_NAME][1] = 'Das Attribut, welches die den Namen des Angebotes beinhaltet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE][0] = 'Angebot verwendet Online-Anmeldesystem';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE][1] = 'Das Attribut, welches festlegt, ob ein Angebot das Online-Anmeldesystem verwendet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX][0] = 'Maximale Teilnehmerzahl für Angebot';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX][1] = 'Das Attribut, welches die maximale Teilnehmerzahl des Angebotes beinhaltet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ATTRIBUTE_NAME][0] = 'Name des Teilnehmers';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ATTRIBUTE_NAME][1] = 'Das Attribut, welches den Namen des Teilnhmers beinhaltet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH][0] = 'Geburtsdatum des Teilnehmers';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH][1] = 'Das Attribut, welches das Geburtsdatum des Teilnhmers beinhaltet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS][0] = 'Einverständniserklärung Fotos';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS][1] = 'Das Attribut, welches die Einverständniserklärung für Fotos von Teilnehmer beinhaltet.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_DATE_CHECK_AGE][0] = 'Datum des Angebotes für Alters-Check';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_DATE_CHECK_AGE][1] = 'Das Attribut, welches das Datum des Angebotes beinhaltet. Das Datum wird für den Alterscheck des Teilnehmers herangezogen.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_AGE][0] = 'Altersbeschränkung des Angebots';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::OFFER_ATTRIBUTE_AGE][1] = 'Das Attribut, welches die Altersbeschränkung des Angebotes beinhaltet.';
// Restrictions
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ALLOWED_ZIP_CODES][0] = 'Erlaubte Postleitzahlen bei Registrierung';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_ALLOWED_ZIP_CODES][1] = 'Geben Sie die Postleitzahlen an, für welche nur eine Registrierung möglich ist.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_MAX_APPLICATIONS_PER_DAY][0] = 'maximale Angebot-Anmeldungen per Tag';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_MAX_APPLICATIONS_PER_DAY][1] = 'Geben Sie an, wie oft sich ein Teilnehmer (nicht Mitglied) für Angebote an einem einzelnen Tag anmelden darf. Bei 0 deaktiviert.';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS][0] = 'Pflichtfelder für Benutzerdaten';
$GLOBALS['TL_LANG']['tl_ferienpass_config'][FerienpassConfig::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS][1] = 'Wählen Sie die Felder aus, die von Mitgliedern angegeben werden müssen.';
