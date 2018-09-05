<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Model;


/**
 * Class ApplicationSystem
 *
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
    public static function findFirstCome(): ?ApplicationSystem
    {
        return static::findByType('firstcome');
    }


    /**
     * Find the application system whose type is "lot"
     *
     * @return ApplicationSystem|null
     */
    public static function findLot(): ?ApplicationSystem
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
    public static function findByType($type): ?ApplicationSystem
    {
        return static::findOneBy('type', $type);
    }


    /**
     * Find the application system model that is active currently
     *
     * @return ApplicationSystem|null
     */
    public static function findCurrent(): ?ApplicationSystem
    {
        $t       = static::$strTable;
        $columns = [];

        $time      = \Date::floorToMinute();
        $columns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60)
                     . "') AND $t.published='1'";

        return static::findOneBy($columns, []);
    }
}
