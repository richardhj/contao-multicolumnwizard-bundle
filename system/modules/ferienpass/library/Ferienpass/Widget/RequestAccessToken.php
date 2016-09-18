<?php

namespace Ferienpass\Widget;


use Contao\FormHidden;
use Contao\TextField;
use Contao\Widget;
use Ferienpass\Model\DataProcessing;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Pixelfear\OAuth2\Client\Provider\Dropbox;
use Pixelfear\OAuth2\Client\Provider\DropboxResourceOwner;


class RequestAccessToken extends Widget
{

    /**
     * Submit user input
     * @var boolean
     */
    protected $blnSubmitInput = true;


    /**
     * Add a for attribute
     * @var boolean
     */
    protected $blnForAttribute = true;


    /**
     * Template
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
        $provider = new Dropbox(
            [
                'clientId'     => \Config::get('dropbox_ferienpass_appId'),
                'clientSecret' => \Config::get('dropbox_ferienpass_appSecret'),
            ]
        );

        $objModel = DataProcessing::findByPk($this->currentRecord);

        if (!$this->varValue && !$objModel->dropbox_uid) {
            $field = new TextField($this->arrConfiguration);
            $field->name = $this->name;
            $field->id = $this->id;

            return sprintf
            (
                '<div class="tl_info" style="margin-bottom: 7px;">%s%s</div>',
                sprintf
                (
                    '<a href="%2$s" target="_blank">%1$s</a>',
                    'Bitte klicken Sie diesen Link und geben dann den generierten Token in das untenstehende Feld ein.',
                    $provider->getAuthorizationUrl()
                ),
                $field->generate()
            );
        } // Convert given code to an access token
        elseif (!$objModel->dropbox_uid) {
            # At this point the value is an authorization code, not the access token

            try {
                $objAccessToken = $provider->getAccessToken(
                    'authorization_code',
                    [
                        'code'         => $this->varValue,
                        'redirect_uri' => [] // only necessary with legacy oauth2-client
                    ]
                );
//@todo "Creating default object from empty value" when creating a DataProcessing in backend
                $objModel->dropbox_access_token = $objAccessToken->getToken();
                $objModel->dropbox_uid = $objAccessToken->getResourceOwnerId();
                $objModel->save();
                \Controller::reload();
            } catch (IdentityProviderException $e) {
                return sprintf
                (
                    '<div class="tl_gerror" style="margin-bottom: 7px;">%s</div>',
                    $e->getMessage()
                );
            }
        }

        try {
            /** @var DropboxResourceOwner $user */
            $user = $provider->getResourceOwner(new AccessToken(['access_token' => $this->varValue]));

        } catch (IdentityProviderException $e) {
            $objModel->dropbox_access_token = '';
            $objModel->dropbox_uid = '';
            $objModel->save();

            return sprintf
            (
                '<div class="tl_gerror" style="margin-bottom: 7px;">%s</div>',
                $e->getMessage()
            );
        }

        $field_token = new FormHidden($this->arrConfiguration);
        $field_token->name = $this->name;
        $field_token->value = $this->varValue;

        return sprintf
        (
            '<div class="tl_confirm" style="margin-bottom: 7px;">%s%s</div>',
            $field_token->generate(),
            sprintf
            (
                'Mit der Dropbox von %s verknüpft',
                $user->getName()
            )
        );
    }
}
