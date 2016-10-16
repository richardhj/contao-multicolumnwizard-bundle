<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;


/**
 * Class Offer
 * @package Ferienpass\Module\SingleOffer
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
