<?php
namespace Usaged;

use \PDO;

require_once '../config.php';

Class Database {

    private $pdo;

    public function __construct() {
$dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;
$options = array(
//    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
); 
        $this->pdo = new PDO($dsn,DB_USERNAME, DB_PASSWORD, $options);
    }

    public function __destruct() {
    }

    public function getPDO() {
        return $this->pdo;
    }

}
