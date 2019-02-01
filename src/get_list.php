<?php


/**
 * Description of get_list
 *
 * @author Lukas Buchs
 */
class get_list {
    protected $_listUrl = 'https://www.vtg.admin.ch/de/aktuell/mitteilungen/schiessanzeigen/_jcr_content/contentPar/zensa_copy.ajax.html';
    protected $_schiessplaetze = array();


    public function __construct() {
        $req = new http_request($this->_listUrl);
        $json = $req->exec();
        unset ($req);

        $json = json_decode($json);
        if (!$json instanceof stdClass || !property_exists($json, 'schiessplaetze') || !is_array($json->schiessplaetze)) {
            throw new Exception('unable to load place list');
        }

        foreach ($json->schiessplaetze as $sp) {
            $this->_schiessplaetze[$sp->id] = $sp->title;
        }
    }

    public function getIdByName($name) {
        if (in_array($name, $this->_schiessplaetze)) {
            return array_search($name, $this->_schiessplaetze);
        }
        
        throw new Exception('place name ' . $name . ' not found.');
    }

    public function getNameById($id) {
        if (array_key_exists($id, $this->_schiessplaetze)) {
            return $this->_schiessplaetze[$id];
        }

        throw new Exception('place id ' . $id . ' not found.');
    }
}
