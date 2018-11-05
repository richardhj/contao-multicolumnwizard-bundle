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

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class RegisterBackendNavigationListener
 *
 * @package Richardhj\ContaoFerienpassBundle\HookListener
 */
class RegisterBackendNavigationListener
{
    /**
     * The request stack.
     *
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * The URL generator.
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * The translator in use.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Create a new instance.
     *
     * @param TranslatorInterface   $translator   The translator.
     * @param RequestStack          $requestStack The request stack.
     * @param UrlGeneratorInterface $urlGenerator The url generator.
     */
    public function __construct(
        TranslatorInterface $translator,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->translator   = $translator;
    }

    /**
     * Hook function
     *
     * @param array $modules The backend navigation.
     *
     * @return mixed
     */
    public function onGetUserNavigation($modules)
    {
        $this->addMenu(
            $modules,
            'ferienpass',
            'erase_personal_data',
            [
                'label' => $this->translator->trans('MOD.ferienpass_erase_personal_data.0', [], 'contao_modules'),
                'title' => $this->translator->trans('MOD.ferienpass_erase_personal_data.1', [], 'contao_modules'),
                'route' => 'richardhj.ferienpass.backend.erase_personal_data',
            ]
        );

        $this->addMenu(
            $modules,
            'ferienpass',
            'send_attendances_overview',
            [
                'label' => $this->translator->trans('MOD.ferienpass_send_attendances_overview.0', [], 'contao_modules'),
                'title' => $this->translator->trans('MOD.ferienpass_send_attendances_overview.1', [], 'contao_modules'),
                'route' => 'richardhj.ferienpass.backend.send_attendances_overview',
            ]
        );

        return $modules;
    }

    /**
     * Add a module to the modules list.
     *
     * @param array  $modules The modules list.
     * @param string $section The section to add to.
     * @param string $name    The name of the module.
     * @param array  $module  The module.
     *
     * @return void
     */
    private function addMenu(&$modules, $section, $name, $module): void
    {
        $active = $this->isActive($module['route']);
        $class  = 'navigation ' . $name;
        if (isset($module['class'])) {
            $class .= ' ' . $module['class'];
        }
        if ($active) {
            $class .= ' active';
        }

        $modules[$section]['modules'][$name] = [
            'label'    => $module['label'],
            'title'    => $module['title'],
            'class'    => $class,
            'isActive' => $active,
            'href'     => $this->urlGenerator->generate($module['route'], [])
        ];
    }

    /**
     * Determine if is active.
     *
     * @param string $route The route name.
     *
     * @return bool
     */
    private function isActive($route): bool
    {
        return !('/contao' === $this->requestStack->getCurrentRequest()->getPathInfo()
                 || !($this->requestStack->getCurrentRequest()->attributes->get('_route') === $route));
    }
}
