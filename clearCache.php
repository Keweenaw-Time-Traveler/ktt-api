<?php
// Path to the cache directory
$cacheDir = 'cache/';

// Cache expiration time (30 minutes)
$cacheExpirationTime = 30 * 60; // 30 minutes in seconds

// Get the current timestamp
$currentTimestamp = time();

// Iterate through cache files and remove those that have expired
$cacheFiles = glob($cacheDir . '*');
foreach ($cacheFiles as $cacheFile) {
    $fileTimestamp = filemtime($cacheFile);
    if ($currentTimestamp - $fileTimestamp >= $cacheExpirationTime) {
        unlink($cacheFile);
    }
}

echo "Cache cleared.";
?>
