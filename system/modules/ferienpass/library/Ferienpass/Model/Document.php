<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Model;

use Contao\Model;
use Contao\StringUtil;
use Haste\Generator\RowClass;
use Haste\Haste;
use MetaModels\IItems;


class Document extends Model
{

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected static $strTable = 'tl_ferienpass_document';


	/**
	 * The collection object
	 *
	 * @var Model\Collection|\MetaModels\IItems
	 */
	protected $objCollection;


	/**
	 * Generate the document and send it to browser
	 *
	 * @param Model\Collection|\MetaModels\IItems $objCollection
	 */
	public function outputToBrowser($objCollection)
	{
		$this->prepareEnvironment($objCollection);

		$arrTokens = $this->prepareCollectionTokens();
		$pdf = $this->generatePDF($arrTokens);

		$pdf->Output
		(
			$this->prepareFileName($this->fileTitle, $arrTokens) . '.pdf',
			'D'
		);
	}


	/**
	 * Generate the document and store it to a given path
	 *
	 * @param Model\Collection|\MetaModels\IItems $objCollection
	 * @param string $strDirectoryPath Absolute path to the directory the file should be stored in
	 *
	 * @return string Absolute path to the file
	 */
	public function outputToFile($objCollection, $strDirectoryPath)
	{
		$this->prepareEnvironment($objCollection);

		$arrTokens = $this->prepareCollectionTokens();
		$pdf = $this->generatePDF($arrTokens);
		$strFile = $this->prepareFileName($this->fileTitle, $arrTokens, $strDirectoryPath) . '.pdf';

		$pdf->Output
		(
			$strFile,
			'F'
		);

		return $strFile;
	}


	/**
	 * Generate the pdf document
	 *
	 * @param array $arrTokens
	 *
	 * @return \TCPDF
	 */
	protected function generatePDF(array $arrTokens)
	{
		// TCPDF configuration
		$l = array();
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_charset'] = $GLOBALS['TL_CONFIG']['characterSet'];
		$l['a_meta_language'] = substr($GLOBALS['TL_LANGUAGE'], 0, 2);
		$l['w_page'] = 'page';

		// Include TCPDF config
		require_once TL_ROOT . '/system/config/tcpdf.php';

		// Create new PDF document
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);

		// Set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor(PDF_AUTHOR);
		$pdf->SetTitle(StringUtil::parseSimpleTokens($this->documentTitle, $arrTokens));

		// Prevent font subsetting (huge speed improvement)
		$pdf->setFontSubsetting(false);

		// Remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// Set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

		// Set auto page breaks
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

		// Set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// Set some language-dependent strings
		$pdf->setLanguageArray($l);

		// Initialize document and add a page
		$pdf->AddPage();

		// Set font
		$pdf->SetFont(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN);

		// Write the HTML content
		$pdf->writeHTML($this->generateTemplate($arrTokens), true, 0, true, 0);

		$pdf->lastPage();

