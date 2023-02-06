<?php

if ($argc != 3) {
    echo "Usage: " . $argv[0] . " <path/to/index.xml> <threshold>";
    exit(-1);
}

$file = $argv[1];
$threshold = (double) $argv[2];

$report = simplexml_load_file($file);
$ratio =  number_format((double) $report["line-rate"] * 100, 2);

echo "Lines: $ratio%";
echo "Threshold: $threshold%";

if ($ratio < $threshold) {
    echo "FAILED! Code coverage is under threshold.";
    exit(-1);
}

echo "SUCCESS!";
