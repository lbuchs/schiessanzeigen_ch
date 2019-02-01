<?php

/**
 * @author Lukas Buchs
 */
class html_output {
    protected $_timespans;
    protected $_id;
    protected $_name;
    protected $_cacheRequestTime;

    public function __construct($id=null, $name=null, $timespans=array(), $cacheRequestTime=null) {
        $this->_timespans = $timespans;
        $this->_id = $id;
        $this->_name = $name;
        $this->_cacheRequestTime = $cacheRequestTime;
    }

    public function output() {
        header('content-type: text/html');
        print("<!DOCTYPE html>\n");
        print('<html><head><title>Schiessplatz ' . htmlspecialchars($this->_name) . '</title></head>');
        print('<body style="font-family:sans-serif">');
        print('
            <table border="1" cellspacing="0" style="border-collapse:collapse;">
                    <tbody>
                            <tr>
                                <td style="padding:2px 10px">ID:</td>
                                <td style="padding:2px 10px">' . htmlspecialchars($this->_id) . '</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 10px">Name:</td>
                                <td style="padding:2px 10px">' . htmlspecialchars($this->_name) . '</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 10px">Cache:</td>
                                <td style="padding:2px 10px">' . htmlspecialchars(date('d.m.Y H:i', $this->_cacheRequestTime)) . '</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding:15px 2px 2px 10px">Zeiten:</td>
                            </tr>');

        foreach ($this->_timespans as $timespan) {
            print('
                <tr>
                    <td colspan="2" style="padding:2px 10px">' . htmlspecialchars($timespan->start->format('D, d.m.Y H:i'). ' - ' . $timespan->end->format('H:i')) . '</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding:2px 10px">' . htmlspecialchars($timespan->comment) . '</td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>');
        }

        print('</tbody></table></body></html>');

    }

    public function errorOut(Throwable $t) {
        $output = new stdClass();
        $output->success = false;
        $output->id = $this->_id;
        $output->name = $this->_name;
        $output->requestTime = date('r');
        $output->message = $t->getMessage();

        header('content-type: text/plain');
        print("FEHLER\n\n");
        print($t->getMessage());
    }
}
