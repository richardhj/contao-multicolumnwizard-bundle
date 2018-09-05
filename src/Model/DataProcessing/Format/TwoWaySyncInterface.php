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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;


/**
 * Interface FormatTwoWaySyncInterface
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing
 */
interface TwoWaySyncInterface
{

    /**
     * @param array  $files The files.
     * @param string $originFileSystem The origin file system.
     */
    public function syncFilesFromRemoteSystem(array $files, string $originFileSystem): void;
}