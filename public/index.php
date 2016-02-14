<?php
$loader = require '../vendor/autoload.php';
//require '../src/Machine.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Usaged\Machine;
use Usaged\Database;

// Helpers

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

// Prepare app
$app = new \Slim\Slim(array(
    'templates.path' => '../templates',
));

// Create monolog logger and store logger in container as singleton 
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton('log', function () {
    $log = new \Monolog\Logger('slim-skeleton');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log', \Monolog\Logger::DEBUG));
    return $log;
});

// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

// Define routes
$app->get('/', function () use ($app) {
    // Sample log message
    $app->log->info("Slim-Skeleton '/' route");
    // Render index view
    $app->render('index.html');
});

$app->get('/machines', function () use ($app) {
    $db = new Database;
    $machine = new Machine($db);
    $app->log->debug('Machines : ' . var_export($machine->getAll(),true));
    $app->render('machines.html',array('machines'=>$machine->getAll()));/*array('machines' => array(
        array('machinename' => 'Laser', 'uuid' => '111'),
        array('machinename' => 'CNC', 'uuid' => '222'),
    )));*/
});

/**
 * Machine Registration
 * url - /machine/create
 * method - POST
 * params - machinename
 */
$app->post('/machine/create', function () use ($app) {
    verifyRequiredParams(array('machinename'));
    $db = new Database;
    $machine = new Machine($db);
    $app->log->debug('Machines : ' . var_export($machine->getAll(),true));
    $status = 200;
    try {
        $response = $machine->createMachine($app->request->post('machinename'));
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        $status = 500;
    }
    $app->status($status);
    $app->contentType('appliaction/json');
    echo json_encode($response);
});

// Run app
$app->run();
