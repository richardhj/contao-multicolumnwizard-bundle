<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Model;


/**
 * Class Config
 * @property $offer_model
 * @property $participant_model
 * @property $offer_attribute_name
 * @property $offer_attribute_applicationlist_active
 * @property $offer_attribute_applicationlist_max
 * @property $offer_attribute_age
 * @property $offer_attribute_date_check_age
 * @property $participant_attribute_name
 * @property $participant_attribute_dateofbirth
 * @property $participant_attribute_agreement_photos
 * @property $max_applications_per_day
 * @property $registration_allowed_zip_codes
 * @property $registration_required_fields
 * @package Ferienpass\Model
 */
class Config extends AbstractSingleModel
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_ferienpass_config';


    protected static $objInstance;


    /**
     * MetaModels
     */
    const OFFER_MODEL = 'offer_model';


    const PARTICIPANT_MODEL = 'participant_model';


    /**
     * Attributes
     */
    const OFFER_ATTRIBUTE_NAME = 'offer_attribute_name';


    const OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE = 'offer_attribute_applicationlist_active';


    const OFFER_ATTRIBUTE_APPLICATIONLIST_MAX = 'offer_attribute_applicationlist_max';


    const OFFER_ATTRIBUTE_AGE = 'offer_attribute_age';


    const OFFER_ATTRIBUTE_DATE_CHECK_AGE = 'offer_attribute_date_check_age'; //@todo this attribute is used for others purposes too. check usages


    const PARTICIPANT_ATTRIBUTE_NAME = 'participant_attribute_name';


    const PARTICIPANT_ATTRIBUTE_DATEOFBIRTH = 'participant_attribute_dateofbirth';


    const PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS = 'participant_attribute_agreement_photos';


    /**
     * Restrictions
     */
    const PARTICIPANT_MAX_APPLICATIONS_PER_DAY = 'max_applications_per_day';


    const PARTICIPANT_ALLOWED_ZIP_CODES = 'registration_allowed_zip_codes';


    const PARTICIPANT_REGISTRATION_REQUIRED_FIELDS = 'registration_required_fields';
}
