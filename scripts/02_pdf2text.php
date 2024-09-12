<?php
$basePath = dirname(__DIR__);

foreach (glob($basePath . '/raw/*/*.pdf') as $pdfFile) {
    $p = pathinfo($pdfFile);
    $txtPath = str_replace('/raw/', '/docs/', $p['dirname']);
    if (!file_exists($txtPath)) {
        mkdir($txtPath, 0777, true);
    }

    exec("/usr/bin/pdftotext {$pdfFile} {$txtPath}/{$p['filename']}.txt");
}
