<?php



/**
 * 
 * @author Lukas Buchs
 */
class json_output {
    protected $_timespans;
    protected $_id;
    protected $_name;

    public function __construct($id=null, $name=null, $timespans=array()) {
        $this->_timespans = $timespans;
        $this->_id = $id;
        $this->_name = $name;
    }

    public function output() {
        $times = array();
        foreach ($this->_timespans as $timespan) {
            $o = new stdClass();
            $o->start = $timespan->start->format('r');
            $o->end = $timespan->end->format('r');
            $o->comment = $timespan->comment;
            $times[] = $o;
        }

        $output = new stdClass();
        $output->success = true;
        $output->id = $this->_id;
        $output->name = $this->_name;
        $output->requestTime = date('r');
        $output->times = $times;

        header('content-type: application/json');
        print(json_encode($output));
    }

    public function errorOut(Throwable $t) {
        $output = new stdClass();
        $output->success = false;
        $output->id = $this->_id;
        $output->name = $this->_name;
        $output->requestTime = date('r');
        $output->message = $t->getMessage();

        header('content-type: application/json');
        print(json_encode($output));
    }
}
