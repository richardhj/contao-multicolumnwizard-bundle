<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;


use Haste\Form\Form;


/**
 * Class HostLogo
 * @package Ferienpass\Module
 */
class HostLogo extends Items
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'mod_host_logo';


    /**
     * Generate module
     */
    protected function compile()
    {
        $this->fetchItem(\FrontendUser::getInstance()->ferienpass_host);

        $form = new Form(
            $this->type.'_'.$this->id, 'POST', function ($haste) {
            /** @noinspection PhpUndefinedMethodInspection */
            return \Input::post('FORM_SUBMIT') === $haste->getFormId();
        }
        );

        $currentLogo = $this->item->get('logo')['bin'][0];

        $form->addFormField(
            'upload',
            [
                'value'     => $currentLogo,
                'inputType' => 'host_logo',
                'eval'      =>
                    [
                        'uploadFolder' => $this->hostLogoDir,
//                'useUserHomeDir' => $this->logo_useHomeDir,
//                'renameFile'     => $this->logo_renameFile,
//                'outputSize'     => deserialize($this->imgSize, true)
                    ],
            ]
        );

        $form->addFormField(
            'download',
            [
                'inputType' => 'html',
                'eval'      => [
                    'html' => $this->getCurrentDownload($currentLogo),
                ],
            ]
        );

        $form->addFormField(
            'reset',
            [
                'label'     => $GLOBALS['TL_LANG']['MSC']['host_logo_reset'],
                'inputType' => 'checkbox',
            ]
        );

        $form->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['host_logo_save']);


        if ($form->validate()) {
            if ($form->fetch('reset')) {
                // Delete deposited file
                if (null !== ($objFilesModel = \FilesModel::findByPk($currentLogo))) {
                    $objFile = new \File($objFilesModel->path);
                    $objFile->delete();
                }

                // Reset member model
                $this->item->set('logo', null);
                $this->item->save();

                \Controller::reload();
            } elseif ($form->fetch('upload')) {
                $attribute = $this->metaModel->getAttribute('logo');
                $this->item->set('logo', $attribute->widgetToValue($form->fetch('upload'), $this->item->get('id')));
                $this->item->save();
            }
        }

        $this->Template->form = $form->generate();
    }


    /**
     * Get the current logo download as parsed html
     *
     * @see ContentDownload
     *
     * @param $singleSRC
     *
     * @return string
     */
    protected function getCurrentDownload($singleSRC)
    {
        $objFile = \FilesModel::findByPk($singleSRC);

        if ($objFile === null) {
            return '';
        }

        $allowedDownload = trimsplit(',', strtolower(\Config::get('allowedDownload')));

        // Return if the file type is not allowed
        if (!in_array($objFile->extension, $allowedDownload)) {
            return '';
        }

        $file = \Input::get('file', true);

        // Send the file to the browser and do not send a 404 header (see #4632)
        if ($file != '' && $file == $objFile->path) {
            \Controller::sendFileToBrowser($file);
        }

        $objFile = new \File($objFile->path, true);

        $strHref = \Environment::get('request');

        // Remove an existing file parameter (see #5683)
        if (preg_match('/(&(amp;)?|\?)file=/', $strHref)) {
            $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
        }

        $strHref .= ((\Config::get('disableAlias') || strpos(
                    $strHref,
                    '?'
                ) !== false) ? '&amp;' : '?').'file='.\System::urlEncode($objFile->value);

        $objTemplate = new \FrontendTemplate('ce_download');

        $objTemplate->class = 'ce_download';
        $objTemplate->link = specialchars($objFile->basename);
        $objTemplate->title = specialchars(
            $this->titleText ?: sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)
        );
        $objTemplate->href = $strHref;
        $objTemplate->filesize = \System::getReadableSize($objFile->filesize, 1);
        $objTemplate->icon = TL_ASSETS_URL.'assets/contao/images/'.$objFile->icon;
        $objTemplate->mime = $objFile->mime;
        $objTemplate->extension = $objFile->extension;
        $objTemplate->path = $objFile->dirname;

        return $objTemplate->parse();
    }
}