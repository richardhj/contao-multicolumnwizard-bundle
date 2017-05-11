<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


namespace Ferienpass\Model\DataProcessing;


use Ferienpass\Model\DataProcessing;
use MetaModels\IItems;


/**
 * Interface FormatInterface
 *
 * @package Ferienpass\Model\DataProcessing
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
