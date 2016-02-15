<?php
namespace Usaged;

use Usaged\Database;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Slim\Slim;

Class InducteeMachine {

    private $db;
    private $app;

    public function __construct(Database $db) {
        $this->db = $db->getPDO();
        $this->app = Slim::getInstance();
    }

    public function getAll() {
        $query = $this->db->prepare('SELECT memberuid,machineuid FROM inducteemachine');
        $results = array();
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array('memberuid'=>$result['memberuid'],'machineuid'=>$result['machineuid']);
            }
            return $results;
        } else {
            return null;
        }
    }

    public function inductMachine($memberuid,$machineuid,$inductoruid) {
        $response = array();

        $response['memberuid'] = $memberuid;
        $response['machineuid'] = $machineuid;
        $response['inductoruid'] = $inductoruid;
        $query = $this->db->prepare('INSERT INTO inducteemachine (memberuid,machineuid,$inductoruid) VALUES (:memberuid,:machineuid, :inductoruid)');


        $this->app->log->debug($query->queryString);
        $this->app->log->debug($response['memberuid'].','.$response['machineuid'].','.$response['inductoruid']);
        $query->bindParam(':memberuid',$response['memberuid']);
        $query->bindParam(':machineuid',$response['machineuid']);
        $query->bindParam(':inductoruid',$response['inductoruid']);
        if ($query->execute()) {
            return($response);
        } else {
            throw (new RuntimeException('Some database error occurred'));
        }
    }
}
