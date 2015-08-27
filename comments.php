<?php

$sources = [
	'single' => ['comments' => 5, 'perPage' => 5],
	'paginated' => ['comments' => 10, 'perPage' => 3]
];

$source = isset($_GET['source']) ? $_GET['source'] : 'single';
$currentPage = isset($_GET['page']) ? $_GET['page'] : 1;

if (!isset($sources[$source])) {
	throw new Exception('Undefined source');
}

extract($sources[$source]);

$totalPages = ceil($comments / $perPage);

if ($currentPage > $totalPages) {
    header('HTTP/1.0 404 Not Found');
    die('Page not found');
}

$offset = ($currentPage - 1) * $perPage;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comment Scraper Test Page</title>
    <style>
    body { font-family: Arial, sans-serif; }
    .single-comment { background: #ccc; padding: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Comment Scraper Test Page</h1>
    <p>source: <strong><?= $source ?></strong> | page: <strong><?= $currentPage ?></strong></h2>
    <?php for ($i = 1; $i <= $perPage; $i++) : ?>
        <?php $num = $offset + $i; if ($num > $comments) continue; ?>    
        <div class="single-comment">
            <div class="text">comment: <?= $num ?> source: <?= $source ?></div>
        </div>
    <?php endfor ?>    
</body>
</html>