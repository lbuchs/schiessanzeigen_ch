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
        $hasToday = false;
        foreach ($this->_timespans as $timespan) {
            if ($timespan->start->format('d.m.Y') === date('d.m.Y')) {
                $hasToday = true;
                break;
            }
        }


        $html = '
            <table><tbody>
                <tr>
                    <td>Name:</td>
                    <td>' . htmlspecialchars($this->_name) . '</td>
                </tr>
                <tr class="id">
                    <td>ID:</td>
                    <td>' . htmlspecialchars($this->_id) . '</td>
                </tr>
                <tr>
                    <td colspan="2" class="times_header">Zeiten:</td>
                </tr>';

        if (!$this->_cacheRequestTime && !$this->_timespans) {
            $html .= '
                <tr class="timerow spacer">
                    <td colspan="2"></td>
                </tr>
                <tr class="timerow today">
                    <td colspan="2" style="text-align:center">Unbekannt.<br>Offizielle Publikation beachten</td>
                </tr>
                ';

        } else if (!$hasToday) {
            $html .= '
                <tr class="timerow spacer">
                    <td colspan="2"></td>
                </tr>
                <tr class="timerow today">
                    <td colspan="2" style="text-align:center">Heute kein Schiessbetrieb.</td>
                </tr>
                ';
        }

        if ($this->_cacheRequestTime && !$this->_timespans) {
            $html .= '
                <tr class="timerow">
                    <td colspan="2" style="text-align:center;font-style:italic;">Kein geplanter Schiessbetrieb in den nächsten Tagen.</td>
                </tr>
                ';
        }


        foreach ($this->_timespans as $timespan) {

            $todayCls = $timespan->start->format('d.m.Y') === date('d.m.Y') ? ' today' : '';

            $html .= '
                <tr class="timerow spacer">
                    <td colspan="2"></td>
                </tr>
                <tr class="timerow' . $todayCls . '">
                    <td colspan="2">' . htmlspecialchars($this->__getDate($timespan->start->getTimestamp()) . ' ' . $timespan->start->format('H:i'). ' - ' . $timespan->end->format('H:i')) . '</td>
                </tr>
                <tr class="timerow' . $todayCls . '">
                    <td colspan="2">' . htmlspecialchars($timespan->comment) . '</td>
                </tr>
                ';
        }

        $html .= '</tbody></table>';
        $output = file_get_contents('template/template.html');
        $output = str_replace(array('%TITLE%', '%TABLE%'), array(htmlspecialchars($this->_name), $html), $output);

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: max-age=' . ($this->_cacheRequestTime+(3600*24) - time()));
        header('Last-Modified: ' . date('r', $this->_cacheRequestTime));
        header('Expires: ' . date('r', $this->_cacheRequestTime+(3600*24)));

        print $output;
    }

    public function errorOut(Throwable $t) {
        $this->output();
    }

    private function __getDate($date) {
        $days = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
        if (date('Y-m-d', $date) === date('Y-m-d')) {
            return 'Heute';
        } else {
            return $days[intval(date('w', $date))] . ', ' . date('d.m.Y', $date);
        }
    }
}
