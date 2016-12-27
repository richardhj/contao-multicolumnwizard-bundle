<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace MetaModels\Filter\Setting;

use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;


/**
 * Class AttendanceAvailable
 * @package MetaModels\Filter\Setting
 */
class AttendanceAvailable extends Checkbox
{

    /**
     * {@inheritdoc}
     */
    public function prepareRules(IFilter $objFilter, $arrFilterUrl)
    {
        $objMetaModel = $this->getMetaModel();
        $strParamName = $this->getParamName();
        $objAttribute = $objMetaModel->getAttributeById($this->get('attr_id'));

        // If is a checkbox defined as "no", 1 has to become -1 like with radio fields.
        if (isset($arrFilterUrl[$strParamName])) {
            $arrFilterUrl[$strParamName] =
                ('1' === $arrFilterUrl[$strParamName] && 'no' === $this->get('ynmode')
                    ? '-1'
                    : $arrFilterUrl[$strParamName]);
        }

        if ($objAttribute && $strParamName && !empty($arrFilterUrl[$strParamName])) {
            // Param -1 has to be '' meaning 'really empty'.
            $arrFilterUrl[$strParamName] = ('-1' === $arrFilterUrl[$strParamName] ? '' : $arrFilterUrl[$strParamName]);

            $strQuery = sprintf(
                <<<'SQL'
                SELECT item.id
FROM %1$s AS item
LEFT JOIN (
  SELECT offer, COUNT(id) as current_participants
  FROM %2$s
  GROUP BY %2$s.offer
) as attendance ON attendance.offer = item.id
INNER JOIN (
  SELECT start as startDateTime, item_id, MIN(start) as minStartDateTime
  FROM tl_metamodel_offer_date
  GROUP BY tl_metamodel_offer_date.item_id
) as dates ON dates.item_id = item.id AND startDateTime = minStartDateTime
WHERE (
  item.%3$s <> 1
  OR (item.%3$s = 1 AND (item.%4$s > current_participants OR ISNULL(current_participants)))
)
AND startDateTime >= %5$s
SQL
                ,
                $objMetaModel->getTableName(),
                Attendance::getTable(),
                FerienpassConfig::getInstance()->offer_attribute_applicationlist_active,
                $objAttribute->getColName(),
                time()
            );

            $objFilterRule = new SimpleQuery($strQuery, [], 'id', $objMetaModel->getServiceContainer()->getDatabase());
            $objFilter->addFilterRule($objFilterRule);

            return;
        }

        $objFilter->addFilterRule(new StaticIdList(null));
    }
}
