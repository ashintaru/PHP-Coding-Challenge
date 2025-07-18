<?php
// process_logs.php

$inFile  = 'sample‑logs.txt';
$outFile = 'output.txt';

if (!is_readable($inFile)) {
    fwrite(STDERR, "Error: Cannot open $inFile\n");
    exit(1);
}

$lines = file($inFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$records = [];
$userIds = [];
$ids = [];

foreach ($lines as $line) {
    // 1. Extract fields by fixed positions
    $id      = trim(substr($line, 0, 12));
    $userId  = trim(substr($line, 12, 6));
    $bytesTX = trim(substr($line, 18, 8));
    $bytesRX = trim(substr($line, 26, 8));
    $dtRaw   = trim(substr($line, 34)); // e.g. "2019-09-10 06:05"

    $bytesTX = number_format((int)$bytesTX);
    $bytesRX = number_format((int)$bytesRX);

    $dt = DateTime::createFromFormat('Y-m-d H:i', $dtRaw);
    if (!$dt) {
        fwrite(STDERR, "Warning: failed to parse date \"{$dtRaw}\" in line:\n$line\n");
        continue;
    }
    $dtFmt = $dt->format('D, F d Y, H:i:s'); // e.g. "Tue, September 10 2019, 06:05:00"

    $records[] = "{$userId}|{$bytesTX}|{$bytesRX}|{$dtFmt}|{$id}";
    $ids[] = $id;
    $userIds[] = $userId;
}

natsort($ids);
$ids = array_values($ids);

$userIds = array_unique($userIds);
sort($userIds);

file_put_contents($outFile, implode(PHP_EOL, $records) . PHP_EOL . PHP_EOL);

file_put_contents($outFile,
    "Sorted IDs:" . PHP_EOL . implode(PHP_EOL, $ids) . PHP_EOL . PHP_EOL,
    FILE_APPEND
);

$out = '';
foreach ($userIds as $i => $uid) {
    $out .= "[" . ($i+1) . "] " . $uid . PHP_EOL;
}
file_put_contents($outFile, $out, FILE_APPEND);

echo "✅ Output written to $outFile\n";
