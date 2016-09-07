<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package    MetaModels
 * @subpackage FilterAge
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Marc Reimann <reimann@mediendepot-ruhr.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Setting;

/**
 * Attribute type factory for text filter settings.
 */
class AgeFilterSettingTypeFactory extends AbstractFilterSettingTypeFactory
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this
            ->setTypeName('age')
            ->setTypeIcon('system/modules/ferienpass/assets/img/filter_fp_age.png')
            ->setTypeClass('MetaModels\Filter\Setting\Age')
            ->allowAttributeTypes(
                'age'
            );
    }
}
