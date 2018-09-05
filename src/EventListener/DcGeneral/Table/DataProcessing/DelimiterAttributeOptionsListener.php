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
 * Class DelimiterAttributeOptionsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing
 */
class DelimiterAttributeOptionsListener
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
        if (('variant_delimiters' !== $event->getPropertyName())
            || 'metamodel_attribute' !== $event->getSubPropertyName()
            || ('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
        ) {
            return;
        }

        $metaModel = $this->factory->getMetaModel('mm_ferienpass');
        if (null === $metaModel) {
            return;
        }

        $options = [];
        foreach ($metaModel->getAttributes() as $attrName => $attribute) {
            $options[$attrName] = $attribute->getName();
        }

        $event->setOptions($options);
    }
}
