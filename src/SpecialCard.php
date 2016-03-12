<?php
namespace Usaged;

use Usaged\Database;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

Class SpecialCard {

    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getPDO();
    }

    public function getAll() {
        $results = array();
        $query = $this->db->prepare('SELECT uid,cardid,description FROM specialcards');
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array('uid'=>$result['uid'],'description'=>$result['description'],'cardid'=>$result['cardid']);
            }
        }
        return $results;
    }

    public function createSpecialCard($cardid,$description) {
        $response = array();
        try {
            $response['uid'] = UUid::uuid5(Uuid::NIL, 'usage.ranyard.info/specialcard/'.$cardid)->toString();
        } catch (UnsatisfiedDependencyException $e) {
            $response['error'] = $e->getMessage();
            $status = 500;
        }
        $response['description'] = $description;
        $response['cardid'] = $cardid;
        $query = $this->db->prepare('INSERT INTO specialcards (uid,cardid,description) VALUES (:uid,:cardid,:description)');
        $query->bindParam(':description',$description);
        $query->bindParam(':cardid',$cardid);
        $query->bindParam(':uid',$response['uid']);
        if ($query->execute()) {
            return($response);
        } else {
            throw (new \RuntimeException('Some database error occurred'));
        }
    }

    public function getByCardId($cardid) {
        $results = null;
        $query = $this->db->prepare('SELECT cardid,uid,description FROM specialcards WHERE cardid = :cardid LIMIT 1');
        $query->bindParam(':cardid',$cardid);
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results = array('uid'=>$result['uid'],'cardid'=>$result['cardid'],'description'=>$result['description']);
            }
        }
        return $results;
    }
}
