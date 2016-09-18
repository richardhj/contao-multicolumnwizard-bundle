<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;


class Config
{

	/**
	 * The prefix used for all ferienpass assigned config values
	 */
	const CONFIG_PREFIX = 'ferienpass_';

	/**
	 * MetaModels
	 */
	const OFFER_MODEL = 'offer:model';
	const PARTICIPANT_MODEL = 'participant:model';

	/**
	 * Attributes
	 */
	const OFFER_ATTRIBUTE_NAME = 'offer:attribute:name';
	const OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE = 'offer:attribute:applicationlist_active';
	const OFFER_ATTRIBUTE_APPLICATIONLIST_MAX = 'offer:attribute:applicationlist_max';
	const OFFER_ATTRIBUTE_AGE = 'offer:attribute:age';
	const OFFER_ATTRIBUTE_DATE_CHECK_AGE = 'offer:attribute:date_check_age'; //@todo this attribute is used for others purposes too. check usages
	const PARTICIPANT_ATTRIBUTE_NAME = 'participant:attribute:name';
	const PARTICIPANT_ATTRIBUTE_DATEOFBIRTH = 'participant:attribute:dateOfBirth';
	const PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS = 'participant:attribute:agreement_photos';

	/**
	 * Restrictions
	 */
	const PARTICIPANT_MAX_APPLICATIONS_PER_DAY = 'participant:restriction:max_applications_per_day';
	const PARTICIPANT_ALLOWED_ZIP_CODES = 'participant:restriction:allowed_zip_codes';
	const PARTICIPANT_REGISTRATION_REQUIRED_FIELDS = 'participant:restriction:registration_required_fields';


	/**
	 * Return a configuration value
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public static function get($key)
	{
		return \Contao\Config::get(static::CONFIG_PREFIX . $key);
	}


	/**
	 * Set and save a configuration value
	 *
	 * @param $key
	 * @param $value
	 */
	public static function set($key, $value)
	{
		\Contao\Config::persist(static::CONFIG_PREFIX . $key, $value);
	}
}
