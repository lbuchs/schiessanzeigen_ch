<?php

/**
 * Description of get_csv
 *
 * @author Lukas Buchs
 */
class get_csv {
    protected $_csvUrl = 'https://data.geo.admin.ch/ch.vbs.schiessanzeigen/Zensa_Spl_Objektdaten.csv';
    protected $_timespans = [];

    public function __construct($placeId) {
        $req = new http_request($this->_csvUrl);
        $csv = $req->exec(false);
        unset ($req);

        $csvFileHandle = fopen('cache/tmp.csv', 'w+');
        fwrite($csvFileHandle, $csv);
        rewind($csvFileHandle);
        unset ($csv);


        while (($data = fgetcsv($csvFileHandle, 1000, ';')) !== FALSE) {
            $this->_handleCsvLine($data, $placeId);
        }
        fclose($csvFileHandle);
        unset ($data);

    }

    public function getTimespans() {
        return $this->_timespans;
    }

    protected function _handleCsvLine($csvLine, $placeId) {
        list($recordType, $pId, $date, $timeFrom, $timeTo, $comment) = $csvLine;

        if ($recordType === 'BSZ' && $pId === $placeId) {
            $matches = [];
            if (preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $date, $matches)) {
                $dateFrom = new DateTime();
                $dateFrom->setDate((int)$matches[1], (int)$matches[2], (int)$matches[3]);
                $dateTo = clone $dateFrom;

                $matches = [];
                if (preg_match('/^([0-9]{2})([0-9]{2})$/', $timeFrom, $matches)) {
                    $dateFrom->setTime((int)$matches[1], (int)$matches[2]);
                }

                $matches = [];
                if (preg_match('/^([0-9]{2})([0-9]{2})$/', $timeTo, $matches)) {
                    $dateTo->setTime((int)$matches[1], (int)$matches[2]);
                }

                if ($dateFrom->getTimestamp() !== $dateTo->getTimestamp()) {
                    $timespan = new stdClass();
                    $timespan->start = $dateFrom;
                    $timespan->end = $dateTo;
                    $timespan->comment = $comment;
                    $this->_timespans[] = $timespan;
                }
            }
        }
    }
}
