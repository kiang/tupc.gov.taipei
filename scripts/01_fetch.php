<?php
$basePath = dirname(__DIR__);
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

$browser = new HttpBrowser(HttpClient::create());

$meet1Path = $basePath . '/raw/meet1';
if (!file_exists($meet1Path)) {
    mkdir($meet1Path, 0777, true);
}

for ($i = 1; $i <= 5; $i++) {
    $page1Raw = "{$meet1Path}/page{$i}.html";
    if (!file_exists($page1Raw) || filesize($page1Raw) < 1000) {
        $crawler = $browser->request('GET', "https://tupc.gov.taipei/News.aspx?n=C1E985DC0854084A&sms=C412520428789622&page={$i}&PageSize=200");
        file_put_contents($page1Raw, $crawler->html());
    }

    $c = file_get_contents($page1Raw);
    $pos = strpos($c, '</thead><tbody>');
    $posEnd = strpos($c, '</tbody>', $pos);
    $lines = explode('</tr>', substr($c, $pos, $posEnd - $pos));
    foreach ($lines as $line) {
        $cols = explode('</td>', $line);
        if (!isset($cols[2])) {
            continue;
        }
        $cols[2] = trim(strip_tags($cols[2]));
        $parts = explode('-', $cols[2]);
        $parts[0] += 1911;
        $theDate = strtotime($parts[0] . '-' . $parts[1] . '-' . $parts[2]);
        $parts = explode('"', $cols[1]);
        if (!isset($parts[7])) {
            continue;
        }
        $parts[7] = html_entity_decode($parts[7]);

        $crawler = $browser->request('GET', "https://tupc.gov.taipei/{$parts[7]}");
        $page = $crawler->html();
        $filePos = strpos($page, 'https://www-ws.gov.taipei/Download.ashx');
        while (false !== $filePos) {
            $fileEnd = strpos($page, '"', $filePos);
            $fileLink = html_entity_decode(substr($page, $filePos, $fileEnd - $filePos));
            $titlePos = strpos($page, 'target', $fileEnd);
            $titleParts = explode('"', substr($page, $fileEnd, $titlePos - $fileEnd));
            $titleParts = preg_split('/[\\[\\]\\(\\)]+/', $titleParts[4]);
            $targetFile = $meet1Path . '/' . date('Ymd', $theDate) . '_' . $titleParts[2] . '.pdf';
            if (!file_exists($targetFile)) {
                file_put_contents($targetFile, file_get_contents($fileLink));
            }
            $filePos = strpos($page, 'https://www-ws.gov.taipei/Download.ashx', $fileEnd);
        }
    }
}
