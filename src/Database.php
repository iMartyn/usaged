<?php
namespace Usaged;

use \PDO;
use Slim\Slim;

require_once '../config.php';

Class Database {

    private $pdo;

    public function __construct() {
$dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;
$options = array(
//    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);
        $this->app = Slim::getInstance();
        $this->app->log->debug('Connecting to database');
        $this->pdo = new PDO($dsn,DB_USERNAME, DB_PASSWORD, $options);
        $this->app->log->debug('Connected to database');
        $query = $this->pdo->prepare('SELECT uid,membername,cardid FROM inductees LIMIT 1');
        $result = $query->execute();
        $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        if ($result !== TRUE) {
            $this->app->log->debug('creating inductees');
            $query = $this->pdo->prepare('CREATE TABLE `inductees` (
                `uid` varchar(36) NOT NULL,
                `membername` varchar(255) DEFAULT NULL,
                `cardid` varchar(8) NOT NULL,
                PRIMARY KEY (`uid`))');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        }
        $query = $this->pdo->prepare('SELECT uid,membername,cardid FROM inductees LIMIT 1');
        $result = $query->execute();
        $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        if ($result !== TRUE) {
            $this->app->log->debug('creating machines');
            $query = $this->pdo->prepare('CREATE TABLE `machines` (
                `uid` varchar(36) NOT NULL,
                `machinename` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`uid`))');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        }
        $query = $this->pdo->prepare('SELECT memberuid,machineuid,inductoruid FROM inducteemachine');
        $result = $query->execute();
        $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        if ($result !== TRUE) {
            $this->app->log->debug('creating inducteemachine');
            $query = $this->pdo->prepare('CREATE TABLE `inducteemachine` (
                `memberuid` varchar(36) NOT NULL,
                `machineuid` varchar(36) NOT NULL,
                `inductoruid` varchar(36) NOT NULL,
                PRIMARY KEY (`memberuid`,`machineuid`))');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        }
        $query = $this->pdo->prepare('SELECT inducteeuid,machineuid,starttime,endtime FROM logs');
        $result = $query->execute();
        $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        if ($result !== TRUE) {
            $this->app->log->debug('creating logs');
            $query = $this->pdo->prepare('CREATE TABLE `logs` (
                `inducteeuid` varchar(36) NOT NULL,
                `machineuid` varchar(36) NOT NULL,
                `starttime` datetime  NOT NULL,
                `endtime` datetime  NOT NULL,
                PRIMARY KEY (`inducteeuid`,`machineuid`,`starttime`))');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        }
        $query = $this->pdo->prepare('SELECT caninduct FROM inducteemachines LIMIT 1');
        $result = $query->execute();
        $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        if ($result !== TRUE) {
            $this->app->log->debug('adding caninduct to inducteemachine');
            $query = $this->pdo->prepare('ALTER TABLE `inducteemachine` ADD COLUMN (
                `caninduct` boolean NOT NULL DEFAULT FALSE
            )');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
            $this->app->log->debug('dropping caninduct from inductees');
            $query = $this->pdo->prepare('ALTER TABLE `inductees` DROP COLUMN `caninduct`');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        }
        $query = $this->pdo->prepare('SELECT uid,cardid,description FROM specialcards');
        $result = $query->execute();
        $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        if ($result !== TRUE) {
            $this->app->log->debug('creating inducteemachine');
            $query = $this->pdo->prepare('CREATE TABLE `specialcards` (
                `uid` varchar(36) NOT NULL,
                `cardid` varchar(8) NOT NULL,
                `description` varchar(255) NOT NULL,
                PRIMARY KEY (`uid`))');
            $result = $query->execute();
            $this->app->log->debug('result is '.gettype($result).' value '.(boolean)$result);
        }
    }

    public function __destruct() {
    }

    public function getPDO() {
        return $this->pdo;
    }

}
