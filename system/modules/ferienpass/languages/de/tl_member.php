<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 27.01.15
 * Time: 10:45
 */

/** @noinspection PhpUndefinedMethodInspection */
$table = \MemberModel::getTable();


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['persist'][0] = 'nicht automatisch löschen';
$GLOBALS['TL_LANG'][$table]['persist'][1] = 'Diesen Account von der automatischen Löschung ausschließen';
