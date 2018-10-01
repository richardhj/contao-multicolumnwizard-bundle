<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserAttendancesICalLink
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserAttendancesICalLink extends AbstractFrontendModuleController
{
    /**
     * A secret.
     *
     * @var string
     */
    private $secret;

    /**
     * UserAttendancesICalLink constructor.
     *
     * @param string $secret A secret.
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Returns the response.
     *
     * @param Template|object $template The template.
     * @param ModuleModel     $model    The module model.
     * @param Request         $request  The request.
     *
     * @return Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $template->link = $this->getLink($request->getSchemeAndHttpHost());

        return Response::create($template->parse());
    }

    private function getLink(string $base): string
    {
        $member = FrontendUser::getInstance();
        $token  = hash('ripemd128', implode('', [$member->id, 'ics', $this->secret]));
        $token  = substr($token, 0, 8);

        return $base . '/share/anmeldungen-ferienpass-' . $member->id . '-' . $token . '.ics';
    }
}
