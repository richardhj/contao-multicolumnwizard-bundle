<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Backend;

use Contao\Message;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;

/**
 * Class ExecuteDataProcessing
 *
 * @package Richardhj\ContaoFerienpassBundle\Backend
 */
class ExecuteDataProcessing
{

    public function execute(\DataContainer $dc)
    {
        $processing = DataProcessing::findByPk($dc->id);
        $processing->run();
        
        Message::addConfirmation('Die Datenverarbeitung wurde erfolgreich ausgeführt.');

        \Controller::redirect(str_replace('&key=execute', '', \Environment::get('request')));
    }
}
