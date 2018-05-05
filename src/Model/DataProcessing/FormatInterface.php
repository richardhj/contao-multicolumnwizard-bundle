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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing;


use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use MetaModels\IItems;


/**
 * Interface FormatInterface
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing
 */
interface FormatInterface
{

    /**
     * Process the items and provide the files
     *
     * @param IItems         $items The items to process.
     *
     * @param DataProcessing $model
     *
     * @return array
     */
    public function processItems(IItems $items, DataProcessing $model): array;
}
