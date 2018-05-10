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

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use Contao\FrontendUser;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Symfony\Component\HttpFoundation\RequestStack;

class InsertTagsListener
{

    /**
     * @var FirstCome
     */
    private $firstComeApplicationSystem;

    /**
     * @var string
     */
    private $secret;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * InsertTagsListener constructor.
     *
     * @param FirstCome    $firstComeApplicationSystem The application system FirstCome.
     * @param RequestStack $requestStack               The request stack.
     * @param string       $secret                     The secret.
     */
    public function __construct(FirstCome $firstComeApplicationSystem, RequestStack $requestStack, string $secret)
    {
        $this->firstComeApplicationSystem = $firstComeApplicationSystem;
        $this->requestStack               = $requestStack;
        $this->secret                     = $secret;
    }

    /**
     * @param string $tag
     *
     * @return string|false
     */
    public function onReplaceInsertTags($tag)
    {
        $elements = trimsplit('::', $tag);

        if ('ferienpass' === $elements[0]) {
            switch ($elements[1]) {
                case 'max_applications_per_day':
                    return $this->firstComeApplicationSystem->getMaxApplicationsPerDay();
                    break;

                case 'ics_share_url':
                    $member = FrontendUser::getInstance();
                    $token  = hash('ripemd128', implode('', [$member->id, 'ics', $this->secret]));
                    $token  = substr($token, 0, 8);

                    $request = $this->requestStack->getCurrentRequest();
                    if (null === $request) {
                        return '';
                    }

                    return $request->getSchemeAndHttpHost()
                           .'/share/anmeldungen-ferienpass-'.$member->id.'-'.$token.'.ics';
                    break;
            }
        }

        return false;
    }
}
