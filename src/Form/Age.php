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
