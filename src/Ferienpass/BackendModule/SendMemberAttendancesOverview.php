<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\BackendModule;

use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use Haste\Util\Format;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;


/**
 * Class SendMemberAttendancesOverview
 * @package Ferienpass\BackendModule
 */
class SendMemberAttendancesOverview extends \BackendModule
{

    protected $strTemplate = 'dcbe_general_edit';


    /**
     * Generate the module
     * @return string
     */
    public function generate()
    {
        if (!\BackendUser::getInstance()->isAdmin) {
            return sprintf('<p class="tl_gerror">%s</p>', 'keine Berechtigung');
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $output = '';
        $formSubmit = 'send_member_attendances_overview';

        if ($formSubmit === \Input::post('FORM_SUBMIT')) {

        }

        $buttons[] = sprintf(
            '<input type="submit" name="start" id="start" class="tl_submit" accesskey="s" value="%s" />',
            'Benachrichtigungen sofort verschicken'
        );

        $this->Template->subHeadline = 'Teilnahmebestätigung an Eltern verschicken';
        $this->Template->table = $formSubmit;
        $this->Template->editButtons = $buttons;
        $this->Template->fieldsets = [
            [
                'class'   => 'tl_box',
                'palette' => $output,
            ],
        ];
    }
}
