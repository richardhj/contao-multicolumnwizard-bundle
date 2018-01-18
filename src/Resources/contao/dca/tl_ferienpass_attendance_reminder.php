<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

use Doctrine\DBAL\Connection;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;

$table = Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder::getTable();


/** @noinspection PhpUndefinedMethodInspection */
$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config'       => [
        'dataContainer' => 'General',
        'sql'           => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list'         => [
        'sorting'           => [
            'mode'        => 1,
            //            'fields'      => ['type'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields'      => [
                'remind_before',
                'nc_notification',
                'attendance_status',
            ],
            'showColumns' => true,
        ],
        'global_operations' => [
            'back' =>
                [
                    'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                    'href'       => 'mod=&table=',
                    'class'      => 'header_back',
                    'attributes' => 'onclick="Backend.getScrollOffset();"',
                ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG'][$table]['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                                .'\')) return false; Backend.getScrollOffset();"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
            'toggle' => [
                'label'          => &$GLOBALS['TL_LANG'][$table]['toggle'],
                'icon'           => 'visible.gif',
                'toggleProperty' => 'published',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes' => [
        'default' => [
            'config'    => [
                //'title',
                'remind_before',
                'nc_notification',
                'attendance_status',
            ],
            'published' => [
                'published',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'                => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'            => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'remind_before'     => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['remind_before'],
            'exclude'   => true,
            'inputType' => 'inputUnit',
            'options'   => [
                'hours',
                'days',
            ],
            'eval'      => [
                'doNotCopy' => true,
                'mandatory' => true,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'nc_notification'   => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['nc_notification'],
            'exclude'          => true,
            'inputType'        => 'select',
            'foreignKey'       => 'tl_nc_notification.title',
            'filter'           => true,
            'options_callback' => function () {
                /** @var Connection $connection */
                $connection    = \Contao\System::getContainer()->get('database_connection');
                $notifications = $connection->createQueryBuilder()
                    ->select('id', 'title')
                    ->from('tl_nc_notification')
                    ->where('type=:type')
                    ->orderBy('title')
                    ->setParameter('type', 'application_list_reminder')
                    ->execute();

                return $notifications->fetchAll(\PDO::FETCH_COLUMN, 'title');
            },
            'eval'             => [
                'mandatory'          => true,
                'unique'             => true,
                'includeBlankOption' => true,
                'tl_class'           => 'w50',
                'chosen'             => true,
                'submitOnChange'     => true,
            ],
            //            'wizard'           => [
            //                function (DcCompat $dc) {
            //                    return ($dc->value < 1)
            //                        ? ''
            //                        : ' <a href="contao/main.php?do=nc_notifications&table=tl_nc_message&amp;id=' . $dc->value
            //                          . '&amp;popup=1&amp;nb=1&amp;rt=' . REQUEST_TOKEN . '" title="' . sprintf(
            //                              specialchars($GLOBALS['TL_LANG']['tl_birthdaymailer']['edit_notification'][1]),
            //                              $dc->value
            //                          ) . '" style="padding-left:3px" onclick="Backend.openModalIframe({\'width\':768,\'title\':\''
            //                          . specialchars(
            //                              str_replace(
            //                                  "'",
            //                                  "\\'",
            //                                  sprintf(
            //                                      $GLOBALS['TL_LANG']['tl_birthdaymailer']['edit_notification'][1],
            //                                      $dc->value
            //                                  )
            //                              )
            //                          ) . '\',\'url\':this.href});return false">' . \Image::getHtml(
            //                            'alias.gif',
            //                            $GLOBALS['TL_LANG']['tl_birthdaymailer']['edit_notification'][0],
            //                            'style="vertical-align:top"'
            //                        ) . '</a>';
            //                }
            //            ],
            'sql'              => "int(10) unsigned NOT NULL default '0'",
            'relation'         => [
                'type' => 'hasOne',
                'load' => 'eager',
            ],
        ],
        'attendance_status' => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['attendance_status'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => array_reduce(
                iterator_to_array(AttendanceStatus::findAll()),
                function (array $carry, AttendanceStatus $status) {
                    $carry[$status->id] = $GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'][$status->type];

                    return $carry;
                },
                []
            ),
            'eval'      => [
                'includeBlankOption' => true,
                'tl_class'           => 'w50',
            ],
            'sql'       => "int(10) NOT NULL default '0'",
        ],
        'published'         => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['published'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                //'submitOnChange' => true,
                'doNotCopy' => true,
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
    ],
];
