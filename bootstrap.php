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

function mageErrorHandler($code, $message, $file, $line, $trace) {
    $output = "<div style='background:#ffaa99;padding: 5px;'><strong>Error:</strong> " . $message . ' in ' . $file . ' on line ' . $line . "</div>";
    $backtrace = $trace ?: debug_backtrace();
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
                $argRows = array_map(function($row) {
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
        } else {
            $str = $row['function'];
        }
        $str = "<td nowrap='true'>$str</td>";
        $str .= "<td nowrap='true'>" . (isset($row['file']) ?  $row['file'] . ':' . $row['line'] : "") . "</td>";
        $rows[] = "<tr>" . $str . "</tr>";
    }
    $output .= "<table style=\"border:1px solid black\"><caption>Debug backtrace:</caption>". implode("", $rows) . "</table>";
    $output = "<div style='padding:10px'>$output</div>";
    echo $output;

    exit;
};

set_error_handler('mageErrorHandler');

function start($title)
{
    global $timeStack;
    if (!is_array($timeStack)) {
        $timeStack = [];
    }
    echo str_pad($title, strlen($title) + count($timeStack), "_", STR_PAD_LEFT) . "<br/>";
    array_push($timeStack, [microtime(true), $title]);
}

function stop()
{
    global $timeStack;
    list($start, $title) = array_pop($timeStack);
    $label = sprintf("End $title: %f", microtime(true) - $start);
    echo str_pad($label,strlen($label) + count($timeStack), "_", STR_PAD_LEFT) . "<br>";
}
