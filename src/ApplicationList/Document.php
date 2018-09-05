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

namespace Richardhj\ContaoFerienpassBundle\ApplicationList;


use Contao\Controller;
use Haste\Haste;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class Document
{

    /**
     * @var IItem
     */
    private $offer;

    public function __construct(IItem $offer)
    {
        $this->offer = $offer;
    }

    public function outputToBrowser(): void
    {
        $pdf = $this->generatePDF();

        $pdf->Output(
            'teilnehmerliste.pdf',
            'D'
        );
    }

    /**
     * Generate the pdf document
     *
     * @return \TCPDF
     */
    protected function generatePDF(): \TCPDF
    {
        // TCPDF configuration
        $l = [];

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
        $pdf->SetTitle('Teilnehmerliste');

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
        $pdf->writeHTML($this->generateTemplate(), true, 0, true, 0);

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
    protected function generateTemplate(): string
    {
        global $objPage;

        $template = new \FrontendTemplate('fp_document_default');

        $template->title         = 'Teilnehmerliste';
        $template->rootPageTitle = $objPage->rootTitle;
        $template->offer         = $this->offer;
        $template->dateFormat    = $objPage->dateFormat ?: $GLOBALS['TL_CONFIG']['dateFormat'];
        $template->timeFormat    = $objPage->timeFormat ?: $GLOBALS['TL_CONFIG']['timeFormat'];
        $template->datimFormat   = $objPage->datimFormat ?: $GLOBALS['TL_CONFIG']['datimFormat'];

        // Render the collection
        $collectionTemplate = new \FrontendTemplate('fp_collection_applicationlist');

        $this->addAttendeesToTemplate($collectionTemplate);

        $template->attendees = $collectionTemplate->parse();

        // Generate template and fix PDF issues, see Contao's ModuleArticle
        $buffer = Haste::getInstance()->call('replaceInsertTags', [$template->parse(), false]);
        $buffer = html_entity_decode($buffer, ENT_QUOTES, \Config::get('characterSet'));
        $buffer = Controller::convertRelativeUrls($buffer, '', true);

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
     * Fetch item's attributes by its collection type and add them to template
     *
     * @param \Template $template
     */
    protected function addAttendeesToTemplate($template): void
    {
        $attendees          = [];
        $attendanceStatuses = [];

        /** @var Attendance $attendee */
        foreach (Attendance::findByOffer($this->offer->get('id')) as $attendee) {
            $attendees[$attendee->status][] = $attendee;

            if (null === $attendanceStatuses[$attendee->status]) {
                $attendanceStatuses[$attendee->status] = $attendee->getStatus();
            }
        }

        $template->maxParticipants    = $this->offer->get('applicationlist_max');
        $template->countParticipants  = Attendance::countByOffer($this->offer->get('id'));
        $template->offer              = $this->offer;
        $template->attendees          = $attendees;
        $template->attendanceStatuses = $attendanceStatuses;
    }
}
