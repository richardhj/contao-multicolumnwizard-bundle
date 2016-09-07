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
		return array(
			ModelToLabelEvent::NAME => 'modelToLabel'
		);
	}


	/**
	 * @param ModelToLabelEvent $objEvent
	 */
	public function modelToLabel(ModelToLabelEvent $objEvent)
	{
		$objModel = $objEvent->getModel();

		if ($objModel instanceof Model && $objModel->getProviderName() == FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL))
		{
			$args = $objEvent->getArgs();
			$args['firstname'] = 'Asdf';
			$objEvent->setArgs($args);
			dump($objEvent->getArgs());
		}
	}
}
