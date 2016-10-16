<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 26.01.15
 * Time: 19:18
 */


/** @noinspection PhpUndefinedMethodInspection */
$table = \MemberModel::getTable();


/**
 * Config
 */
$GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = ['Ferienpass\Helper\UserAccount', 'setRequiredFields'];


/**
 * Fields
 */
$GLOBALS['TL_DCA'][$table]['fields']['persist'] = [
    'label'     => &$GLOBALS['TL_LANG'][$table]['persist'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class'   => 'w50 m12',
        'feEditable' => true,
    ],
    'sql'       => "char(1) NOT NULL default ''",
];
