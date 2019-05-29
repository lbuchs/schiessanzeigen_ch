<?php
/**
 * @author Lukas Buchs
 */
class html_parser {
    protected $_base_url = 'https://www.vtg.admin.ch/de/aktuell/mitteilungen/schiessanzeigen/_jcr_content/contentPar/zensa_copy.ajax_zensa_detail.html/%ID%.html';
    protected $_id;
    protected $_ranges = array();
    public $requestTime = null;

    public function __construct($id) {
        $this->_id = $id;
    }


    public function parse($cache=true) {
        $req = new http_request(str_replace('%ID%', $this->_id, $this->_base_url));
        $html = $req->exec($cache);
        $this->requestTime = $req->getRequestTime();
        unset ($req);

        $matches = null;
        preg_match('/Schiessdaten<\/h3>((?:.|\n|\r)*)<h/iU', $html, $matches);

        if (!$matches || !$matches[1]) {
            throw new Exception('Aktuelle Schiessdaten nicht vorhanden.');
        }

        $doc = new DOMDocument();
        $doc->loadHTML($matches[1]);

        // HTML-Tabelle mit Datum und Zeit suchen
        $this->_recursiveSearchHtml($doc->documentElement);

        // Split-Zeiten
        $this->_getSplitTimes($html);

        // sortieren
        usort($this->_ranges, function($a, $b) {
            if ($a->start > $b->start) return 1;
            if ($a->start < $b->start) return -1;
            return 0;
        });

        // zurückgeben
        return $this->_ranges;
    }


    protected function _recursiveSearchHtml(DOMNode $node) {
        if ($node instanceof DOMText) {
            // Do. 31.01.2019
            $matches = null;
            if (preg_match('/[a-z]{2,}(?:\.\s)*([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{2,4})/i', trim($node->textContent), $matches)) {
                $this->_handleDateNode($node, $matches[1]);
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                $this->_recursiveSearchHtml($childNode);
            }
        }
    }

    protected function _handleDateNode(DOMText $node, $date) {
        // Container ist zwei ebenen höher
        $container = $node->parentNode->parentNode;

        $time = (string) $container->childNodes[3]->firstChild->textContent;
        $comment = (string) $container->childNodes[5]->firstChild->textContent;
        $matches = null;

        // 08:00 - 18:00
        if (preg_match('/([0-9]{1,2}:[0-9]{1,2})\s+-\s+([0-9]{1,2}:[0-9]{1,2})/', $time, $matches)) {
            $timeStart = $matches[1];
            $timeEnd = $matches[2];

            $range = new stdClass();
            $range->start = new DateTime($date . ' ' . $timeStart, new DateTimeZone('Europe/Zurich'));
            $range->end = new DateTime($date . ' ' . $timeEnd, new DateTimeZone('Europe/Zurich'));
            $range->comment = trim($comment);
            $this->_ranges[] = $range;
            unset ($range);
        }
    }

    protected function _getSplitTimes($html) {
        // Das Schiessen wird jeweils von 11:45 - 1330 Uhr sowie von 18:00 - 19:00 Uhr unterbrochen.

        $matches = null;
        if (preg_match('/schiessen([^<>]+)unterbrochen/i', $html, $matches)) {
            $line = $matches[1];

            $matches = array();
            if (preg_match_all('/([0-9]{1,2}:?[0-9]{1,2})\s*-\s*([0-9]{1,2}:?[0-9]{1,2})/', $line, $matches,  PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $start = $this->_cleanupTimeStr($match[1]);
                    $end = $this->_cleanupTimeStr($match[2]);
                    $this->_splitRanges($start, $end);
                }
            }
        }
    }

    protected function _splitRanges($startTime, $endTime) {
        $newRanges = array();

        foreach ($this->_ranges as &$range) {
            $rangeStart = $range->start;
            $rangeEnd = $range->end;
            $splitStart = new DateTime($rangeStart->format('d.m.Y'). ' ' . $startTime, $rangeStart->getTimezone());
            $splitEnd = new DateTime($rangeEnd->format('d.m.Y'). ' ' . $endTime, $rangeEnd->getTimezone());

            // Falls es eine überschneidung gibt, aufteilen
            if ($rangeStart < $splitStart && $rangeEnd > $splitStart) {
                $range->end = $splitStart;

                if ($rangeEnd > $splitEnd) {
                    $newRange = new stdClass();
                    $newRange->start = clone $splitEnd;
                    $newRange->end = clone $rangeEnd;
                    $newRange->comment = $range->comment;
                    $newRanges[] = $newRange;
                }
            }
        }

        foreach ($newRanges as $newRange) {
            $this->_ranges[] = $newRange;
        }
    }

        protected function _cleanupTimeStr($time) {
        if (preg_match('/[0-9]{4}/', $time)) {
            return substr($time, 0, 2) . ':' . substr($time, 2);
        }
        return $time;
    }
}
