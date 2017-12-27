<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Widget;

use Contao\FormHidden;
use Contao\TextField;
use Contao\Widget;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Stevenmaguire\OAuth2\Client\Provider\Dropbox as DropboxOAuthProvider;
use Stevenmaguire\OAuth2\Client\Provider\DropboxResourceOwner;


/**
 * Class RequestAccessToken
 *
 * @package Richardhj\ContaoFerienpassBundle\Widget
 */
class RequestAccessToken extends Widget
{

    /**
     * Submit user input
     *
     * @var boolean
     */
    protected $blnSubmitInput = true;


    /**
     * Add a for attribute
     *
     * @var boolean
     */
    protected $blnForAttribute = true;


    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';


    /**
     * generate widget
     *
     * @return string
     */
    public function generate()
    {
        global $container;

        $provider = new DropboxOAuthProvider(
            [
                'clientId'     => $container['ferienpass.dropbox.appId'],
                'clientSecret' => $container['ferienpass.dropbox.appSecret'],
            ]
        );

        /** @var DcCompat $dataContainer */
        $dataContainer = $this->dataContainer;
        $model         = $dataContainer->getModel();

        if ('create' === $dataContainer->getEnvironment()->getInputProvider()->getParameter('act')) {
            return sprintf(
                '<div class="tl_info" style="margin-bottom: 7px;">%s</div>',
                'Bitte speichern und wiederkommen'
            );
        }

        if (!$this->varValue && null === $model->getProperty('dropbox_uid')) {
            $field       = new TextField($this->arrConfiguration);
            $field->name = $this->name;
            $field->id   = $this->id;

            return sprintf(
                '<div class="tl_info" style="margin-bottom: 7px;">%s%s</div>',
                sprintf(
                    '<a href="%2$s" target="_blank">%1$s</a>',
                    'Bitte klicken Sie diesen Link und geben dann den generierten Token in das untenstehende Feld ein.',
                    $provider->getAuthorizationUrl()
                ),
                $field->generate()
            );
        } // Convert given code to an access token
        elseif (null === $model->getProperty('dropbox_uid')) {
            # At this point the value is an authorization code, not the access token

            try {
                $objAccessToken = $provider->getAccessToken(
                    'authorization_code',
                    [
                        'code' => $this->varValue
                    ]
                );

                $model->setProperty('dropbox_access_token', $objAccessToken->getToken());
                $model->setProperty('dropbox_uid', $objAccessToken->getResourceOwnerId());

                $dataContainer
                    ->getEnvironment()
                    ->getDataProvider($model->getProviderName())
                    ->save($model);

                \Controller::reload();

            } catch (IdentityProviderException $e) {
                $model->setProperty('dropbox_access_token', null);
                $model->setProperty('dropbox_uid', null);
                $dataContainer
                    ->getEnvironment()
                    ->getDataProvider($model->getProviderName())
                    ->save($model);

                return sprintf(
                    '<div class="tl_gerror" style="margin-bottom: 7px;">%s</div>',
                    $e->getMessage()
                );
            }
        }

        try {
            /** @var DropboxResourceOwner $user */
            $user = $provider->getResourceOwner(
                new AccessToken(
                    [
                        'access_token' => $this->varValue
                    ]
                )
            );

        } catch (IdentityProviderException $e) {
            $model->setProperty('dropbox_access_token', null);
            $model->setProperty('dropbox_uid', null);
            $dataContainer
                ->getEnvironment()
                ->getDataProvider($model->getProviderName())
                ->save($model);

            return sprintf(
                '<div class="tl_gerror" style="margin-bottom: 7px;">%s</div>',
                $e->getMessage()
            );
        }

        $fieldToken        = new FormHidden($this->arrConfiguration);
        $fieldToken->name  = $this->name;
        $fieldToken->value = $this->varValue;

        return sprintf(
            '<div class="tl_confirm" style="margin-bottom: 7px;">%s%s</div>',
            $fieldToken->generate(),
            sprintf(
                'Mit der Dropbox von %s verknüpft',
                $user->getName()
            )
        );
    }
}
