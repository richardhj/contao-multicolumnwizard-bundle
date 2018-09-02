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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttribute;
use MetaModels\FilterCheckboxBundle\FilterSetting\Checkbox;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;


/**
 * Class AttendanceAvailable
 *
 * @package MetaModels\Filter\Setting
 */
final class AttendanceAvailable extends Checkbox
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * AttendanceAvailable constructor.
     *
     * @param            $collection
     * @param            $data
     * @param Connection $connection
     */
    public function __construct($collection, $data, Connection $connection)
    {
        parent::__construct($collection, $data);

        $this->connection = $connection;
    }

    /**
     * Retrieve the attribute we are filtering on.
     *
     * @return IAttribute|null
     */
    protected function getApplicationListMaxAttribute(): ?IAttribute
    {
        return $this->getMetaModel()->getAttribute('applicationlist_max');
    }

    /**
     * Tells the filter setting to add all of its rules to the passed filter object.
     *
     * The filter rules can evaluate the also passed filter url.
     *
     * A filter url hereby is a simple hash of name => value layout, it may eventually be interpreted
     * by attributes via IMetaModelAttribute::searchFor() method.
     *
     * @param IFilter  $filter       The filter to append the rules to.
     *
     * @param string[] $filterParams The parameters to evaluate.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function prepareRules(IFilter $filter, $filterParams): void
    {
        $metaModel = $this->getMetaModel();
        $paramName = $this->getParamName();
        $attribute = $this->getApplicationListMaxAttribute();

        // If is a checkbox defined as "no", 1 has to become -1 like with radio fields.
        if (isset($filterParams[$paramName])) {
            $filterParams[$paramName] =
                ('1' === $filterParams[$paramName] && 'no' === $this->get('ynmode')
                    ? '-1'
                    : $filterParams[$paramName]);
        }

        if ($attribute && $paramName && !empty($filterParams[$paramName])) {
            // Param -1 has to be '' meaning 'really empty'.
            $filterParams[$paramName] = ('-1' === $filterParams[$paramName] ? '' : $filterParams[$paramName]);

            $query = sprintf(
                <<<'SQL'
                SELECT item.id
FROM %1$s AS item
LEFT JOIN (
  SELECT offer, COUNT(id) AS current_participants
  FROM %2$s
  GROUP BY %2$s.offer
) AS attendance ON attendance.offer = item.id
INNER JOIN (
  SELECT START AS startDateTime, item_id, MIN(START) AS minStartDateTime
  FROM tl_metamodel_offer_date
  GROUP BY tl_metamodel_offer_date.item_id
) AS dates ON dates.item_id = item.id AND startDateTime = minStartDateTime
WHERE (
  item.%3$s <> 1
  OR (item.%3$s = 1 AND (item.%4$s > current_participants OR ISNULL(current_participants)))
)
AND startDateTime >= %5$s
SQL
                ,
                $metaModel->getTableName(),
                Attendance::getTable(),
                'applicationlist_active',
                $attribute->getColName(),
                time()
            );

            $filterRule = new SimpleQuery($query, [], 'id', $this->connection);
            $filter->addFilterRule($filterRule);

            return;
        }

        $filter->addFilterRule(new StaticIdList(null));
    }
}
