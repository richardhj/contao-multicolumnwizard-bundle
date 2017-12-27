<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Model;

use Contao\Model;
use Contao\StringUtil;
use Haste\Generator\RowClass;
use Haste\Haste;
use MetaModels\IItems;


/**
 * Class Document
 *
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
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
    protected $collection;


    /**
     * Generate the document and send it to browser
     *
     * @param Model\Collection|\MetaModels\IItems $collection
     */
    public function outputToBrowser($collection)
    {
        $this->prepareEnvironment($collection);

        $tokens = $this->prepareCollectionTokens();
        $pdf    = $this->generatePDF($tokens);

        $pdf->Output(
            $this->prepareFileName($this->fileTitle, $tokens) . '.pdf',
            'D'
        );
    }


    /**
     * Generate the document and store it to a given path
     *
     * @param Model\Collection|\MetaModels\IItems $collection
     * @param string                              $path Absolute path to the directory the file should be stored in
     *
     * @return string Absolute path to the file
     */
    public function outputToFile($collection, $path): string
    {
        $this->prepareEnvironment($collection);

        $tokens   = $this->prepareCollectionTokens();
        $pdf      = $this->generatePDF($tokens);
        $fileName = $this->prepareFileName($this->fileTitle, $tokens, $path) . '.pdf';

        $pdf->Output(
            $fileName,
            'F'
        );

        return $fileName;
    }


    /**
     * Generate the pdf document
     *
     * @param array $tokens
     *
     * @return \TCPDF
     */
    protected function generatePDF(array $tokens): \TCPDF
    {
        // TCPDF configuration
        $l                    = [];
        $l['a_meta_dir']      = 'ltr';
        $l['a_meta_charset']  = $GLOBALS['TL_CONFIG']['characterSet'];
        $l['a_meta_language'] = substr($GLOBALS['TL_LANGUAGE'], 0, 2);
        $l['w_page']          = 'page';

        // Include TCPDF config
        require_once TL_ROOT . '/system/config/tcpdf.php';

        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(PDF_AUTHOR);
        $pdf->SetTitle(StringUtil::parseSimpleTokens($this->documentTitle, $tokens));

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
        $pdf->writeHTML($this->generateTemplate($tokens), true, 0, true, 0);

        $pdf->lastPage();

        return $pdf;
    }


    /**
     * Generate and return document template
     *
     * @param array $tokens
     *
     * @return string
     */
    protected function generateTemplate(array $tokens): string
    {
        //$objPage = \PageModel::findWithDetails($objCollection->page_id);
        global $objPage;

        $template = new \FrontendTemplate($this->documentTpl);
        $template->setData($this->arrData);

        $template->title       = StringUtil::parseSimpleTokens($this->documentTitle, $tokens);
        $template->collection  = $this->collection;
        $template->page        = $objPage;
        $template->dateFormat  = $objPage->dateFormat ?: $GLOBALS['TL_CONFIG']['dateFormat'];
        $template->timeFormat  = $objPage->timeFormat ?: $GLOBALS['TL_CONFIG']['timeFormat'];
        $template->datimFormat = $objPage->datimFormat ?: $GLOBALS['TL_CONFIG']['datimFormat'];

        // Render the collection
        $collectionTemplate = new \FrontendTemplate($this->collectionTpl);

        $this->addCollectionToTemplate($collectionTemplate);

        $template->items = $collectionTemplate->parse();

        // Generate template and fix PDF issues, see Contao's ModuleArticle
        $buffer = Haste::getInstance()->call('replaceInsertTags', [$template->parse(), false]);
        $buffer = html_entity_decode($buffer, ENT_QUOTES, \Config::get('characterSet'));
        $buffer = \Controller::convertRelativeUrls($buffer, '', true);

        // Remove form elements and JavaScript links
        $search = [
            '@<form.*</form>@Us',
            '@<a [^>]*href="[^"]*javascript:[^>]+>.*</a>@Us',
        ];

        $buffer = preg_replace($search, '', $buffer);

        // URL decode image paths (see contao/core#6411)
        // Make image paths absolute
        $buffer = preg_replace_callback(
            '@(src=")([^"]+)(")@',
            function ($args) {
                if (preg_match('@^(http://|https://)@', $args[2])) {
                    return $args[2];
                }

                return $args[1] . TL_ROOT . '/' . rawurldecode($args[2]) . $args[3];
            },
            $buffer
        );

        // Handle line breaks in preformatted text
        $buffer = preg_replace_callback('@(<pre.*</pre>)@Us', 'nl2br_callback', $buffer);

        // Default PDF export using TCPDF
        $search = [
            '@<span style="text-decoration: ?underline;?">(.*)</span>@Us',
            '@(<img[^>]+>)@',
            '@(<div[^>]+block[^>]+>)@',
            '@[\n\r\t]+@',
            '@<br( /)?><div class="mod_article@',
            '@href="([^"]+)(pdf=[0-9]*(&|&amp;)?)([^"]*)"@',
        ];

        $replace = [
            '<u>$1</u>',
            '<br>$1',
            '<br>$1',
            ' ',
            '<div class="mod_article',
            'href="$1$4"',
        ];

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }


    /**
     * Add the collection to a template
     *
     * @param \Template $template
     */
    public function addCollectionToTemplate($template)
    {
        $this->addItemsToCollectionTemplate($template);

        $template->collection = $this->collection;
    }


    /**
     * Fetch item's attributes by its collection type and add them to template
     *
     * @param \Template $template
     *
     * @return array An array of all items with each item array containing every attribute. Auxiliary attributes data
     *               are accessible too
     * @todo We have to sort the items by $this->orderCollectionBy
     */
    protected function addItemsToCollectionTemplate($template): array
    {
        $items = [];

        if ($this->collection instanceof Model\Collection) {
            // Set pointer to beginning
            $this->collection->reset();

            while ($this->collection->next()) {
                $item = [];

                foreach ($this->collection->row() as $attr => $value) {
                    $item[$attr] = $value;

                    // Load auxiliary data (if using the col name scheme "xxx_id")
                    if (in_array($attr, ['offer', 'participant'])) {


                        /** @type MetaModelBridge $class */
                        $class = __NAMESPACE__ . '\\' . ucfirst($attr);

                        // Try to find model
                        if (class_exists($class)) {
                            $objRelated = $class::getInstance()->findById($value);

                            foreach ($objRelated->getMetaModel()->getAttributes() as $attrRelatedName => $attrRelated) {
                                $item[$attr . '_' . $attrRelatedName] = $objRelated->get($attrRelatedName);
                            }
                        }
                    }
                }

                $items[$this->collection->status][] = $item;
                // Add css classes
                RowClass::withKey('rowClass')->addCount('row_')->addFirstLast('row_')->addEvenOdd('row_')->applyTo(
                    $items[$this->collection->status]
                );
            }
        } elseif ($this->collection instanceof IItems) {
            $arrValues = $this->collection->parseAll();

            foreach ($arrValues as $arrItem) {
                $item = [];

                foreach ($arrItem['text'] as $attr => $value) {
                    $item[$attr] = $arrItem['text'][$attr];

                    // Add related fields (e.g. from select fields)
                    if (is_array($arrItem['raw'][$attr])) {
                        foreach ($arrItem['raw'][$attr] as $refName => $refVal) {
                            $item[$attr . '_' . $refName] = $refVal;
                        }
                    }
                }

                $items[] = $item;
            }
        }


        $template->items = $items;

        return $items;
    }


    /**
     * Loads the page configuration and language before generating a PDF.
     *
     * @param Model\Collection|\MetaModels\IItems $collection
     */
    protected function prepareEnvironment($collection)
    {
        $this->collection = $collection;

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
    protected function prepareCollectionTokens(): array
    {
        $tokens = [];

        if ($this->collection instanceof Model\Collection) {
            foreach ($this->collection->row() as $k => $v) {
                $tokens['collection_' . $k] = $v;
            }
        } elseif ($this->collection instanceof IItems) //@todo
        {
            foreach ($this->collection->getItem()->getMetaModel()->getAttributes() as $k => $v) {
                $tokens['collection_' . $k] = $this->collection->getItem()->get($v);
            }
        }

        return $tokens;
    }


    /**
     * Prepare file name
     *
     * @param string $name   File name
     * @param array  $tokens Simple tokens
     * @param string $path   Path
     *
     * @return string Sanitized file name
     */
    protected function prepareFileName($name, $tokens = [], $path = ''): string
    {
        // Replace simple tokens
        $name = StringUtil::parseSimpleTokens($name, $tokens);
        $name = $this->sanitizeFileName($name);

        if ($path) {
            // Make sure the path contains a trailing slash
            $path = preg_replace('/([^\/]+)$/', '$1/', $path);

            $name = $path . $name;
        }

        return $name;
    }


    /**
     * Sanitize file name
     *
     * @param string  $name              File name
     * @param boolean $preserveUppercase Preserve uppercase
     *
     * @return string Sanitized file name
     */
    protected function sanitizeFileName($name, $preserveUppercase = true): string
    {
        return standardize(ampersand($name, false), $preserveUppercase);
    }
}
