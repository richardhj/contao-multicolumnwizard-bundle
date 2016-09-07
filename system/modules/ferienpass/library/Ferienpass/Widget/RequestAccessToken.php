<?php

namespace Ferienpass\Widget;


use Contao\FormHidden;
use Contao\TextField;
use Contao\Widget;
use Ferienpass\Model\DataProcessing;
use League\OAuth2\Client\Token\AccessToken;
use Pixelfear\OAuth2\Client\Provider\Dropbox;

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
		/*
		 *
		 * Code uses legacy oauth2-client since it runs with php 5.4
		 * Code snippets for the latest oauth2-client is commented
		 * Reactivate if server runs with php 5.5 at least
		 *
		 */


		$provider = new Dropbox([
			'clientId'     => \Config::get('dropbox_ferienpass_appId'),
			'clientSecret' => \Config::get('dropbox_ferienpass_appSecret')
		]);

		$objModel = DataProcessing::findByPk($this->currentRecord);

		if (!$this->varValue && !$objModel->dropbox_uid)
		{
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
					str_replace('&redirect_uri=', '', $provider->getAuthorizationUrl()) // str_replace only necessary with legacy oauth2-client
				),
				$field->generate()
			);
		}
		// Convert given code to an access token
		elseif (!$objModel->dropbox_uid)
		{
			# At this point the value is an authorization code, not the access token

			try
			{
				$objAccessToken = $provider->getAccessToken('authorization_code', [
					'code' => $this->varValue,
					'redirect_uri' => [] // only necessary with legacy oauth2-client
				]);
//@todo "Creating default object from empty value" when creating a DataProcessing in backend
//				$objModel->dropbox_access_token = $objAccessToken->getToken();
//				$objModel->dropbox_uid = $objAccessToken->getResourceOwnerId();
				$objModel->dropbox_access_token = $objAccessToken->accessToken;
				$objModel->dropbox_uid = $objAccessToken->uid;
				$objModel->save();
				\Controller::reload();
			} catch (\Exception/*IdentityProviderException*/
			$e)
			{
				return sprintf
				(
					'<div class="tl_gerror" style="margin-bottom: 7px;">%s</div>',
					$e->getMessage()
				);
			}
		}

		try
		{
//			/** @var DropboxResourceOwner $user */
//			$user = $provider->getResourceOwner(new AccessToken(['access_token' => $this->varValue]));

			/** @var \League\OAuth2\Client\Entity\User $user */
			$user = $provider->getUserDetails(new AccessToken(['access_token' => $this->varValue]));

		} catch (\Exception/*IdentityProviderException*/
		$e)
		{
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
//				$user->getName()
				$user->name
			)
		);
	}
}
