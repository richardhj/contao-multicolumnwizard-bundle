<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use Contao\Model;


/**
 * Class ApplicationSystem
 * @property string $type
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
            preg_grep('/^ferienpass\.applicationsystem\.(.+)$/', $container->keys())
        );
    }
}