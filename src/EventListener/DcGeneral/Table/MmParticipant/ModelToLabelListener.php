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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmParticipant;


use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use MetaModels\DcGeneral\Data\Model;

class ModelToLabelListener
{

    /**
     * Add the member edit link when in participant list view
     *
     * @param ModelToLabelEvent $event The event.
     */
    public function handle(ModelToLabelEvent $event)
    {
        $model = $event->getModel();

        if ($model instanceof Model && 'mm_participant' === $model->getProviderName()) {
            $args = $event->getArgs();

            $metaModel     = $model->getItem()->getMetaModel();
            $parentColName = $metaModel->getAttributeById($metaModel->get('owner_attribute'))->getColName();

            // No parent referenced
            if (!$args[$parentColName]) {
                return;
            }

            System::loadLanguageFile('tl_member');

            $parentRaw = $model->getItem()->get($parentColName);

            // Adjust the label
            foreach ($args as $k => $v) {
                switch ($k) {
                    case $parentColName:
                        $args[$k] = sprintf(
                            '<a href="contao/main.php?do=member&amp;act=edit&amp;id=%1$u&amp;popup=1&amp;nb=1&amp;rt=%4$s" class="open_parent" title="%3$s" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%3$s\',\'url\':this.href});return false">%2$s</a>',
                            // Member ID
                            $parentRaw['id'],
                            // Link
                            '<i class="fa fa-external-link tl_gray"></i> ' . $args[$k],
                            // Member edit description
                            sprintf(
                                $GLOBALS['TL_LANG']['tl_member']['edit'][1],
                                $parentRaw['id']
                            ),
                            REQUEST_TOKEN
                        );
                        break;

                    default:
                        if ('' === $model->getItem()->get($k) && '' !== ($parentData = $parentRaw[$k])) {
                            $args[$k] = sprintf('<span class="tl_gray">%s</span>', $parentData);
                        }
                }
            }

            $event->setArgs($args);
        }
    }
}

