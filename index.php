<?php

require_once 'src/http_request.php';
require_once 'src/html_parser.php';
require_once 'src/get_list.php';
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

    // Liste der Plätze
    $gl = new get_list();

    // request from xcontest server.
    // if there is a need for more places in future, we can extend $name to a array
    if (!$commandLine && array_key_exists('name', $_GET) && $_GET['name'] === 'places_xcontest') {
        $name = 'Blumenstein';
        $id = $gl->getIdByName($name);

    } else if (!$commandLine && array_key_exists('name', $_GET) && $_GET['name']) {
        $name = $_GET['name'];
        $id = $gl->getIdByName($name);

    } else if ($commandLine) {
        foreach ($argv as $cmdArg) {
            try {
                $name = trim($cmdArg);
                $id = $gl->getIdByName($name);
                break;
            } catch (Exception $ex) {}
        }
    }

    if (!$name || !$id) {
        throw new Exception('please provide name or id with HTTP GET');
    }

    $parser = new html_parser($id);
    $ranges = $parser->parse();

    if ($format === 'json') {

        $place = new stdClass();
        $place->id = $id;
        $place->name = $name;
        $place->timespans = $ranges;
        $place->requestTime = $parser->requestTime;

        $out = new json_output(array($place));
        $out->output();

    } else if ($format === 'html') {
        $out = new html_output($id, $name, $ranges, $parser->requestTime);
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