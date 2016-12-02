<?php

// Initialize the system
use Ferienpass\Model\DataProcessing;


define('TL_MODE', 'FE');
require '../../../../initialize.php';

header('Content-Type: text/plain');

// Check get parameter
// Necessary for enabling the webhook via dropbox' app console
if (($challenge = \Input::get('challenge'))) {
    die($challenge);
}

$raw_data = file_get_contents('php://input');
$json = json_decode($raw_data);
$app_secret = \Config::get('dropbox_ferienpass_appSecret');

if ($_SERVER['HTTP_X_DROPBOX_SIGNATURE'] !== hash_hmac('sha256', $raw_data, $app_secret)) {
    header('HTTP/1.0 403 Forbidden');
    die('Invalid request');
}

// Return a response to the client before processing
// Dropbox wants a response quickly
header('Connection: close');
ob_start();
header('HTTP/1.0 200 OK');
ob_end_flush();
flush();

// Fetch all dropbox data processings with the submitted UIDs
/** @type \Model\Collection $processings */
$processings = DataProcessing::findBy
(
    [
        'filesystem=\'dropbox\'',
        'sync=1',
        sprintf(
            'dropbox_uid IN(%s)',
            implode(',', array_map('intval', $json->delta->users))
        ),
    ],
    null
);

// Walk each data processing
while (null !== $processings && $processings->next()) {
    /** @var DataProcessing $processings ->current() */
    $processings->current()->syncFromRemoteDropbox();
}
