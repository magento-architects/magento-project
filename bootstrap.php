<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
error_reporting(E_ALL);
ini_set('opcache.revalidate_freq', 3);
ini_set('display_startup_errors', true);
ini_set('display_errors', true);
/*  For data consistency between displaying (printing) and serialization a float number */
ini_set('precision', 14);
ini_set('serialize_precision', 14);
date_default_timezone_set('UTC');
$timeStack = [];
$total = [];

function handleException(\Exception $e) {
    mageErrorHandler(0, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
}

function mageErrorHandler($code, $message, $file, $line, $trace) {
    if (ini_get('display_errors')) {
        $output = "<div style='background:#ffaa99;padding: 5px;'><strong>Error:</strong> " . $message . ' in ' . $file . ' on line ' . $line . "</div>";
        $backtrace = debug_backtrace();
        $rows = [];
        foreach ($backtrace as $row) {
            if (!$row) {
                break;
            }
            if (isset($row['class'])) {
                if (isset($row['object'])) {
                    $str = $row['class'] . $row['type'] . $row['function'];
                } else {
                    $str = $row['class'] . $row['type'] . $row['function'];
                }
                $argsStr = '';
                if (isset($row['args'])) {
                    $argRows = array_map(function ($row) {
                        if (is_object($row)) {
                            return get_class($row) . " {object}";
                        } else if (is_array($row)) {
                            return "[" . count($row) . "]";
                        } else {
                            return "\"" . $row . "\"";
                        }
                    }, $row['args']);
                    $argsStr .= implode(', ', $argRows);
                }
                $str .= '(' . $argsStr . ')';
            } else if (isset($row['function'])){
                $str = $row['function'];
            } else {
                $str = '';
            }
            $str = "<td nowrap='true'>$str</td>";
            $str .= "<td nowrap='true'>" . (isset($row['file']) ? substr($row['file'], strlen(__DIR__)) . ':' . $row['line'] : "") . "</td>";
            $rows[] = "<tr>" . $str . "</tr>";
        }
        $output .= "<table style=\"border:1px solid black\">" . implode("", $rows) . "</table>";
        $output = "<div style='padding:10px'>$output</div>";
        echo $output;
    } else {
        echo "Error happened";
    }
    exit;
};

set_error_handler('mageErrorHandler');

function start($title)
{
    global $timeStack;
    if (!is_array($timeStack)) {
        $timeStack = [];
    }
  //  echo str_pad($title, strlen($title) + count($timeStack), "_", STR_PAD_LEFT) . "<br/>";
    array_push($timeStack, [microtime(true), $title]);
}

function stop()
{
    global $timeStack, $total;
    list($start, $title) = array_pop($timeStack);
    $time = microtime(true) - $start;
    $label = sprintf("End $title: %f", $time);
    //echo str_pad($label,strlen($label) + count($timeStack), "_", STR_PAD_LEFT) . "<br>";
    if (!isset($total[$title])) {
        $total[$title] = ['time' => $time, 'count' => 0];
    }
    $total[$title]['time'] += $time;
    $total[$title]['count'] += 1;
}

register_shutdown_function(function() {
    global $total, $objects;
    if (count($total)) {
        var_dump($total);
    }
    var_dump($objects);
});
