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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing;


use MetaModels\IFactory;
use MultiColumnWizard\Event\GetOptionsEvent;

/**
 * Class ICalAttributeOptionsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing
 */
class ICalAttributeOptionsListener
{
    /**
     * The MetaModels factory.
     *
     * @var IFactory
     */
    private $factory;

    /**
     * FilterOptionsListener constructor.
     *
     * @param IFactory $factory The MetaModels factory.
     */
    public function __construct(IFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Set the available filters as options.
     *
     * @param GetOptionsEvent $event The event.
     */
    public function handle(GetOptionsEvent $event): void
    {
        if (('ical_fields' !== $event->getPropertyName())
            || 'metamodel_attribute' !== $event->getSubPropertyName()
            || ('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
        ) {
            return;
        }

        $options = [];

        foreach ($this->factory->collectNames() as $table) {
            $metaModel = $this->factory->getMetaModel($table);
            if (null === $metaModel) {
                continue;
            }

            foreach ($metaModel->getAttributes() as $attrName => $attribute) {
                $options[$table][$attrName] = $attribute->getName();
            }
        }

        $event->setOptions($options);
    }
}
