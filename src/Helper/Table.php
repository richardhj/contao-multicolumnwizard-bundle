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

namespace Richardhj\ContaoFerienpassBundle\Helper;


/**
 * Class Table
 * @package Richardhj\ContaoFerienpassBundle\Helper
 */
class Table
{

    /**
     * @param  array                  $arrRows
     * @param  string                 $strName
     * @param  \Module|\Contao\Module $objModule
     * @param  \Closure|null          $rowClass
     * @param  \Closure|null          $cellClass
     *
     * @return array
     */
    public static function getDataArray($arrRows, $strName, $objModule, $rowClass = null, $cellClass = null)
    {
        /** @var \Contao\PageModel $objPage */
        global $objPage;

        $nl2br = ($objPage->outputFormat == 'xhtml') ? 'nl2br_xhtml' : 'nl2br_html5';
        $arrDataTable = [
            'id'    => $strName.'_'.$objModule->id,
            'class' => $strName,
        ];

        $arrDataTable['useHeader'] = true;

        $arrHeader = [];
        $arrBody = [];

        // Table header
        foreach ($arrRows[0] as $i => $v) {
            // Add cell
            $arrHeader[] = [
                'class'   => 'head_'.$i.(($i == 0) ? ' col_first' : '').(($i == (count(
                                $arrRows[0]
                            ) - 1)) ? ' col_last' : '').(($i == 0 && $objModule->tleft) ? ' unsortable' : ''),
                'content' => (($v != '') ? $nl2br($v) : '&nbsp;'),
            ];
        }

        array_shift($arrRows);

        $arrDataTable['header'] = $arrHeader;
        $limit = count($arrRows);

        // Table body
        for ($j = 0; $j < $limit; $j++) {
            $class_tr = '';

            if ($j == 0) {
                $class_tr .= ' row_first';
            }

            if ($j == ($limit - 1)) {
                $class_tr .= ' row_last';
            }

            if ($rowClass !== null) {
                $class_tr .= ' '.$rowClass($j, $arrRows);
            }

            $class_eo = (($j % 2) == 0) ? ' even' : ' odd';

            foreach ($arrRows[$j] as $i => $v) {
                $class_td = '';

                if ($i == 0) {
                    $class_td .= ' col_first';
                }

                if ($i == (count($arrRows[$j]) - 1)) {
                    $class_td .= ' col_last';
                }

                if ($cellClass !== null) {
                    $class_td .= ' '.$cellClass($i, $arrRows);
                }

                $arrBody['row_'.$j.$class_tr.$class_eo][] = [
                    'class'   => 'col_'.$i.$class_td,
                    'content' => (($v != '') ? $nl2br($v) : '&nbsp;'),
                ];
            }
        }

        $arrDataTable['body'] = $arrBody;

        return $arrDataTable;
    }
}