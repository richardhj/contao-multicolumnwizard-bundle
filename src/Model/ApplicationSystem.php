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

namespace Richardhj\ContaoFerienpassBundle\Model;


/**
 * Class ApplicationSystem
 *
 * @property string $title
 * @property string $type
 * @property mixed  $maxApplicationsPerDay
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
// Do not use \Contao\Model as it will conflict with the Contao\Model\Registry->register();
class ApplicationSystem extends \Model
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
     * @return ApplicationSystem|null
     */
    public static function findFirstCome()
    {
        return static::findByType('firstcome');
    }


    /**
     * Find the application system whose type is "lot"
     *
     * @return ApplicationSystem|null
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
     * @return ApplicationSystem|null
     */
    public static function findByType($type)
    {
        return static::findOneBy('type', $type);
    }


    /**
     * Find the application system model that is active currently
     *
     * @return ApplicationSystem|null
     */
    public static function findCurrent()
    {
        $t       = static::$strTable;
        $columns = [];

        $time      = \Date::floorToMinute();
        $columns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60)
                     . "') AND $t.published='1'";

        return static::findOneBy($columns, []);
    }


    /**
     * @return array
     */
    public static function getApplicationSystemNames(): array
    {
        global $container;

        return array_map(
            function ($v) {
                list(, , $name) = trimsplit('.', $v);

                return $name;
            },
            array_values(preg_grep('/^Richardhj\ContaoFerienpassBundle\.applicationsystem\.(.+)$/', $container->keys()))
        );
    }
}
