<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage FilterCheckbox
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <mail@netzmacht.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;


/**
 * Filter "attendance available" for FE-filtering, based on filters by the meta models team.
 *
 * @package    MetaModels
 * @subpackage FrontendFilter
 * @author     Richard Henkenjohann
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
		if (isset($arrFilterUrl[$strParamName]))
		{
			$arrFilterUrl[$strParamName] =
				($arrFilterUrl[$strParamName] == '1' && $this->get('ynmode') == 'no'
					? '-1'
					: $arrFilterUrl[$strParamName]);
		}

		if ($objAttribute && $strParamName && !empty($arrFilterUrl[$strParamName]))
		{
			// Param -1 has to be '' meaning 'really empty'.
			$arrFilterUrl[$strParamName] = ($arrFilterUrl[$strParamName] == '-1' ? '' : $arrFilterUrl[$strParamName]);

			$strQuery = sprintf(<<<'SQL'
SELECT item.id
FROM %1$s AS item
LEFT JOIN (
	SELECT offer, COUNT(id) as current_participants
	FROM %2$s
	GROUP BY %2$s.offer
) as attendance ON attendance.offer = item.id
WHERE (
	item.%3$s <> 1
	OR (item.%3$s = 1 AND (item.%4$s > current_participants OR ISNULL(current_participants)))
)
AND item.%5$s >= %6$s
SQL
				,
				$objMetaModel->getTableName(),
				Attendance::getTable(),
                FerienpassConfig::getInstance()->offer_attribute_applicationlist_active,
				$objAttribute->getColName(),
                FerienpassConfig::getInstance()->offer_attribute_date,
			    time()
            );

			$objFilterRule = new SimpleQuery($strQuery, array(), 'id', $objMetaModel->getServiceContainer()->getDatabase());
			$objFilter->addFilterRule($objFilterRule);

			return;
		}

		$objFilter->addFilterRule(new StaticIdList(null));
	}
}
