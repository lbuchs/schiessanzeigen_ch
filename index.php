<?php

require_once 'src/http_request.php';
require_once 'src/get_csv.php';
require_once 'src/html_parser.php';
require_once 'src/json_output.php';
require_once 'src/html_output.php';

$format = 'html';
$formats = array('json', 'html');
$name = null;
$id = null;

try {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    $commandLine = isset($argv) && is_array($argv) && $argv;

    if ($commandLine) {
        chdir(dirname(__FILE__));
    }

    // use https
    if(!$commandLine && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") && $_SERVER['HTTP_HOST'] !== 'localhost'){
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }

    // Format (default: html)
    if (!$commandLine && array_key_exists('format', $_GET)) {
        if (in_array($_GET['format'], $formats)) {
            $format = $_GET['format'];
        }
    } else if ($commandLine) {
        $format = 'cmd';
    }

    $id = '1314.010'; // id of Blumenstein
    $name = 'Blumenstein';

    // datenabfrage
//    $getCsv = new get_csv($id);
//    $timespans = $getCsv->getTimespans();
//    unset ($getCsv);

    // HTML parsen
    $getHtml = new html_parser($id);
    $timespans = $getHtml->parse(!$commandLine);

    if ($format === 'json') {

        $place = new stdClass();
        $place->id = $id;
        $place->name = $name;
        $place->timespans = $timespans;
        $place->requestTime = $getHtml->requestTime;

        $out = new json_output(array($place), $name);
        $out->output();

    } else if ($format === 'html') {
        $out = new html_output($id, $name, $timespans, $getHtml->requestTime);
        $out->output();


    } else if ($format === 'cmd') {
        // Keine Ausgabe.
    }

} catch (Throwable $ex) {
    $msg = array(
        str_repeat('-', 40),
        'Date: '. date('Y-m-d H:i:s'),
        'Msg:  ' . $ex->getMessage(),
        'Code: ' . $ex->getCode(),
        'File: ' . $ex->getFile(),
        'Line: ' . $ex->getLine(),
        'Trace:',
        $ex->getTraceAsString()
    );
    file_put_contents('log/error.log', "\n" . implode("\n", $msg), FILE_APPEND);

    if ($format === 'json') {
        $out = new json_output($id, $name);
        $out->errorOut($ex);

    } else if ($format === 'html') {
        $out = new html_output($id, $name);
        $out->errorOut($ex);

    } else if ($format === 'cmd') {
        print ($ex->getMessage());
    }
}