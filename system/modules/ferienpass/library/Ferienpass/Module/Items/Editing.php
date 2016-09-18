<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module\Items;

use Ferienpass\Helper\Message;
use Ferienpass\Helper\Config as FerienpassConfig;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\DataProcessing;
use Ferienpass\Module\Items;
use Haste\Form\Form;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\FrontendEditingItem as Item;


/**
 * Class OfferEditing
 *
 * @property \FrontendTemplate Template
 * @property bool              isNewItem
 *
 * @package Ferienpass\Module
 */
class Editing extends Items
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'offer_editing_default';


    /**
     * Provide item for class
     */
    public function generate()
    {
        // Try to load the the item by its auto_item
        if (!$this->fetchItem()) {
            // Generate 404 if item not found
            if ($this->autoItem) {
                $this->exitWith404();
            }

            // Otherwise create a new item for the referenced owner
            $this->fetchOwnerAttribute();

            $this->item = new Item
            (
                $this->metaModel, [
                    $this->ownerAttribute->getColName() => $this->User->getData(),
                ]
            );

            // Prepare variant creation
            if (($varGroup = (int)\Input::get('vargroup'))) {
                $parentItem = $this->metaModel->findById($varGroup);

                // Exit if permissions for provided var group are insufficient
                if ($parentItem->get($this->ownerAttribute->getColName())['id'] != $this->User->id) {
                    $this->exitWith403();
                }

                // Set a copy as current item
                $this->item = $parentItem->varCopy();

                // Remove alias to trigger the auto generation
                $this->item->set($this->aliasColName, null);
            }

            $this->isNewItem = true;
        } else {
            // Check permission for editing an existent item
            $this->checkPermission();
        }

        return parent::generate();
    }


    /**
     * Generate the module
     * @throws \Exception
     */
    protected function compile()
    {
        $arrMemberGroups = deserialize($this->User->groups);

        $objDcaCombine = $this->database
            ->prepare(
                "SELECT * FROM tl_metamodel_dca_combine WHERE fe_group IN(".implode(',', $arrMemberGroups).") AND pid=?"
            )
            ->limit(1)
            ->execute($this->metaModel->get('id'));

        // Throw exception if no dca combine setting is set
        if (!$objDcaCombine->numRows) {
            throw new \RuntimeException(
                sprintf(
                    'No dca combine setting found for MetaModel ID %u and member groups %s found',
                    $this->metaModel->get('id'),
                    var_export($arrMemberGroups, true)
                )
            );
        }

        /*
         * Get dca and attribute settings
         */
        $dcaDatabase = $this->database
            ->prepare("SELECT * FROM tl_metamodel_dca WHERE id=?")
            ->execute($objDcaCombine->dca_id);

        $attributesDatabase = $this->database
            ->prepare(
                "SELECT a.*,s.*,c.type as condition_type, c.attr_id as condition_attr_id, (SELECT colname FROM tl_metamodel_attribute WHERE id=c.attr_id) as condition_attr_name, c.value as condition_value FROM tl_metamodel_attribute a INNER JOIN tl_metamodel_dcasetting s ON a.id=s.attr_id LEFT JOIN tl_metamodel_dcasetting_condition c ON c.settingId=s.id AND c.enabled=1 WHERE s.pid=? AND s.published=1 ORDER BY s.sorting ASC"
            )
            ->execute($dcaDatabase->id);

        // Exit if a new item creation is not allowed
        if ($this->isNewItem && (!$dcaDatabase->iscreatable || !$dcaDatabase->iseditable)) {
            Message::addError($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);

            $this->Template->message = Message::generate();

            return;
        }

        /*
         * Vars
         */
        $isLocked = !$dcaDatabase->iseditable;
        $modified = false;
        $editVariants = false; # Only true if item is variant base

        // Inform about not editable offer
        if ($isLocked) {
            Message::addInformation($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);
        }

        /** @type \Model $objPage */
        global $objPage;

        /*
         * Build the form
         */
        $form = new Form(
            $this->metaModel->getTableName().'_'.$this->id, 'POST', function ($haste) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $haste->getFormId() === \Input::post('FORM_SUBMIT');
        }
        );

        // Create variant changer for variant base
        if ($this->enableVariants && !$this->item->isVariant(
            )
        ) # do not use isVariantBase() because it returns false if the item is new
        {
            $this->addSubmitOnChangeForInput('#ctrl_variants .radio');

            if ('y' === \Input::post('variants')) {
                $editVariants = true;
            } elseif ('n' === \Input::post('variants')) {
                $editVariants = false;
            } else {
                $editVariants = (0 !== $this->metaModel->findVariants($this->item->get('id'), null)->getCount());
            }

            $form->addFormField(
                'variants',
                [
                    'inputType' => 'radio',
                    'default'   => 'n',
                    'value'     => $editVariants ? 'y' : 'n',
                    'options'   => ['n', 'y'],
                    'reference' => $GLOBALS['TL_LANG']['MSC']['enableVariantsOptions'],
                ]
            );
        }

        // Walk every attribute
        while ($attributesDatabase->next()) {
            // Skip non-variant-attributes if item is variant AND skip variant-attributes if item is variant base
            if (($this->item->isVariant() && !$attributesDatabase->isvariant)
                || ($editVariants && $attributesDatabase->isvariant)
            ) {
                continue;
            }

            // Process dca condition check
            switch ($attributesDatabase->condition_type) {
                case 'conditionpropertyvalueis':
                    if ($this->item->get(
                            $attributesDatabase->condition_attr_name
                        ) != $attributesDatabase->condition_value
                    ) {
                        continue 2;
                    }
            }

            $field = $attributesDatabase->colname;

            $attribute = $this->metaModel->getAttribute($field);

            $data = array_merge
            (
                $attribute->getFieldDefinition
                (
                    [
                        'mandatory' => $attributesDatabase->mandatory,
                        'readonly'  => $isLocked,
                    ]
                ),
                [
                    'value' => $this->metaModel
                        ->getAttribute($field)
                        ->valueToWidget($this->item->get($field)),
                ]
            );

            // Modify arrData by attribute's type
            switch ($attribute->get('type')) /** @noinspection PhpMissingBreakStatementInspection */ {
                // Add date picker for timestamp attributes
                case 'timestamp':

                    // @todo this should be done with a setting in the dca and not hardcoded
                    if ($this->metaModel->get('tableName') == FerienpassConfig::get(
                            FerienpassConfig::PARTICIPANT_MODEL
                        )
                    ) {
                        continue;
                    }

                    $useTimePicker = (in_array($attribute->get('timetype'), ['datim', 'time'])) ? 'true' : 'false';
                    $GLOBALS['TL_JQUERY'][] = <<<HTML
<script>
	(function ($) {
		$(document).ready(function () {
			$.datetimepicker.setLocale('{$objPage->rootLanguage}');

			$('#ctrl_{$attribute->get('colname')}').datetimepicker( {
				timepicker: {$useTimePicker},
 				format: '{$GLOBALS['TL_CONFIG'][$attribute->get('timetype').'Format']}'
 			});
		});
	})(jQuery);
</script>
HTML;
                // Call hooks
                default:
                    if (isset($GLOBALS['FP_HOOKS']['alterEditingFormField']) && is_array(
                            $GLOBALS['FP_HOOKS']['alterEditingFormField']
                        )
                    ) {
                        foreach ($GLOBALS['FP_HOOKS']['alterEditingFormField'] as $varCallback) {
                            $data = (is_callable($varCallback)) ? call_user_func(
                                $varCallback,
                                $data,
                                $attribute,
                                $form
                            ) : $data;
                        }
                    }
            }


            if ($attribute->get('colname') == 'infotable' && !$form->isSubmitted() && empty($data['value'])) {
                $data['value'] = array
                (
                    array
                    (
                        'col_0' => 'Ort',
                        'col_1' => '',
                    ),
                    array
                    (
                        'col_0' => 'Kosten',
                        'col_1' => '',
                    ),
                    array
                    (
                        'col_0' => 'Anmeldung',
                        'col_1' => '',
                    ),
                    array
                    (
                        'col_0' => 'max. Teilnehmer',
                        'col_1' => '',
                    ),
                    array
                    (
                        'col_0' => 'Mitbringen',
                        'col_1' => '',
                    ),

                    array
                    (
                        'col_0' => 'Zu beachten',
                        'col_1' => '',
                    ),
                    array
                    (
                        'col_0' => 'Veranstalter',
                        'col_1' => '',
                    ),
                    array
                    (
                        'col_0' => 'Verantwortlich',
                        'col_1' => '',
                    ),
                );
            }//@todo refactor: move


            // Handle submitOnChange
            if ($attributesDatabase->submitOnChange) {
                $this->addSubmitOnChangeForInput('#ctrl_'.$field);
            }

            $form->addFormField($field, $data);
        }

        //@todo refactor: move
        if ($this->metaModel->getTableName() == FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL)) {
            // Add validator for participant's dateOfBirth
            // It must not be changed afterwards
            $form->addValidator(
                FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH),
                function ($varValue, $objWidget, $objForm) {
                    if ($varValue != $this->item->get($objWidget->name)) {
                        if (Attendance::countByParticipant($this->item->get('id'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['ERR']['changedDateOfBirthAfterwards']);
                        }
                    }
                }
            );

            // Add validator for participant's agreement for photos
            // It must not be revoked afterwards
            $form->addValidator(
                FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS),
                function ($varValue, $objWidget, $objForm) {
                    // Allow to grant but not to revoke
                    if ($varValue != $this->item->get($objWidget->name) && !$varValue) {
                        if (Attendance::countByParticipant($this->item->get('id'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['ERR']['changedAgreementPhotosAfterwards']);
                        }
                    }
                }
            );
        }

        $form->addSubmitFormField('save', $GLOBALS['TL_LANG']['MSC']['saveData']);

        /*
         * Validate the form data
         */
        if ($form->validate()) {
            foreach ($form->fetchAll() as $name => $value) {
                /*
                 * Set the new field value
                 */
                if ($this->metaModel->hasAttribute($name)) {
                    $modified = true;
                    $this->item->set
                    (
                        $name,
                        $this->metaModel
                            ->getAttribute($name)
                            ->widgetToValue($value, $this->item->get('id'))
                    );
                }
            }

            /*
             * Save the MetaModel item
             */
            if ($modified && !$isLocked) {
                // Save item
                $this->item->save();

//				// Trigger data processing synchronisation
//				/** @type \Model\Collection|DataProcessing $objProcesings */
//				$objProcesings = DataProcessing::findBy(array('sync=1', 'filesystem<>?'), array('sendToBrowser'));
//
//				while (null !== $objProcesings && $objProcesings->next())
//				{
//					$objProcesings->current()->run(array($this->objItem->get('id')));
//				}

                // Redirect to item editing page if item is new
                if ($this->isNewItem) {
                    if ($this->item->isVariant()) {
                        // Save variant base to persist non variant attributes
                        $this->metaModel->saveItem
                        (
                            $this->metaModel->findById($this->item->get('vargroup'))
                        );

                        // Output confirmation message
                        Message::addConfirmation(
                            sprintf(
                                'Diese Variante zum Angebot "%s" wurde erfolgreich erstellt.',
                                $this->item->get('name')
                            )
                        ); //@todo lang
                    }

                    \Controller::redirect(
                        $this->addToUrl(
                            sprintf
                            (
                                'items=%s&vargroup=',
                                $this->item->get($this->aliasColName)
                            )
                        )
                    );
                }
            }

            //@todo enhance || being a variant doesn't mean it is opened in a lightbox || we don't want JS here
            // auto-close colorbox
            if ($this->metaModel->get('id') == 2 || $this->item->isVariant()) {
                $GLOBALS['TL_JQUERY'][] = <<<HTML
<script>
    parent.jQuery.colorbox.close();
</script>
HTML;
            } // Check whether there is a jumpTo page
            elseif (null !== ($objJumpTo = $this->objModel->getRelated('jumpTo'))) {
                $this->jumpToOrReload($objJumpTo->row());
            }
        }

        if ($editVariants && $this->item->isVariantBase()) {
            $this->Template->variants =
                $this->newVariantLink()
                .$this->variantsCount($this->metaModel->findVariants($this->item->get('id'), null));
        }

        $this->Template->form = $form->generate();
        $this->Template->message = Message::generate();
    }


    protected function newVariantLink()
    {
        $template = new \FrontendTemplate('ce_hyperlink');
        $template->class = 'create_new_variant';

        //@todo IF lightbox
        $template->attribute = ' data-lightbox="" data-lightbox-iframe="" data-lightbox-reload=""';

        $template->href = sprintf
        (
            '%s?vargroup=%u',
            rtrim(str_replace($this->autoItem, '', \Environment::get('request')), '/').'-variante',
            //@todo be configurable
            $this->item->get('id')
        );

        $template->link = 'Variante erstellen';
        $template->linkTitle = specialchars(
            sprintf('Eine neue Variante zum Element "%s" erstellen', $this->item->get('name'))
        ); //@todo lang

        return $template->parse();
    }


    /**
     * @param IItem[]|IItems $objVariants
     *
     * @return string
     */
    protected function variantsMenu($objVariants)
    {
        $objTemplate = new \FrontendTemplate('nav_default');
        $objTemplate->level = 'variants';

        $arrItems = [];

        while ($objVariants->next()) {
            $arrItems[] = [
                'class' => 'edit-variant',
                'href' => str_replace(
                    $this->item->get($this->aliasColName),
                    $objVariants->getItem()->get($this->aliasColName),
                    \Environment::get('request')
                ),
                'title' => sprintf(specialchars('Die Variante "%s" bearbeiten'), $objVariants->getItem()->get('name')),
                //@todo lang
                'target' => ' data-lightbox=""',
                'link' => $objVariants->getItem()->get('name'),
            ];
        }

        $objTemplate->items = $arrItems;

        return $objTemplate->parse();
    }


    /**
     * @param IItem[]|IItems $variants
     *
     * @return string
     */
    protected function variantsCount($variants)
    {
        return sprintf(
            '<p class="count_variants">Es existieren aktuell %u Varianten zu diesem Angebot.</p>',
            $variants->getCount()
        ); //@todo lang
    }


    /**
     * Add a submitOnChange handler for a specific input
     *
     * @param string $inputId The jQuery selector
     */
    protected function addSubmitOnChangeForInput($inputId)
    {
        if (strlen($GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange'])) {
            $GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange'] = preg_replace_callback(
                '/(\$\(\')(.*?)(\'\).change)/',
                function ($arrMatches) use ($inputId) {
                    return sprintf('%s%s,%s%s', $arrMatches[1], $arrMatches[2], $inputId, $arrMatches[3]);
                },
                $GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange']
            );

            return;
        }

        $GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange'] = <<<HTML
<script>
	(function ($) {
		$(document).ready(function () {
			$('{$inputId}').change(function () {
				$(this).parents('form:first')[0].submit();
			});
		});
	})(jQuery);
</script>
HTML;

    }
}
