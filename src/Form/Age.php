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
 * Class Age
 * @package Richardhj\ContaoFerienpassBundle\Form
 */
class Age extends \Richardhj\ContaoFerienpassBundle\Widget\Age
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_fpage';


    /**
     * Error message
     *
     * @var string
     */
    protected $strError = '';


    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-age';

}
