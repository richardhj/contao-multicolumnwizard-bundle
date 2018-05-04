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
     * FilesystemInterface constructor.
     *
     * @param DataProcessing $model
     */
    public function __construct(DataProcessing $model);

    /**
     * @param IItems $items
     *
     * @return FilesystemInterface
     *
     * @deprecated
     */
    public function setItems(IItems $items): self;

    /**
     * @param array $files
     *
     * @return void
     */
    public function processFiles(array $files): void;
}
