<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Form;

/**
 * Class UploadImage
 * @package Richardhj\ContaoFerienpassBundle\Form
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
                    'uploadFolder' => '926817ba-c7bf-11e6-86e8-acb172276daf', //@todo
//                    'renameFile'   => 'angebot_##id##_'.substr(md5(uniqid(mt_rand())), 0, 6) //@todo
                ],
                (array)$attributes
            )
        );
    }
}  
