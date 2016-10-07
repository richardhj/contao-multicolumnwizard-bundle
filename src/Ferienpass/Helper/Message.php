<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Helper;

use Contao\Controller;


/**
 * Class Message
 * @package Ferienpass\Helper
 */
class Message extends Controller
{

    /**
     * Add an error message
     *
     * @param string $message The error message
     */
    public static function addError($message)
    {
        static::add($message, 'FP_ERROR');
    }


    /**
     * Add a confirmation message
     *
     * @param string $message The confirmation message
     */
    public static function addConfirmation($message)
    {
        static::add($message, 'FP_CONFIRMATION');
    }


    /**
     * Add a warning message
     *
     * @param string $message The warning message
     */
    public static function addWarning($message)
    {
        static::add($message, 'FP_WARNING');
    }


    /**
     * Add an info message
     *
     * @param string $message The info message
     */
    public static function addInformation($message)
    {
        static::add($message, 'FP_INFORMATION');
    }


    /**
     * Add a preformatted message
     *
     * @param string $message The preformatted message
     */
    public static function addRaw($message)
    {
        static::add($message, 'FP_RAW');
    }


    /**
     * Add a message
     *
     * @param string $message The message text
     * @param string $type    The message type
     *
     * @throws \Exception If $strType is not a valid message type
     */
    public static function add($message, $type)
    {
        if ('' === $message) {
            return;
        }

        if (!in_array($type, static::getTypes())) {
            throw new \Exception("Invalid message type $type");
        }

        if (!is_array($_SESSION[$type])) {
            $_SESSION[$type] = [];
        }

        $_SESSION[$type][] = $message;
    }


    /**
     * Return all messages as HTML
     *
     * @param boolean $noWrapper If true, there will be no wrapping DIV
     *
     * @return string The messages HTML markup
     */
    public static function generate($noWrapper = false)
    {
        $return = '';

        // Regular messages
        foreach (static::getTypes() as $type) {
            if (!is_array($_SESSION[$type])) {
                continue;
            }

            $class = strtolower(substr($type, 3)); // Remove prefix
            $_SESSION[$type] = array_unique($_SESSION[$type]);

            foreach ($_SESSION[$type] as $message) {
                if ('TL_RAW' === $type) {
                    $return .= $message;
                } else {
                    $return .= sprintf('<p class="%s">%s</p>%s', $class, $message, "\n");
                }
            }

            if (!$_POST) {
                $_SESSION[$type] = [];
            }
        }

        $return = trim($return);

        // Wrapping container
        if (!$noWrapper && $return != '') {
            $return = sprintf('<div class="messages">%s%s%s</div>', "\n", $return, "\n");
        }

        return $return;
    }


    /**
     * Reset the message system
     */
    public static function reset()
    {
        foreach (static::getTypes() as $type) {
            $_SESSION[$type] = [];
        }
    }


    /**
     * Return all available message types
     *
     * @return array An array of message types
     */
    public static function getTypes()
    {
        return ['FP_ERROR', 'FP_CONFIRMATION', 'FP_WARNING', 'FP_INFORMATION', 'FP_RAW'];
    }
}
