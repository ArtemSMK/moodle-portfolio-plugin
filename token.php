<?php
define('AJAX_SCRIPT', true);
define('REQUIRE_CORRECT_ACCESS', true);
define('NO_MOODLE_COOKIES', true);

require(__DIR__.'/inc.php');

function block_exaport_load_service($service) {
    //extract($GLOBALS);
    //print_r($GLOBALS);exit;
    $CFG = $GLOBALS['CFG'];
    $OUTPUT = $GLOBALS['OUTPUT'];
    $DB = $GLOBALS['DB'];

    ob_start();
    try {
        $_POST['service'] = $service;
        require(__DIR__.'/../../login/token.php');
    } catch (moodle_exception $e) {
        if ($e->errorcode == 'servicenotavailable') {
            return null;
        } else {
            throw $e;
        }
    }
    $ret = ob_get_clean();

    $data = json_decode($ret);
    if ($data && $data->token) {
        return $data->token;
    } else {
        return null;
    }
}

// Allow CORS requests.
header('Access-Control-Allow-Origin: *');
echo $OUTPUT->header();

required_param('app', PARAM_TEXT);
required_param('app_version', PARAM_TEXT);

if (optional_param('testconnection', false, PARAM_BOOL)) {
    echo json_encode([
            'moodleName' => $DB->get_field('course', 'fullname', ['id' => 1]),
    ], JSON_PRETTY_PRINT);
    exit;
}

$exatokens = [];

$services = optional_param('services', '', PARAM_TEXT);
// Default services + .
$services = array_keys(
        ['moodle_mobile_app' => 1, 'exaportservices' => 1] + ($services ? array_flip(explode(',', $services)) : []));

foreach ($services as $service) {
    $token = block_exaport_load_service($service);
    $exatokens[] = [
            'service' => $service,
            'token' => $token,
    ];
}

require_once(__DIR__.'/externallib.php');

// Get login data.
$data = block_exaport_external::login();
// Add tokens.
$data['tokens'] = $exatokens;

// Clean output.
$data = external_api::clean_returnvalue(block_exaport_external::login_returns(), $data);

echo json_encode($data, JSON_PRETTY_PRINT);
