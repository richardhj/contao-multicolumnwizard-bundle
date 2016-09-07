<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 26.01.15
 * Time: 19:18
 */


/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'][] = array('Ferienpass\Helper\UserAccount', 'setRequiredFields');
