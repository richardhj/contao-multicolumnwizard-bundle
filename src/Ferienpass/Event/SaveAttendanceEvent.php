<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Event;

use Ferienpass\Model\Attendance;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class SaveAttendanceEvent
 * @package Ferienpass\Event
 */
class SaveAttendanceEvent extends Event
{

    const NAME = 'ferienpass.model.attendance.save';


    /**
     * @var Attendance
     */
    protected $model;


    /**
     * @var Attendance
     */
    protected $originalModel;


    /**
     * SaveAttendanceEvent constructor.
     *
     * @param Attendance       $model
     * @param Attendance $originalModel
     */
    public function __construct(Attendance $model, Attendance $originalModel)
    {
        $this->model = $model;
        $this->originalModel = $originalModel;
    }


    /**
     * @return Attendance
     */
    public function getModel()
    {
        return $this->model;
    }


    /**
     * @return Attendance
     */
    public function getOriginalModel()
    {
        return $this->originalModel;
    }
}
