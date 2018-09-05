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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\EventListener;

use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use MetaModels\CoreBundle\EventListener\DcGeneral\Table\FilterSetting\AbstractFilterSettingTypeRenderer;

/**
 * Handles rendering of model from tl_metamodel_filtersetting.
 */
class AttendanceAvailableFilterSettingTypeRendererListener extends AbstractFilterSettingTypeRenderer
{
    /**
     * {@inheritdoc}
     */
    protected function getTypes(): array
    {
        return ['attendance_available'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelParameters(EnvironmentInterface $environment, ModelInterface $model): array
    {
        return $this->getLabelParametersWithAttributeAndUrlParam($environment, $model);
    }
}
