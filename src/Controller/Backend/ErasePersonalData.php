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

namespace Richardhj\ContaoFerienpassBundle\Controller\Backend;

use Contao\CheckBox;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Image;
use Contao\MemberGroupModel;
use Contao\MemberModel;
use Contao\Message;
use Contao\Widget;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ContaoBackendViewTemplate;
use Doctrine\DBAL\Connection;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IFactory;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ErasePersonalData extends Controller
{
    /**
     * The twig engine.
     *
     * @var EngineInterface
     */
    private $templating;

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * The MetaModels factory.
     *
     * @var IFactory
     */
    private $factory;

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;


    /**
     * Create a new instance.
     *
     * @param EngineInterface     $templating The twig engine.
     * @param TranslatorInterface $translator The translator.
     * @param IFactory            $factory    The MetaModels factory.
     * @param Connection          $connection The database connection.
     */
    public function __construct(
        EngineInterface $templating,
        TranslatorInterface $translator,
        IFactory $factory,
        Connection $connection
    ) {
        $this->templating = $templating;
        $this->translator = $translator;
        $this->factory    = $factory;
        $this->connection = $connection;
    }

    /**
     * Invoke this.
     *
     * @param Request $request The request.
     *
     * @return Response The template data.
     */
    public function __invoke(Request $request)
    {
        return new Response(
            $this->templating->render(
                'RichardhjContaoFerienpassBundle::Backend/be_erase_personal_data.html.twig',
                [
                    'stylesheets'  => [
                    ],
                    'headline'     => $this->translator->trans(
                        'MOD.ferienpass_erase_personal_data.0',
                        [],
                        'contao_modules'
                    ),
                    'sub_headline' => $this->translator->trans(
                        'MSC.ferienpass_erase_personal_data.main_headline',
                        [],
                        'contao_default'
                    ),
                    'form'         => $this->compile($request),
                ]
            )
        );
    }

    /**
     * @param Request $request The request.
     *
     * @return string
     */
    protected function compile(Request $request): string
    {
        $output = '';

        $formSubmit  = 'tl_erase_personal_data';
        $memberGroup = MemberGroupModel::findByPk('2'); // @todo
        if (null === $memberGroup) {
            throw new \LogicException('Member group not found: ID 2');
        }

        $members               = MemberModel::findBy(['groups=?'], [serialize([$memberGroup->id])]);
        $attendances           = Attendance::findAll();
        $participantsMetaModel = $this->factory->getMetaModel('mm_participant');
        if (null === $participantsMetaModel) {
            throw new \LogicException('MetaModel not found: mm_participant');
        }

        $participantsFilter     = $participantsMetaModel->getEmptyFilter();
        $participantsFilterRule = (null === $members)
            ? new StaticIdList([])
            : new SimpleQuery(
                sprintf(
                    'SELECT id FROM %1$s WHERE %2$s IN (%3$s) OR %2$s=0',
                    $participantsMetaModel->getTableName(),
                    'pmember',
                    implode(',', $members->fetchEach('id'))
                )
            );
        $participantsFilter->addFilterRule($participantsFilterRule);
        $participants = $participantsMetaModel->findByFilter($participantsFilter);

        /** @var CheckBox|Widget $checkboxConfirm */
        $checkboxConfirm = new CheckBox(null);

        $checkboxConfirm->id        = $checkboxConfirm->name = 'confirm';
        $checkboxConfirm->mandatory = true;
        $checkboxConfirm->options   = [
            [
                'value' => 1,
                'label' => 'Ich bin mir bewusst, dass die Daten unwiderruflich gelöscht werden.',
            ],
        ];

        /** @var CheckBox|Widget $checkboxPreserveAttendances */
        $checkboxPreserveAttendances          = new CheckBox(null);
        $checkboxPreserveAttendances->id      = $checkboxPreserveAttendances->name = 'preserveAttendances';
        $checkboxPreserveAttendances->options = [
            [
                'value' => 1,
                'label' => 'Anmeldungen nicht löschen (für Statistiken / enthalten keine pers. Daten)',
            ],
        ];

        if ($formSubmit === $request->request->get('FORM_SUBMIT')) {
            $checkboxConfirm->validate();
            $checkboxPreserveAttendances->validate();

            if (!$checkboxConfirm->hasErrors()) {
                // Truncate attendances
                if ('1' !== $checkboxPreserveAttendances->value && null !== $attendances) {
                    $ids = $attendances->fetchEach('id');
                    $this->connection->createQueryBuilder()
                        ->delete(Attendance::getTable())
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                        ->execute();
                }

                // Truncate participants
                if (0 !== $participants->getCount()) {
                    $ids = array_map(
                        function (IItem $item) {
                            return $item->get('id');
                        },
                        iterator_to_array($participants)
                    );

                    $this->connection->createQueryBuilder()
                        ->delete($participantsMetaModel->getTableName())
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                        ->execute();
                }

                // Truncate members
                if (null !== $members) {
                    $ids = $members->fetchEach('id');
                    $this->connection->createQueryBuilder()
                        ->delete('tl_member')
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                        ->execute();
                }

                Message::addConfirmation('Löschung wurde erfolgreich ausgeführt');
                throw new RedirectResponseException($request->getUri());
            }
        }

        $buttonTemplate = new ContaoBackendViewTemplate('dc_general_button');
        $buttonTemplate->setData(
            [
                'label'      => 'Daten unwiderruflich löschen',
                'attributes' => [
                    'type'      => 'submit',
                    'name'      => 'start',
                    'id'        => 'start',
                    'class'     => 'tl_submit',
                    'accesskey' => 's',
                ],
            ]
        );
        $buttons['save'] = $buttonTemplate->parse();

        $submitButtons = ['toggleIcon' => Image::getHtml('navcol.svg')];
        $editButtons   = $buttons;
        if (array_key_exists('save', $editButtons)) {
            $submitButtons['save'] = $editButtons['save'];
            unset($editButtons['save']);
        }

        if (0 < \count($editButtons)) {
            $submitButtons['buttonGroup'] = $editButtons;
        }

        $submitButtonTemplate = new ContaoBackendViewTemplate('dc_general_submit_button');
        $submitButtonTemplate->setData($submitButtons);

        /** @noinspection PhpUndefinedMethodInspection */
        $output .= sprintf(
            <<<'HTML'
<table>
    <tbody>
    <tr>
        <td>Die Mitglieder, die sich <em>ausschließlich</em> in der Mitgleidergruppe "%7$s" befinden</td>
        <td>%1$s</td>
        <td>%4$s</td>
    </tr>
    <tr>
        <td>Deren Teilnehmer</td>
        <td>%2$s</td>
        <td>%5$s</td>
    </tr>
    <tr>
        <td>Alle Anmeldungen</td>
        <td>%3$s</td>
        <td>%6$s</td>
    </tr>
    </tbody>
    <thead>
    
    <tr>
        <th>Beschreibung</th>
        <th>Tabelle</th>
        <th>Anzahl zu löschender Datensätze</th>
    </tr>
</thead>
</table>
HTML
            ,
            MemberModel::getTable(),
            $participantsMetaModel->getTableName(),
            Attendance::getTable(),
            (null !== $members) ? $members->count() : 0,
            $participants->getCount(),
            (null !== $attendances) ? $attendances->count() : 0,
            $memberGroup->name
        );

        $output .= $checkboxPreserveAttendances->generateWithError();
        $output .= $checkboxConfirm->generateWithError();

        return $output;
    }
}
