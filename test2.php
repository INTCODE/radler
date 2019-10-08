<?php

// Load Timestamps
$sixMonthsAgo = strtotime("31-06-2019");
$oneYearAgo = strtotime("01-06-2019");

// Load Files & Check Timestamps
// Note* you can filter by ext, e.g: /datacore/*.pdf
$myFiles = rglob(__DIR__."/vendor/*");

// Now Check & Build List
$checkedFiles = array();
foreach ($myFiles as $myFile) {
    $fileModifiedTime = filemtime($myFile);
    if ($fileModifiedTime >= $oneYearAgo && $fileModifiedTime <= $sixMonthsAgo)
        $checkedFiles[] = $myFile;
}

// Debug
echo "Found ". sizeof($checkedFiles) ." files that were updated from ".
    date('d-m-Y H:i:s a', $sixMonthsAgo) ." to ".
    date('d-m-Y H:i:s a', $oneYearAgo);

echo "<pre>";
//print_r($checkedFiles);
echo "</pre>";


function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}
