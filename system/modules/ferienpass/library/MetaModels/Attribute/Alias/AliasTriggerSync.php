<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package     MetaModels
 * @subpackage  AttributeAlias
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Andreas Isaak <info@andreas-isaak.de>
 * @author      Stefan Heimes <stefan_heimes@hotmail.com>
 * @author      Oliver Hoff <oliver@hofff.com>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Alias;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\ReplaceInsertTagsEvent;
use Ferienpass\Model\DataProcessing;
use MetaModels\Attribute\BaseSimple;
use MetaModels\Render\Setting\Simple;

/**
 * This is the MetaModelAttribute class for handling text fields.
 *
 * @package    MetaModels
 * @subpackage AttributeAlias
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class AliasTriggerSync extends Alias
{

	/**
	 * {@inheritDoc}
	 */
	public function getAttributeSettingNames()
	{
		return array_merge(
			parent::getAttributeSettingNames(),
			array(
				'data_processing'
			)
		);
	}


	/**
	 * {@inheritdoc}
	 */
	public function getDefaultRenderSettings()
	{
		$objSetting = new Simple(
			array
			(
				'template' => 'mm_attr_alias'
			)
		);
		return $objSetting;
	}


	/**
	 * {@inheritdoc}
	 */
	public function modelSaved($objItem)
	{
		// Generate alias
		parent::modelSaved($objItem);

		// Run associated data processing
		$objProcessing = DataProcessing::findByPk($this->get('data_processing'));

		if (null !== $objProcessing)
		{
			$objProcessing->run($objItem->get('id'));
		}
	}
}
