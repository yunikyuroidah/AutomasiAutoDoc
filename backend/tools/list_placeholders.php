<?php
declare(strict_types=1);

$relative = $argv[1] ?? 'lembar_kegiatan.docx';
$template = dirname(__DIR__, 1) . '/../template/' . $relative;

if (!is_file($template)) {
    fwrite(STDERR, "Template not found: {$template}\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($template) !== true) {
    fwrite(STDERR, "Unable to open template\n");
    exit(1);
}

$xml = $zip->getFromName('word/document.xml');
$zip->close();

if ($xml === false) {
    fwrite(STDERR, "document.xml missing\n");
    exit(1);
}

$dom = new DOMDocument();
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$runs = $xpath->query('//w:r[w:rPr/w:color[@w:val="EE0000"]]');

$index = 1;
foreach ($runs as $run) {
    $texts = [];
    foreach ($run->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $textNode) {
        $texts[] = $textNode->nodeValue;
    }

    $label = trim(implode('', $texts));
    if ($label === '') {
        continue;
    }

    echo $index++, '. ', $label, PHP_EOL;
}
