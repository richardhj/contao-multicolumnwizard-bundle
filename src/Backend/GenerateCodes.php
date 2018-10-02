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

namespace Richardhj\ContaoFerienpassBundle\Backend;


use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Class GenerateCodes
 *
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 */
class GenerateCodes
{

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * GenerateCodes constructor.
     */
    public function __construct()
    {
        $this->connection = System::getContainer()->get('database_connection');
    }


    /**
     * Generate a product label and return it as HTML string
     *
     * @param $row
     * @param $label
     * @param $dc
     * @param $args
     *
     * @return array
     */
    public function generateLabel($row, $label, $dc, $args): array
    {
        if ($row['activated'] > 0) {
            $args[0] = sprintf('<span style="text-decoration: line-through;">%s</span>', $args[0]);
        }

        return $args;
    }

    /**
     * Generated the module.
     *
     * @return string
     * @throws \Exception
     */
    public function generate(): string
    {
        $success = false;
        if ('generate_codes' === \Input::post('FORM_SUBMIT')) {
            $quantity    = \Input::post('quantity');
            $attributeId = $this->connection->createQueryBuilder()
                ->select('id')
                ->from('tl_metamodel_attribute')
                ->where("type='ferienpass_code'")
                ->execute()
                ->fetchColumn(0);

            while ($quantity--) {
                $code = $this->generateCode();
                try {
                    $this->persistCode($attributeId, $code);
                } catch (UniqueConstraintViolationException $exception) {
                    $quantity++;
                }
            }

            $success = true;
        }

        $return = '<div id="tl_buttons">
<a href="' . ampersand(str_replace('&key=generate', '', \Environment::get('request'))) . '" class="header_back" title="'
                  . specialchars($GLOBALS['TL_LANG']['MSC']['backBT']) . '">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>

<h2 class="sub_headline">Ferienpass-Zugangscodes generieren</h2>


' . ($success ? '<p class="tl_confirm">Die Codes wurden generiert und in der Datenbank gespeichert.</p>' : '') . '

<form action="' . ampersand(\Environment::get('request'), true) . '" class="tl_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="generate_codes">
<input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '">

<fieldset class="tl_tbox block">
<label>Anzahl Codes</label>
<input type="number" name="quantity" value="" placeholder="0">
</fieldset>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="' . specialchars('Generieren') . '">
</div>

</div>
</form>';

        return $return;
    }

    /**
     * Generate one code.
     *
     * @param int $length
     *
     * @return string
     *
     * @throws \Exception
     */
    private function generateCode(int $length = 6): string
    {
        $code = '';
        while ($length--) {
            $code .= random_int(0, 9);
        }

        return $code;
    }

    /**
     * Persist one code in the database.
     *
     * @param string $attributeId The id of the code attribute.
     * @param string $code        The code.
     *
     * @throws UniqueConstraintViolationException If code is not unique in database.
     */
    private function persistCode($attributeId, string $code): void
    {
        $this->connection->createQueryBuilder()
            ->insert('tl_ferienpass_code')
            ->values(
                [
                    'tstamp' => '?',
                    'att_id' => '?',
                    'code'   => '?'
                ]
            )
            ->setParameter(0, time())
            ->setParameter(1, $attributeId)
            ->setParameter(2, $code)
            ->execute();
    }
}
