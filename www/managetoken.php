<?php

if (session_status() != PHP_SESSION_ACTIVE) {
    session_cache_limiter('nocache');
}
$globalConfig = \SimpleSAML\Configuration::getInstance();

\SimpleSAML\Logger::info('FIDO2 - Accessing WebAuthn token management');

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new \SimpleSAML\Error\BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];
$state = \SimpleSAML\Auth\State::loadState($id, 'webauthn:request');

if ($state['FIDO2AuthSuccessful'] === false) {
    throw new Exception("Attempt to access the token management page unauthenticated.");
}
switch ($_POST['submit']) {
    case "NEVERMIND":
        \SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
        break;
    case "DELETE":
        if ($state['FIDO2AuthSuccessful'] == $_POST['credId']) {
            throw new Exception("Attempt to delete the currently used credential despite UI preventing this.");
        }
        $store = $state['webauthn:store'];
        $store->deleteTokenData($_POST['credId']);
        \SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
        break;
    default:
        throw new Exception("Unknown submit button state.");
}

