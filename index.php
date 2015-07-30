<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function getShortUrl($url)
{
	$segments = parse_url($url);
	$shortUrl = $segments['scheme'] . '://' . $segments['host'];

	return $shortUrl;
}

function generateHtmlFile($data)
{
	$now = time();
	$fp = fopen('htmls/' . $now . '.index.php', 'x+');
    
    $head = "<!DOCTYPE html>\n";
    $head .= "<html>\n<head>\n<meta charset=\"utf-8\" />\n<link type=\"text/css\" rel=\"stylesheet\" href=\"../bootstrap.min.css\" />\n</head>\n";

    fwrite($fp, $head);

    $body = "<body>\n";
    $body .= "<table class=\"table\">\n";
    $body .= "<thead>\n<tr>\n<th>App name</th>\n<th>Redirects</th>\n<th>Finish url</th>\n</tr>\n</thead>\n<tbody>";

    fwrite($fp, $body);

    foreach ($data as $row) {
    	$tr = "<tr" . ($row['market_or_google'] ? " class=\"success\"" : "") . ">";
    	$tr .= "<td><a href=\"" . $row['offer_url'] . "\">" . $row['text'] . "</a></td>\n";
    	$tr .= "<td>";
    	foreach ($row['redirect_urls'] as $url) {
    		$tr .= getShortUrl($url) . "<br><br>";
    	}
    	$tr .= "</td>\n";
    	$tr .= "<td>" . $row['finish_url'] . "</td>\n</tr>\n";

    	fwrite($fp, $tr);
    }

    fwrite($fp, "</tbody>\n</table>\n</body>\n</html>");
    fclose($fp);

    echo '<a href="htmls/' . $now . '.index.php">' . $now . '.index.php</a> - Open this link to see results';
}

require_once 'OfferTest.php';

$tester = new OfferTester('apps.csv');
$data = $tester->test();
generateHtmlFile($data);
die;