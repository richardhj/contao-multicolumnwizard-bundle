<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\BackendModule;


/**
 * Class BackendOverview
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 * @author  Isotope eCommerce Workgroup
 * @link    https://isotopeecommerce.org
 */
abstract class BackendOverview extends \BackendModule
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_overview';


    /**
     * Ferienpass modules
     * @var array
     */
    protected $modules = [];


    /**
     * Generate the module
     * @return string
     */
    public function generate()
    {
        $this->modules = [];

        // enable collapsing legends
        $session = \Session::getInstance()->get('fieldset_states');

        foreach ($this->getModules() as $k => $group) {
            list($k, $hide) = explode(':', $k, 2);

            if (isset($session['iso_be_overview_legend'][$k])) {
                $group['collapse'] = !$session['iso_be_overview_legend'][$k];
            } elseif ($hide == 'hide') {
                $group['collapse'] = true;
            }

            $this->modules[$k] = $group;
        }

        // Open module
        if (\Input::get('mod') != '') {
            return $this->getModule(\Input::get('mod'));
        } // Table set but module missing, fix the saveNcreate link
        elseif (\Input::get('table') != '') {
            foreach ($this->modules as $group) {
                if (isset($group['modules'])) {
                    foreach ($group['modules'] as $module => $config) {
                        if (is_array($config['tables']) && in_array(\Input::get('table'), $config['tables'])) {
                            \Controller::redirect($this->addToUrl('mod='.$module));
                        }
                    }
                }
            }
        }

        return parent::generate();
    }


    /**
     * Get modules
     * @return array
     */
    abstract protected function getModules();


    /**
     * Open a module and return it as HTML
     *
     * @param string
     *
     * @return mixed
     */
    protected function getModule($module)
    {
        $arrModule = [];
        $dc = null;

        foreach ($this->modules as $arrGroup) {
            if (!empty($arrGroup['modules']) && in_array($module, array_keys($arrGroup['modules']))) {
                $arrModule =& $arrGroup['modules'][$module];
            }
        }

        // Check whether the current user has access to the current module
        if (!$this->checkUserAccess($module)) {
            \System::log(
                'Module "'.$module.'" was not allowed for user "'.$this->User->username.'"',
                __METHOD__,
                TL_ERROR
            );
            \Controller::redirect('contao/main.php?act=error');
        }

        // Redirect the user to the specified page
        if ($arrModule['redirect'] != '') {
            \Controller::redirect($arrModule['redirect']);
        }

        $table = \Input::get('table');

        if ($table == '' && $arrModule['callback'] == '') {
            \Controller::redirect($this->addToUrl('table='.$arrModule['tables'][0]));
        }

        // Add module style sheet
        if (isset($arrModule['stylesheet'])) {
            $GLOBALS['TL_CSS'][] = $arrModule['stylesheet'];
        }

        // Add module javascript
        if (isset($arrModule['javascript'])) {
            $GLOBALS['TL_JAVASCRIPT'][] = $arrModule['javascript'];
        }

        // Redirect if the current table does not belong to the current module
        if ($table != '') {
            if (!in_array($table, (array)$arrModule['tables'])) {
                \System::log('Table "'.$table.'" is not allowed in module "'.$module.'"', __METHOD__, TL_ERROR);
                \Controller::redirect('contao/main.php?act=error');
            }

            // Load the language and DCA file
            \System::loadLanguageFile($table);
            $this->loadDataContainer($table);

            // Include all excluded fields which are allowed for the current user
            if ($GLOBALS['TL_DCA'][$table]['fields']) {
                foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $k => $v) {
                    if ($v['exclude']) {
                        /** @noinspection PhpParamsInspection */
                        if (\BackendUser::getInstance()->hasAccess($table.'::'.$k, 'alexf')) {
                            $GLOBALS['TL_DCA'][$table]['fields'][$k]['exclude'] = false;
                        }
                    }
                }
            }

            // Fabricate a new data container object
            if (!strlen($GLOBALS['TL_DCA'][$table]['config']['dataContainer'])) {
                \System::log('Missing data container for table "'.$table.'"', __METHOD__, TL_ERROR);
                trigger_error('Could not create a data container object', E_USER_ERROR);
            }

            $dataContainer = 'DC_'.$GLOBALS['TL_DCA'][$table]['config']['dataContainer'];
            $dc = new $dataContainer($table);
        }

        // AJAX request
        if ($_POST && \Environment::get('isAjaxRequest')) {
            $this->objAjax->executePostActions($dc);
        } // Call module callback
        elseif (class_exists($arrModule['callback'])) {

            /** @type \BackendModule $objCallback */
            $objCallback = new $arrModule['callback']($dc, $arrModule);

            return $objCallback->generate();
        } // Custom action (if key is not defined in config.php the default action will be called)
        elseif (\Input::get('key') && isset($arrModule[\Input::get('key')])) {
            $objCallback = new $arrModule[\Input::get('key')][0]();

            return $objCallback->$arrModule[\Input::get('key')][1]($dc, $table, $arrModule);
        } // Default action
        elseif (is_object($dc)) {
            $act = \Input::get('act');

            if (!strlen($act) || $act == 'paste' || $act == 'select') {
                $act = ($dc instanceof \listable) ? 'showAll' : 'edit';
            }

            switch ($act) {
                case 'delete':
                case 'show':
                case 'showAll':
                case 'undo':
                    if (!$dc instanceof \listable) {
                        \System::log('Data container '.$table.' is not listable', __METHOD__, TL_ERROR);
                        trigger_error('The current data container is not listable', E_USER_ERROR);
                    }
                    break;

                case 'create':
                case 'cut':
                case 'cutAll':
                case 'copy':
                case 'copyAll':
                case 'move':
                case 'edit':
                    if (!$dc instanceof \editable) {
                        \System::log('Data container '.$table.' is not editable', __METHOD__, TL_ERROR);
                        trigger_error('The current data container is not editable', E_USER_ERROR);
                    }
                    break;
            }

            return $dc->$act();
        }

        return null;
    }


    /**
     * Check if a user has access to the current module
     *
     * @param string $module
     *
     * @return boolean
     */
    abstract protected function checkUserAccess($module);


    /**
     * Generate the module
     */
    protected function compile()
    {
        $this->Template->modules = $this->modules;
    }
}
