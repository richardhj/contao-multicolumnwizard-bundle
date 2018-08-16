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

interface FilesystemInterface
{

    /**
     * @param array          $files The file paths to handle.
     *
     * @param DataProcessing $model The model.
     *
     * @return void
     */
    public function processFiles(array $files, DataProcessing $model): void;
}
