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

namespace Richardhj\ContaoFerienpassBundle\EventListener\UserApplication\BuildParticipantOptions;


use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Helper\GetFerienpassConfigTrait;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class DisableLimitReachedParticipantsListener
{

    use GetFerienpassConfigTrait;

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    /**
     * Disable participants from options that have reached their limit.
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event The event.
     *
     * @return void
     */
    public function onBuildParticipantsOptionsForUserApplication(BuildParticipantOptionsForUserApplicationEvent $event)
    {
        if (!$this->applicationSystem instanceof FirstCome) {
            return;
        }

        $options = $event->getResult();

        $maxApplicationsPerDay = ApplicationSystem::findFirstCome()->maxApplicationsPerDay;
        if (!$maxApplicationsPerDay) {
            return;
        }

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            if (Attendance::countByParticipantAndDay($option['value']) >= $maxApplicationsPerDay) {
                $options[$k]['disabled'] = true;
                $options[$k]['label']    = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['limit_reached'],
                    $option['label']
                );
            }
        }

        $event->setResult($options);
    }
}
