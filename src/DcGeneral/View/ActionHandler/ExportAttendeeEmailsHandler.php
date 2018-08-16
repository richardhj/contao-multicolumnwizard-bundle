<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\DcGeneral\View\ActionHandler;


use Contao\Environment;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use Doctrine\DBAL\Connection;
use Symfony\Component\Translation\TranslatorInterface;

class ExportAttendeeEmailsHandler
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeDeterminator;

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ExportAttendeeEmailsHandler constructor.
     *
     * @param RequestScopeDeterminator $scopeDeterminator
     * @param Connection               $connection
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        RequestScopeDeterminator $scopeDeterminator,
        Connection $connection,
        TranslatorInterface $translator
    ) {
        $this->scopeDeterminator = $scopeDeterminator;
        $this->connection        = $connection;
        $this->translator        = $translator;
    }

    /**
     * @param ActionEvent $event
     */
    public function handleEvent(ActionEvent $event): void
    {
        $environment   = $event->getEnvironment();
        $inputProvider = $environment->getInputProvider();
        $definition    = $environment->getDataDefinition();

        if (!$this->scopeDeterminator->currentScopeIsBackend()
            || !$inputProvider->hasValue('export_attendee_emails')
            || 'mm_ferienpass' !== $definition->getName()
            || 'select' !== $event->getAction()->getName()) {
            return;
        }

        $offerIds = [];
        foreach ($inputProvider->getValue('models') as $model) {
            $modelId    = ModelId::fromSerialized($model);
            $offerIds[] = $modelId->getId();
        }

        $statement = $this->connection->createQueryBuilder()
            ->select(
                'DISTINCT IFNULL(NULLIF(p.email, \'\'), m.email) AS email, m.firstname AS firstname, m.lastname AS lastname'
            )
            ->from('tl_member', 'm')
            ->innerJoin('m', 'mm_participant', 'p', 'p.pmember=m.id')
            ->innerJoin('p', 'tl_ferienpass_attendance', 'a', 'a.participant=p.id')
            ->where('a.offer IN (:offers)')
            ->setParameter('offers', $offerIds, Connection::PARAM_STR_ARRAY)
            ->execute();

        $a = implode(', ', array_map([$this, 'parseEmail'], $statement->fetchAll(\PDO::FETCH_OBJ)));

        $response = sprintf(
            '<div id="tl_buttons">
	<a href="%1$s" class="header_back" title="%2$s" accesskey="b" onclick="Backend.getScrollOffset();">
		%3$s
	</a>
</div>

<h2 class="sub_headline">%4$s</h2>
<div class="tl_listing_container">
<p>Alle Kontakt-E-Mail-Adressen von Anmeldungen zu den Angeboten: ID %6$s</p>
<p>Die Anmeldungen haben alle möglichen Status (z.B. <em>angemeldet</em> oder <em>auf Warteliste</em>).</p>
<textarea class="tl_textarea">%5$s</textarea>
</div>
',
            UrlBuilder::fromUrl(Environment::get('request'))->unsetQueryParameter('act')->getUrl(),
            $this->translator->trans('MSC.backBTTitle', [], 'contao_default'),
            $this->translator->trans('MSC.backBT', [], 'contao_default'),
            'E-Mail-Adressen zum Kopieren',
            $a,
            implode(', ID ', $offerIds)
        );

        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * @param $row
     *
     * @return string
     */
    private function parseEmail($row): string
    {
        return sprintf(
            '%s %s <%s>',
            $row->firstname,
            $row->lastname,
            $row->email
        );
    }
}
