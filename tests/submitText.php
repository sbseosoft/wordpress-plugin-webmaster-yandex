<?php

// Change settings below
// Website id: http://webmaster.yandex.ru/site/?host=0000000
define('WEBSITE_ID', '0000000');
define('YANDEX_TOKEN', 'XXXXXXXXXXXXX');
define('YANDEX_WEBMASTER_HOST', 'webmaster.yandex.ru');
define('YANDEX_API_REQUEST_TIMEOUT', 30);

function sendTextToYandex($text) {
    $url = "/api/v2/hosts/" . WEBSITE_ID . "/original-texts/";
    $text = urlencode($text);
    $text = "<original-text><content>{$text}</content></original-text>";
    $additionalHeaders = array('Content-Length: ' . strlen($text));
    $curlOptions = array(CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_POSTFIELDS => $text);
    $response = performYandexWebmasterApiRequest($url, 'POST', $curlOptions, $additionalHeaders);
    return $response;
}

function getPage($curlOptions = array()) {
    $ch = curl_init();
    curl_setopt_array($ch, $curlOptions);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    return array('result' => $result, 'info' => $info);
}

function performYandexWebmasterApiRequest($url, $requestType = 'GET', $curlOptions = array(), $additionalHeaders = array()) {
    $headers = array(
        "{$requestType} {$url} HTTP/1.1",
        'Host: webmaster.yandex.ru',
        'Authorization: OAuth ' . YANDEX_TOKEN
    );

    $headers = array_merge($headers, $additionalHeaders);
    $requestOptions = array(
        CURLOPT_URL => 'https://' . YANDEX_WEBMASTER_HOST . $url,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_CONNECTTIMEOUT => YANDEX_API_REQUEST_TIMEOUT,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => 1
    );
    if (count($curlOptions)) {
        foreach ($curlOptions as $curlOption => $curlOptionValue) {
            $requestOptions[$curlOption] = $curlOptionValue;
        }
    }
    $response = getPage($requestOptions);
    return $response;
}

$text = <<<TXT
Founded in the 12th century, the Principality of Muscovy, was able to emerge from over 200 years of 
Mongol domination (13th-15th centuries) and to gradually conquer and absorb surrounding principalities. 
In the early 17th century, a new Romanov Dynasty continued this policy of expansion across Siberia to the 
Pacific. Under PETER I (ruled 1682-1725), hegemony was extended to the Baltic Sea and the country was 
renamed the Russian Empire. During the 19th century, more territorial acquisitions were made in 
Europe and Asia. Defeat in the Russo-Japanese War of 1904-05 contributed to the Revolution of 
1905, which resulted in the formation of a parliament and other reforms. Repeated devastating 
defeats of the Russian army in World War I led to widespread rioting in the major cities of the 
Russian Empire and to the overthrow in 1917 of the imperial household. The communists under 
Vladimir LENIN seized power soon after and formed the USSR. The brutal rule of Iosif STALIN 
(1928-53) strengthened communist rule and Russian dominance of the Soviet Union at a cost of 
tens of millions of lives. The Soviet economy and society stagnated in the following decades until 
General Secretary Mikhail GORBACHEV (1985-91) introduced glasnost (openness) and perestroika 
(restructuring) in an attempt to modernize communism, but his initiatives inadvertently released 
forces that by December 1991 splintered the USSR into Russia and 14 other independent republics. 
Since then, Russia has shifted its post-Soviet democratic ambitions in favor of a centralized 
semi-authoritarian state in which the leadership seeks to legitimize its rule through managed 
national elections, populist appeals by President PUTIN, and continued economic growth. Russia 
has severely disabled a Chechen rebel movement, although violence still occurs throughout the North Caucasus.
TXT;

$response = sendTextToYandex($text);
print "<pre>";
print "\nHTTP CODE: " . $response['info']['http_code'];
print "\nURL:" . $response['info']['url'];
print "\n ------------------ Response from Yandex: ---------------------\n\n";
var_dump($response['result']);
print "</pre>";
