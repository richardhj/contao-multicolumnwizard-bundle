<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Form;

/**
 * Class UploadImage
 * @package Ferienpass\Form
 */
class UploadImage extends \UploadPreviewFieldFE
{

    /**
     * Set upload folder for attribute
     *
     * @param array|null $attributes
     */
    public function __construct($attributes = null)
    {
        parent::__construct
        (
            array_merge
            (
                [
                    'uploadFolder' => '7a56a80f-f055-11e4-b330-ce2f81f95ce0', //@todo
                    'renameFile'   => 'angebot_##id##_'.substr(md5(uniqid(mt_rand())), 0, 6) //@todo
                ],
                (array)$attributes
            )
        );
    }
}  