		return $pdf;
	}


	/**
	 * Generate and return document template
	 *
	 * @param array $arrTokens
	 *
	 * @return string
	 */
	protected function generateTemplate(array $arrTokens)
	{
		//$objPage = \PageModel::findWithDetails($objCollection->page_id);
		global $objPage;

		$objTemplate = new \FrontendTemplate($this->documentTpl);
		$objTemplate->setData($this->arrData);

		$objTemplate->title = StringUtil::parseSimpleTokens($this->documentTitle, $arrTokens);
		$objTemplate->collection = $this->objCollection;
		$objTemplate->page = $objPage;
		$objTemplate->dateFormat = $objPage->dateFormat ?: $GLOBALS['TL_CONFIG']['dateFormat'];
		$objTemplate->timeFormat = $objPage->timeFormat ?: $GLOBALS['TL_CONFIG']['timeFormat'];
		$objTemplate->datimFormat = $objPage->datimFormat ?: $GLOBALS['TL_CONFIG']['datimFormat'];

		// Render the collection
		$objCollectionTemplate = new \FrontendTemplate($this->collectionTpl);

		$this->addCollectionToTemplate($objCollectionTemplate);

		$objTemplate->items = $objCollectionTemplate->parse();

		// Generate template and fix PDF issues, see Contao's ModuleArticle
		$strBuffer = Haste::getInstance()->call('replaceInsertTags', array($objTemplate->parse(), false));
		$strBuffer = html_entity_decode($strBuffer, ENT_QUOTES, \Config::get('characterSet'));
		$strBuffer = \Controller::convertRelativeUrls($strBuffer, '', true);

		// Remove form elements and JavaScript links
		$arrSearch = array
		(
			'@<form.*</form>@Us',
			'@<a [^>]*href="[^"]*javascript:[^>]+>.*</a>@Us'
		);

		$strBuffer = preg_replace($arrSearch, '', $strBuffer);

		// URL decode image paths (see contao/core#6411)
		// Make image paths absolute
		$strBuffer = preg_replace_callback('@(src=")([^"]+)(")@', function ($args)
		{
			if (preg_match('@^(http://|https://)@', $args[2]))
			{
				return $args[2];
			}

			return $args[1] . TL_ROOT . '/' . rawurldecode($args[2]) . $args[3];
		}, $strBuffer);

		// Handle line breaks in preformatted text
		$strBuffer = preg_replace_callback('@(<pre.*</pre>)@Us', 'nl2br_callback', $strBuffer);

		// Default PDF export using TCPDF
		$arrSearch = array
		(
			'@<span style="text-decoration: ?underline;?">(.*)</span>@Us',
			'@(<img[^>]+>)@',
			'@(<div[^>]+block[^>]+>)@',
			'@[\n\r\t]+@',
			'@<br( /)?><div class="mod_article@',
			'@href="([^"]+)(pdf=[0-9]*(&|&amp;)?)([^"]*)"@'
		);

		$arrReplace = array
		(
			'<u>$1</u>',
			'<br>$1',
			'<br>$1',
			' ',
			'<div class="mod_article',
			'href="$1$4"'
		);

		$strBuffer = preg_replace($arrSearch, $arrReplace, $strBuffer);

		return $strBuffer;
	}


	/**
	 * Add the collection to a template
	 *
	 * @param \Template $objTemplate
	 */
	public function addCollectionToTemplate($objTemplate)
	{
		$this->addItemsToCollectionTemplate($objTemplate);

		$objTemplate->collection = $this->objCollection;
	}


	/**
	 * Fetch item's attributes by its collection type and add them to template
	 *
	 * @param \Template $objTemplate
	 *
	 * @return array An array of all items with each item array containing every attribute. Auxiliary attributes data
	 *               are accessible too
	 * @todo We have to sort the items by $this->orderCollectionBy
	 */
	protected function addItemsToCollectionTemplate($objTemplate)
	{
		$arrItems = array();

		if ($this->objCollection instanceof Model\Collection)
		{
			// Set pointer to beginning
			$this->objCollection->reset();

			while ($this->objCollection->next())
			{
				$item = array();

				foreach ($this->objCollection->row() as $attr => $value)
				{
					$item[$attr] = $value;

					// Load auxiliary data (if using the col name scheme "xxx_id")
					if (substr($attr, -3) == '_id')
					{
						$field = substr($attr, 0, -3);

						/** @type MetaModelBridge $strClass */
						$strClass = __NAMESPACE__ . '\\' . ucfirst($field);

						// Try to find model
						if (class_exists($strClass))
						{
							$objRelated = $strClass::getInstance()->findById($value);

							foreach ($objRelated->getMetaModel()->getAttributes() as $attrRelatedName => $attrRelated)
							{
								$item[$field . '_' . $attrRelatedName] = $objRelated->get($attrRelatedName);
							}
						}
					}
				}

				$arrItems[] = $item;
			}
		}
		elseif ($this->objCollection instanceof IItems)
		{
			$arrValues = $this->objCollection->parseAll();

			foreach ($arrValues as $arrItem)
			{
				$item = array();

				foreach ($arrItem['text'] as $attr => $value)
				{
					$item[$attr] = $arrItem['text'][$attr];

					// Add related fields (e.g. from select fields)
					if (is_array($arrItem['raw'][$attr]))
					{
						foreach ($arrItem['raw'][$attr] as $refName => $refVal)
						{
							$item[$attr . '_' . $refName] = $refVal;
						}
					}
				}

				$arrItems[] = $item;
			}
		}

		// Add css classes
		RowClass::withKey('rowClass')->addCount('row_')->addFirstLast('row_')->addEvenOdd('row_')->applyTo($arrItems);

		$objTemplate->items = $arrItems;

		return $arrItems;
	}


	/**
	 * Loads the page configuration and language before generating a PDF.
	 *
	 * @param Model\Collection|\MetaModels\IItems $objCollection
	 */
	protected function prepareEnvironment($objCollection)
	{
		$this->objCollection = $objCollection;

//		global $objPage;
//
//		if (!is_object($objPage) && $objCollection->pageId > 0) {
//			$objPage = \PageModel::findWithDetails($objCollection->pageId);
//			$objPage = \Isotope\Frontend::loadPageConfig($objPage);
//
//			\System::loadLanguageFile('default', $GLOBALS['TL_LANGUAGE'], true);
//		}
	}


	/**
	 * Prepares the collection tokens
	 *
	 * @return array
	 */
	protected function prepareCollectionTokens()
	{
		$arrTokens = array();

		if ($this->objCollection instanceof Model\Collection)
		{
			foreach ($this->objCollection->row() as $k => $v)
			{
				$arrTokens['collection_' . $k] = $v;
			}
		}
		elseif ($this->objCollection instanceof IItems) //@todo
		{
			foreach ($this->objCollection->getItem()->getMetaModel()->getAttributes() as $k => $v)
			{
				$arrTokens['collection_' . $k] = $this->objCollection->getItem()->get($v);
			}
		}

		return $arrTokens;
	}

	/**
	 * Prepare file name
	 *
	 * @param string $strName   File name
	 * @param array  $arrTokens Simple tokens
	 * @param string $strPath   Path
	 *
	 * @return string Sanitized file name
	 */
	protected function prepareFileName($strName, $arrTokens = array(), $strPath = '')
	{
		// Replace simple tokens
		$strName = StringUtil::parseSimpleTokens($strName, $arrTokens);
		$strName = $this->sanitizeFileName($strName);

		if ($strPath)
		{
			// Make sure the path contains a trailing slash
			$strPath = preg_replace('/([^\/]+)$/', '$1/', $strPath);

			$strName = $strPath . $strName;
		}

		return $strName;
	}


	/**
	 * Sanitize file name
	 *
	 * @param string  $strName              File name
	 * @param boolean $blnPreserveUppercase Preserve uppercase
	 *
	 * @return string Sanitized file name
	 */
	protected function sanitizeFileName($strName, $blnPreserveUppercase = true)
	{
		return standardize(ampersand($strName, false), $blnPreserveUppercase);
	}
}
