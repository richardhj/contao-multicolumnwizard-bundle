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

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Haste\Form\Form;
use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class HostPrivacyConsent
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class HostPrivacyConsent extends AbstractFrontendModuleController
{

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * HostPrivacyConsent constructor.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
        $markdown            = MarkdownExtra::defaultTransform($model->privacyStatementMarkdown);
        $template->statement = strip_tags($markdown, Config::get('allowedTags'));

        $user = FrontendUser::getInstance();

        $statement = $this->connection->createQueryBuilder()
            ->select('tstamp')
            ->from('tl_ferienpass_host_privacy_consent')
            ->where('member=:member')
            ->andWhere('type="sign"')
            ->setParameter('member', $user->id)
            ->setMaxResults(1)
            ->orderBy('tstamp', 'DESC')
            ->execute();

        $signed = $statement->rowCount() > 0;

        if ($signed) {
            $template->confirmation =
                'Sie haben diese Erklärung am ' . date(Config::get('dateFormat'), $statement->fetchColumn())
                . ' unterzeichnet.';
        } else {
            $form = new Form(
                'host_privacy_consent_signing', 'POST', function (Form $haste) {
                return Input::post('FORM_SUBMIT') === $haste->getFormId();
            }
            );

            $form->addFormField(
                'firstname',
                [
                    'label'     => 'Vorname',
                    'inputType' => 'text',
                    'eval'      => ['mandatory' => true, 'placeholder' => $user->firstname]
                ]
            );

            $form->addFormField(
                'lastname',
                [
                    'label'     => 'Nachname',
                    'inputType' => 'text',
                    'eval'      => ['mandatory' => true, 'placeholder' => $user->lastname]
                ]
            );

            $form->addFormField(
                'submit',
                [
                    'label'     => 'Unterzeichnen',
                    'inputType' => 'submit'
                ]
            );

            $form->addValidator(
                'firstname',
                function ($value) use ($user) {
                    if ($value !== $user->firstname) {
                        throw new \Exception('Der Vorname stimmt nicht überein.');
                    }

                    return $value;
                }
            );

            $form->addValidator(
                'lastname',
                function ($value) use ($user) {
                    if ($value !== $user->lastname) {
                        throw new \Exception('Der Nachname stimmt nicht überein.');
                    }

                    return $value;
                }
            );

            if ($form->validate()) {
                $this->connection->createQueryBuilder()
                    ->insert('tl_ferienpass_host_privacy_consent')
                    ->values(
                        [
                            'tstamp'         => '?',
                            'member'         => '?',
                            'type'           => '?',
                            'form_data'      => '?',
                            'statement_hash' => '?'
                        ]
                    )
                    ->setParameters(
                        [
                            time(),
                            $user->id,
                            'sign',
                            json_encode($form->fetchAll()),
                            sha1($markdown)
                        ]
                    )
                    ->execute();

                throw new RedirectResponseException($request->getRequestUri());
            }

            $template->signingForm = $form->generate();
        }

        $template->signed = $signed;

        return Response::create($template->parse());
    }
}
