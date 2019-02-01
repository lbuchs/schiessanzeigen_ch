<?php

require_once 'src/http_request.php';
require_once 'src/html_parser.php';
require_once 'src/json_output.php';
require_once 'src/get_list.php';

$format = 'json';
$formats = array('json');
$name = null;
$id = null;

try {

    // use https
    if((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") && $_SERVER['HTTP_HOST'] !== 'localhost'){
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }

    // Liste der Plätze
    $gl = new get_list();

    if (array_key_exists('format', $_GET)) {
        if (in_array($_GET['format'], $formats)) {
            $format = $_GET['format'];
        }
    }

    if (array_key_exists('name', $_GET) && $_GET['name']) {
        $name = $_GET['name'];
        $id = $gl->getIdByName($name);

    } else if (array_key_exists('id', $_GET) && $_GET['id']) {
        $id = $_GET['id'];
        $name = $gl->getNameById($id);
    }

    if (!$name || !$id) {
        throw new Exception('please provide name or id with HTTP GET');
    }

    $parser = new html_parser($id);
    $ranges = $parser->parse();

    if ($format === 'json') {
        $out = new json_output($id, $name, $ranges, $parser->requestTime);
        $out->output();
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
    }
}