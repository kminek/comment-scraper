<?php

require 'vendor/autoload.php';

/**
 * Run PHP web server (UNIX)
 */
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo 'Run PHP web server first: php -S localhost:8000' . PHP_EOL;
} else {
    $output = [];
    exec('php -S localhost:8000 >/dev/null 2>&1 & echo $!', $output);
    $pid = (int) $output[0];
    register_shutdown_function(function () use ($pid) {
        echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
        exec('kill ' . $pid);
    });
}

/**
 * Usage
 */
$scraper = new CommentScraper\Scraper();

$scraper->add(new CommentScraper\Source\Dummy(['source' => 'paginated', 'paginated' => true]));
$scraper->add(new CommentScraper\Source\Dummy(['source' => 'single']));

$scraper->callback(function ($comments) {
    dump($comments);
});

$scraper->run();
