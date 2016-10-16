<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\ApplicationSystem;


use Ferienpass\Model\Attendance;
use MetaModels\IItem;


abstract class AbstractApplicationSystem
{

    abstract public function findAttendanceStatus(Attendance $attendance, IItem $offer);
}