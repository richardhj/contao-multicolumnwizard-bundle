<?php

namespace Ferienpass\Helper;

use Ferienpass\Helper\Config as FerienpassConfig;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use MetaModels\DcGeneral\Data\Model;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Subscriber implements EventSubscriberInterface
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ModelToLabelEvent::NAME => 'modelToLabel',
        ];
    }


    /**
     * @param ModelToLabelEvent $event
     */
    public function modelToLabel(ModelToLabelEvent $event)
    {
        $objModel = $event->getModel();

        if ($objModel instanceof Model
            && FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL) === $objModel->getProviderName()
        ) {
            $args = $event->getArgs();
            $args['firstname'] = 'Asdf';
            $event->setArgs($args);
            dump($event->getArgs());
        }
    }
}
