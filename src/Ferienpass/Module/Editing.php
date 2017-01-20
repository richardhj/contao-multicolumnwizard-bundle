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

use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Haste\Form\Form;
use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\Select\MetaModelSelect;
use MetaModels\Attribute\Tags\MetaModelTags as MetaModelTagsAttribute;
use MetaModels\FrontendEditingItem as Item;
use MetaModels\IItem;
use MetaModels\IItems;


/**
 * Class Editing
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
        switch (\Input::get('act')) {
            /*
             * Edit
             */
            case 'edit':
                $modelId = ModelId::fromSerialized(\Input::get('id'));

                if ($modelId->getDataProviderName() !== $this->metaModel->getTableName()) {
                    throw new \RuntimeException('data provider name does not match');
                }

                $this->item = $this->metaModel->findById($modelId->getId());

                if (null === $this->item) {
                    $this->exitWith404();
                }

                $this->checkPermission();
                break;

            /*
             * Copy
             */
            case 'copy':
                $modelId = ModelId::fromSerialized(\Input::get('id'));

                if ($modelId->getDataProviderName() !== $this->metaModel->getTableName()
                    || 'mm_ferienpass' !== $this->metaModel->getTableName()
                ) {
                    throw new \RuntimeException('data provider name does not match');
                }

                $itemToCopy = $this->metaModel->findById($modelId->getId());
                $itemToCopy->set('vargroup', null);
                //todo
                $itemToCopy->set('pass_release', '1');

                if (null === $itemToCopy) {
                    $this->exitWith404();
                }

                // Check permission
                if ($itemToCopy->get('host')[MetaModelSelect::SELECT_RAW]['id'] != $this->User->ferienpass_host) {
                    $this->exitWith403();
                }

                $this->item = $itemToCopy->copy();
                // Remove alias to trigger the auto generation
                $this->item->set('alias', null);

                $this->isNewItem = true;
                break;

            /*
             * Create
             */
            default:
                $this->item = new Item(
                    $this->metaModel, []
                );

                switch ($this->metaModel->getTableName()) {
                    case 'mm_ferienpass':
                        $this->item->set('host', $this->User->ferienpass_host);
                        //todo
                        $this->item->set('pass_release', '1');
                        break;

                    case 'mm_participant':
                        $this->item->set('pmember', $this->User->getData());
                        break;
                }

                // Prepare variant creation
                if (($varGroup = \Input::get('vargroup'))) {
                    $modelId = ModelId::fromSerialized($varGroup);

                    if ($modelId->getDataProviderName() !== $this->metaModel->getTableName()) {
                        throw new \RuntimeException('data provider name does not match');
                    }

                    $parentItem = $this->metaModel->findById($modelId->getId());

                    // Exit if permissions for provided var group are insufficient
                    switch ($this->metaModel->getTableName()) {
                        case 'mm_ferienpass':
                            if ($parentItem->get('host')[MetaModelSelect::SELECT_RAW]['id']
                                != $this->User->ferienpass_host
                            ) {
                                $this->exitWith403();
                            }
                            break;
                    }

                    // Set a copy as current item
                    $this->item = $parentItem->varCopy();

//                    // Remove alias to trigger the auto generation
//                    $this->item->set('alias', null);

                    // Remove variant dependent attributes
                    /** @var IAttribute $attribute */
                    foreach ($this->item->getMetaModel()->getAttributes() as $attribute) {
                        if ($attribute->get('isvariant')) {
                            $this->item->set($attribute->getColName(), null);
                        }
                    }
                }

                $this->isNewItem = true;
                break;
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
        if ($this->enableVariants && !$this->item->isVariant()
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

            $data['eval']['class'] = $attributesDatabase->tl_class;

            // Load options for tags attribute
            if ($attribute instanceof MetaModelTagsAttribute) {
                $data['options'] = $attribute->getFilterOptions($this->item->get('id'), false);
            }

            // Modify arrData by attribute's type
            switch ($attribute->get('type')) /** @noinspection PhpMissingBreakStatementInspection */ {
                // Add date picker for timestamp attributes
                case 'timestamp':

                    // @todo this should be done with a setting in the dca and not hardcoded
                    if ('mm_participant' === $this->metaModel->get('tableName')) {
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
                $data['value'] = [
                    [
                        'col_0' => 'Ort',
                        'col_1' => '',
                    ],
                    [
                        'col_0' => 'Wegbeschreibung',
                        'col_1' => '',
                    ],
                    [
                        'col_0' => 'Mitzubringen',
                        'col_1' => '',
                    ],
                    [
                        'col_0' => 'Zu beachten',
                        'col_1' => '',
                    ],
                    [
                        'col_0' => 'Auskunft unter',
                        'col_1' => '',
                    ],
                ];
            }//@todo refactor: move


            // Handle submitOnChange
            if ($attributesDatabase->submitOnChange) {
                $this->addSubmitOnChangeForInput('#ctrl_'.$field);
            }

            $form->addFormField($field, $data);
        }

        //@todo refactor: move
        if ('mm_participant' === $this->metaModel->getTableName()) {
            // Add validator for participant's dateOfBirth
            // It must not be changed afterwards
            $form->addValidator(
                'dateOfBirth',
                function ($value, $widget) {
                    if ($value != $this->item->get($widget->name)) {
                        if (Attendance::countByParticipant($this->item->get('id'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['ERR']['changedDateOfBirthAfterwards']);
                        }
                    }
                }
            );

            // Add validator for participant's agreement for photos
            // It must not be revoked afterwards
            $form->addValidator(
                'agreement_photo',
                function ($value, $widget) {
                    // Allow to grant but not to revoke
                    if ($value != $this->item->get($widget->name) && !$value) {
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

                    $redirectUrl = \Environment::get('request');
                    list($redirectUrl,) = explode('?', $redirectUrl, 2);

                    $modelId = ModelId::fromValues(
                        $this->metaModel->getTableName(),
                        $this->item->isVariant()
                            ? $this->item->get('vargroup')
                            : $this->item->get('id')
                    );
                    $redirectUrl .= '?act=edit&id='.$modelId->getSerialized();

                    \Controller::redirect($redirectUrl);
                }
            }

            // Check whether there is a jumpTo page
            if (null !== ($objJumpTo = $this->objModel->getRelated('jumpTo'))) {
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


    protected function newVariantLink()
    {
        $template = new \FrontendTemplate('ce_hyperlink');
        $template->class = 'create_new_variant';

        $template->href = str_replace(['edit', 'id'], ['create', 'vargroup'], \Environment::get('request'));

        $template->link = 'Termin erstellen';
        $template->linkTitle = specialchars(
            sprintf('Eine neue Variante zum Element "%s" erstellen', $this->item->get('name'))
        ); //@todo lang

        return $template->parse();
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
}
