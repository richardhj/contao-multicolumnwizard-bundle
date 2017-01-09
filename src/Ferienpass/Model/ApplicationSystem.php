<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use Contao\Model;


/**
 * Class ApplicationSystem
 * @property string $type
 * @property mixed $maxApplicationsPerDay
 * @package Ferienpass\Model
 */
class ApplicationSystem extends Model
{

    /**
     * The table name
     *
     * @var string
     */
    protected static $strTable = 'tl_ferienpass_applicationsystem';


    /**
     * Find the application system whose type is "firstcome"
     *
     * @return ApplicationSystem
     */
    public static function findFirstCome()
    {
        return static::findByType('firstcome');
    }


    /**
     * Find the application system whose type is "lot"
     *
     * @return ApplicationSystem
     */
    public static function findLot()
    {
        return static::findByType('lot');
    }


    /**
     * Find a application system model by given type
     *
     * @param $type
     *
     * @return static
     */
    public static function findByType($type)
    {
        return static::findOneBy('type', $type);
    }


    /**
     * Find the application system model that is active currently
     *
     * @return ApplicationSystem
     */
    public static function findCurrent()
    {
        $t = static::$strTable;
        $columns = [];

        $time = \Date::floorToMinute();
        $columns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'".($time + 60)."') AND $t.published='1'";

        return static::findOneBy($columns, []);
    }


    /**
     * @return array
     */
    public static function getApplicationSystemNames()
    {
        global $container;

        return array_map(
            function ($v) {
                list(, , $name) = trimsplit('.', $v);

                return $name;
            },
            array_values(preg_grep('/^ferienpass\.applicationsystem\.(.+)$/', $container->keys()))
        );
    }
}
