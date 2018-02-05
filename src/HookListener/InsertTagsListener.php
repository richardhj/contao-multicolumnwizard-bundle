<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;

class InsertTagsListener
{

    /**
     * @var FirstCome
     */
    private $firstComeApplicationSystem;

    /**
     * InsertTagsListener constructor.
     *
     * @param FirstCome $firstComeApplicationSystem
     */
    public function __construct(FirstCome $firstComeApplicationSystem)
    {
        $this->firstComeApplicationSystem = $firstComeApplicationSystem;
    }

    /**
     * @param string $tag
     *
     * @return string|false
     */
    public function onReplaceInsertTags($tag)
    {
        $elements = trimsplit('::', $tag);

        if ('ferienpass' === $elements[0]) {
            switch ($elements[1]) {
                case 'max_applications_per_day':
                    return $this->firstComeApplicationSystem->getMaxApplicationsPerDay();
                    break;
            }
        }

        return false;
    }
}
