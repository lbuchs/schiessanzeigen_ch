<?php

/**
 * @author Lukas Buchs
 */
class json_output {
    protected $_places;

    public function __construct($places=null) {
        $this->_places = $places;
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

        foreach ($this->_places as $place) {
            $times = array();
            foreach ($place->timespans as $timespan) {
                $o = new stdClass();
                $o->start = $timespan->start->format('r');
                $o->startUnix = $timespan->start->getTimestamp();
                $o->end = $timespan->end->format('r');
                $o->endUnix = $timespan->end->getTimestamp();
                $o->comment = $timespan->comment;
                $times[] = $o;
            }

            $p = new stdClass();
            $p->id = $place->id;
            $p->name = $place->name;
            $p->cacheTime = date('r', $place->requestTime);
            $p->cacheTimeUnix = $place->requestTime;
            $p->timespans = $times;
            $output->places[] = $p;
        }

        header('content-type: application/json');
        print(json_encode($output));
    }

    public function errorOut(Throwable $t) {
        $output = new stdClass();
        $output->success = false;
        $output->message = $t->getMessage();
        $output->requestTime = date('r');
        $output->requestTimeUnix = time();
        $output->validFrom = date('r', strtotime(date('Y-m-d') . ' 00:00:00')-1);
        $output->validFromUnix = strtotime(date('Y-m-d') . ' 00:00:00')-1;
        $output->validTo = date('r', strtotime(date('Y-m-d') . ' 00:00:00'));
        $output->validToUnix = strtotime(date('Y-m-d') . ' 00:00:00');
        $output->places = array();


        header('content-type: application/json');
        print(json_encode($output));
    }
}
