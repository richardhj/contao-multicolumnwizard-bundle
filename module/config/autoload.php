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
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'offer_editing_default'                             => 'system/modules/ferienpass/templates',
    'mod_offers_management'                             => 'system/modules/ferienpass/templates/module',
    'mod_items_editing_actions'                         => 'system/modules/ferienpass/templates/module',
    'mod_user_attendances'                              => 'system/modules/ferienpass/templates/module',
    'mod_offer_addattendeehost'                         => 'system/modules/ferienpass/templates/module',
    'mod_ferienpass_messages'                           => 'system/modules/ferienpass/templates/module',
    'mod_host_logo'                                     => 'system/modules/ferienpass/templates/module',

	// Calendar
    'cal_offers'                                        => 'system/modules/ferienpass/templates',

	// Widgets
    'be_widget_fpage'                                   => 'system/modules/ferienpass/templates',
    'form_fpage'                                        => 'system/modules/ferienpass/templates',
    'form_mcw'                                          => 'system/modules/ferienpass/templates',

	// Application list
    'mod_offer_applicationlist'                         => 'system/modules/ferienpass/templates/module',
    'mod_offer_applicationlisthost'                     => 'system/modules/ferienpass/templates/module',

	// MetaModel
    'metamodel_details_in_lightbox'                     => 'system/modules/ferienpass/templates',
    'metamodel_multiple_buttons'                        => 'system/modules/ferienpass/templates',
    'metamodel_table'                                   => 'system/modules/ferienpass/templates',
    'mm_attr_age'                                       => 'system/modules/ferienpass/templates',
    'mm_attr_tabletext_xmlexport'                       => 'system/modules/ferienpass/templates',
    'mm_attr_file_xmlexport'                            => 'system/modules/ferienpass/templates',
    'mm_attr_checkbox_applicationlist_active_xmlexport' => 'system/modules/ferienpass/templates',
    'mm_attr_combinedvalues_date_xmlexport'             => 'system/modules/ferienpass/templates',
    'mm_attr_select_host_xmlexport'                     => 'system/modules/ferienpass/templates',
    'mm_attr_offer_date'                                => 'system/modules/ferienpass/templates',

	// Backend
    'be_overview'                                       => 'system/modules/ferienpass/templates',
    'be_simple_content'                                 => 'system/modules/ferienpass/templates',

	// Document
    'fp_document_default'                               => 'system/modules/ferienpass/templates/document',

	// Collection
    'fp_collection_applicationlist'                     => 'system/modules/ferienpass/templates/collection',

	// Data Processing
    'dataprocessing_xml_comment'                        => 'system/modules/ferienpass/templates',

	// jQuery
    'j_chosen'                          => 'system/modules/ferienpass/templates/jquery',

    // DcGeneral
    'dcbe_general_offerAttendancesView' => 'system/modules/ferienpass/templates',
));
