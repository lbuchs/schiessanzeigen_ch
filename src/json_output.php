<?php

/**
 * @author Lukas Buchs
 */
class json_output {
    protected $_places;
    protected $_name;

    public function __construct($places=null, $name=null) {
        $this->_places = $places;
        $this->_name = $name;
    }

    public function output() {

        $output = new stdClass();
        $output->success = true;
        $output->requestTime = date('r');
        $output->requestTimeUnix = time();
        $output->validFrom = date('r', strtotime(date('Y-m-d') . ' 00:00:00'));
        $output->validFromUnix = strtotime(date('Y-m-d') . ' 00:00:00');
        $output->validTo = date('r', strtotime(date('Y-m-d') . ' 00:00:00')+(3600*24*3)-1);
        $output->validToUnix = strtotime(date('Y-m-d') . ' 00:00:00')+(3600*24*3)-1;
        $output->places = array();

        $lastEnd = 0;
        foreach ($this->_places as $place) {
            $times = array();
            foreach ($place->timespans as $timespan) {

                $timespan->start->setTimezone(new DateTimeZone('UTC'));
                $timespan->end->setTimezone(new DateTimeZone('UTC'));

                $o = new stdClass();
                $o->start = $timespan->start->format('r');
                $o->startUnix = $timespan->start->getTimestamp();
                $o->end = $timespan->end->format('r');
                $o->endUnix = $timespan->end->getTimestamp();
                $o->comment = $timespan->comment;
                $times[] = $o;

                $lastEnd = max($lastEnd, $timespan->end->getTimestamp());
            }

            $p = new stdClass();
            $p->id = $place->id;
            $p->name = $place->name;
            $p->timespans = $times;
            $output->places[] = $p;
        }

        // Gültig bis zum grössten Datum
        if ($lastEnd > 0) {
            $output->validTo = date('r', $lastEnd);
            $output->validToUnix = $lastEnd;
        }

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        print(json_encode($output, JSON_PRETTY_PRINT));
    }

    public function errorOut(Throwable $t) {
        $output = new stdClass();
        $output->success = false;
        $output->message = $t->getMessage();
        $output->requestTime = date('r');
        $output->requestTimeUnix = time();
        $output->validFrom = date('r', strtotime(date('Y-m-d') . ' 00:00:00'));
        $output->validFromUnix = strtotime(date('Y-m-d') . ' 00:00:00');
        $output->validTo = date('r', strtotime(date('Y-m-d') . ' 00:00:00')+(3600*24*2)-1);
        $output->validToUnix = strtotime(date('Y-m-d') . ' 00:00:00')+(3600*24*2)-1;
        $output->places = array();

        // 24h aktiv
        if ($this->_name) {
            $times = array();
            foreach ($this->_getErrorTimespans() as $timespan) {
                $o = new stdClass();
                $o->start = $timespan->start->format('r');
                $o->startUnix = $timespan->start->getTimestamp();
                $o->end = $timespan->end->format('r');
                $o->endUnix = $timespan->end->getTimestamp();
                $o->comment = $timespan->comment;
                $times[] = $o;
            }

            $p = new stdClass();
            $p->id = '1314.010';
            $p->name = $this->_name;
            $p->cacheTime = date('r', time());
            $p->cacheTimeUnix = time();
            $p->timespans = $times;
            $output->places[] = $p;
        }

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        print(json_encode($output));
    }



    protected function _getErrorTimespans() {
        $timespans = array();

        // heute
        $timespan = new stdClass();
        $timespan->comment = 'unable to load: full day active';
        $timespan->start = new DateTime();
        $timespan->start->setTime(0, 0, 0);
        $timespan->end = new DateTime();
        $timespan->end->setTime(23, 59, 59);
        $timespans[] = $timespan;

        // morgen
        $timespan = new stdClass();
        $timespan->comment = 'unable to load: full day active';
        $timespan->start = new DateTime();
        $timespan->start->setTime(0, 0, 0);
        $timespan->start->add(new DateInterval('P1D'));
        $timespan->end = new DateTime();
        $timespan->end->setTime(23, 59, 59);
        $timespan->end->add(new DateInterval('P1D'));
        $timespans[] = $timespan;

        return $timespans;
    }
}
