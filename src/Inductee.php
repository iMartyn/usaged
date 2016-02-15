<?php
namespace Usaged;

use Usaged\Database;
use Usaged\InducteeMachine;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Slim\Slim;

Class Inductee {

    private $db;
    private $app;

    public function __construct(Database $db) {
        $this->db = $db->getPDO();
        $this->app = Slim::getInstance();
    }

    public function getAll() {
        $query = $this->db->prepare('SELECT uid,membername,cardid FROM inductees');
        $results = array();
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array('uid'=>$result['uid'],'membername'=>$result['membername'],'cardid'=>$result['cardid']);
            }
            return $results;
        } else {
            return null;
        }
    }

    public function createInductee($membername,$cardid) {
        $response = array();
        try {
            $response['uid'] = UUid::uuid5(Uuid::NIL, 'usage.ranyard.info'.$membername.$cardid)->toString();
        } catch (UnsatisfiedDependencyException $e) {
            $response['error'] = $e->getMessage();
            $status = 500;
        }
	$response['membername'] = $membername;
        $response['cardid'] = $cardid;
        $query = $this->db->prepare('INSERT INTO inductees (uid,membername,cardid) VALUES (:uid,:membername,:cardid)');
        $this->app->log->debug($query->queryString);
        $this->app->log->debug($response['membername'].','.$response['uid'].','.$response['cardid']);
        $query->bindParam(':membername',$response['membername']);
        $query->bindParam(':uid',$response['uid']);
        $query->bindParam(':cardid',$response['cardid']);
        if ($query->execute()) {
            return($response);
        } else {
            throw (new RuntimeException('Some database error occurred'));
        }
    }

    public function cardCanUseMachine($cardid,$machineid) {
        $query = $this->db->prepare('SELECT cardid FROM inductees LEFT JOIN inducteemachine ON inductees.uid = inducteemachine.memberuid WHERE cardid = :cardid');
        $query->bindParam(':cardid',$cardid);
        $query->bindParam(':machineid',$machineid);
        $query->execute();
        return ($query->rowCount() > 0);
    }
}
