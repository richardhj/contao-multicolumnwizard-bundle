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
     * FormatInterface constructor.
     *
     * @param DataProcessing $model
     */
    public function __construct(DataProcessing $model);

    /**
     * @param IItems $items
     *
     * @return FormatInterface
     */
    public function setItems(IItems $items): self;

    /**
     * Process the items and provide the files in the expected format
     *
     * @return self
     */
    public function processItems(): self;

    /**
     * Get the files in the expected format as an array
     *
     * @return array The file information in the format of `listContents`
     */
    public function getFiles(): array;
}
