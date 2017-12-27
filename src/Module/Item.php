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

namespace Richardhj\ContaoFerienpassBundle\Module;


/**
 * Class Offer
 * @package Richardhj\ContaoFerienpassBundle\Module\SingleOffer
 */
abstract class Item extends Items
{

    /**
     * Provide MetaModel item in object
     *
     * @param bool $isProtected Do permission check if true
     *
     * @return string
     */
    public function generate($isProtected = false)
    {
        $this->fetchItem();

        if ('FE' === TL_MODE) {
            // Generate 404 if item not found
            if (null === $this->item) {
                $this->exitWith404();
            }

            // Check permission
            if ($isProtected) {
                $this->checkPermission();
            }
        }

        return parent::generate();
    }
}
