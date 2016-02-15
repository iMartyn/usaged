<?php
$loader = require '../vendor/autoload.php';
//require '../src/Machine.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Usaged\Database;
use Usaged\Machine;
use Usaged\Inductee;
use Usaged\InducteeMachine;

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

$app->get('/inductees', function () use ($app) {
    $db = new Database;
    $members = new Inductee($db);
    $app->log->debug('Members who can use machines : ' . var_export($members->getAll(),true));
    $app->render('inductees.html',array('inductees'=>$members->getAll()));
});

/**
 * Inductee Registration
 * url - /inductee/create
 * method - POST
 * params - machinename
 */
$app->post('/inductee/create', function () use ($app) {
    verifyRequiredParams(array('membername','cardid'));
    $db = new Database;
    $inductee = new Inductee($db);
    $app->log->debug('Members who can use machines : ' . var_export($inductee->getAll(),true));
    $status = 200;
    try {
        $response = $inductee->createInductee($app->request->post('membername'),$app->request->post('cardid'));
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        $status = 500;
    }
    $app->status($status);
    $app->contentType('appliaction/json');
    echo json_encode($response);
});

$app->get('/canuse/:name/:cardid', function ($name,$cardid) use ($app) {
    $db = new Database;
    $inductee = new Inductee($db);
    $machine = new Machine($db);
    $machineid = $machine->getByName($name)['uid'];
    if (!$machineid) {
        $app->response->setStatus(500);
        echo 'false';
    } else {
        $app->log->debug('Machine : '.$machineid);
        if ($inductee->cardCanUseMachine($cardid,$machineid)) {
            echo 'true';
        } else {
            $app->response->setStatus(403);
            echo 'false';
        }
    }
});

$app->get('/induct/:inductoruid', function ($inductoruid) use ($app) {
    $db = new Database;
    $machine = new Machine($db);
    $inductor = new Inductee($db);
    if ($inductor->canInductOthers($inductoruid)) {
        $app->render('inductform.html', array('machines'=>$machine->getAll(),'inductoruid'=>$inductoruid));
    } else {
        $app->response->setStatus(403);
        echo 'Sorry, I\'ve not been told you can induct people, see Martyn!';
    }
});

$app->post('/inductmember', function () use ($app) {
    $db = new Database;
    $machine = new Machine($db);
    $machineuid = $app->request->post('machine');
    $machinedetail = $machine->getById($machineuid);
    if (!$machinedetail) {
        $app->response->setStatus(400);
        echo 'Seems you do not rembember the machine uid off the top of your head.';
        return false;
    }
    $inductor = new Inductee($db);
    $inductoruid = $app->request->post('inductor');
    if ($inductor->canInductOthers($inductoruid)) {
        $inducteeuid = $inductor->getUidByCard($app->request->post('cardid'));
        if (!$inducteeuid) {
            $inductee = $inductor->createInductee($app->request->post('membername'),$app->request->post('cardid'));
            if ($inductee) {
                $inducteeuid = $inductee['uid'];
            } else {
                $app->response->setStatus(500);
                echo 'Could not create new inductee!';
                return false;
            }
        }
        $inducteedetail = $inductor->getById($inducteeuid)[0];
        $inductordetail = $inductor->getById($inductoruid)[0];
        $linker = new InducteeMachine($db);
        try {
        $linker->inductMachine($inducteeuid,$machineuid,$inductoruid);
        } catch ( Exception $e ) {}
        $memberdetails = array();
        foreach ($linker->getAllInducted($machineuid) as $member) {
            $memberdetails[] = $inductor->getById($member['memberuid'])[0];
        }
        $renderdata = array('test'=>'hello','members'=>$memberdetails,'inductor'=>$inductordetail,'inductee'=>$inducteedetail,'machine'=>$machinedetail);
        $app->log->debug(json_encode($renderdata));
        $app->render('inducted.html', $renderdata);
    } else {
        $app->response->setStatus(403);
        echo 'Sorry, I\'ve not been told you can induct people, see Martyn!';
    }
});

// Run app
$app->run();
