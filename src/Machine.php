<?php
namespace Usaged;

use Usaged\Database;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

Class Machine {

    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getPDO();
    }

    public function getAll() {
        $results = array();
        $query = $this->db->prepare('SELECT machines.uid,machinename,statuses.description as status,inductees.membername as statusby,statuswhen,statuses.color FROM machines '.
            'LEFT JOIN statuses ON machines.status = statuses.uid '.
            'LEFT JOIN inductees ON machines.statusby = inductees.uid');
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array(
                    'uid'=>$result['uid'],
		    'machinename'=>$result['machinename'],
		    'status'=>$result['status'],
		    'statusby'=>$result['statusby'],
		    'statuswhen'=>$result['statuswhen'],
		    'color'=>$result['color']);
            }
        }
        return $results;
    }

    public function createMachine($machinename) {
        $response = array();
        try {
            $response['uid'] = UUid::uuid5(Uuid::NIL, 'usage.ranyard.info'.$machinename)->toString();
        } catch (UnsatisfiedDependencyException $e) {
            $response['error'] = $e->getMessage();
            $status = 500;
        }
        $response['machinename'] = $machinename;
        $query = $this->db->prepare('INSERT INTO machines (uid,machinename) VALUES (:uid,:machinename)');
        $query->bindParam(':machinename',$machinename);
        $query->bindParam(':uid',$response['uid']);
        if ($query->execute()) {
            return($response);
        } else {
            throw (new \RuntimeException('Some database error occurred'));
        }
    }

    public function getByName($machinename) {
        $results = null;
        $query = $this->db->prepare('SELECT uid,machinename FROM machines WHERE machinename = :machinename LIMIT 1');
        $query->bindParam(':machinename',$machinename);
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results = array('uid'=>$result['uid'],'machinename'=>$result['machinename']);
            }
        }
        return $results;
    }

    public function getById($uid) {
        $results = null;
        $query = $this->db->prepare('SELECT uid,machinename FROM machines WHERE uid = :uid LIMIT 1');
        $query->bindParam(':uid',$uid);
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results = array('uid'=>$result['uid'],'machinename'=>$result['machinename']);
            }
        }
        return $results;
    }

    public function setMachineStatus($uid,$status,$useruid) {
        if ($this->getById($uid)) {
            $query = $this->db->prepare('UPDATE machines SET machinestatus = :status, statusby = :useruid, statuswhen = now() WHERE uid = :uid');
        }
        return ($query->execute() != false);
    }
}
