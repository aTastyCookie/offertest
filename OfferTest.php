<?php 

class OfferTester
{
	private $_apps;

	private $_results;

	public function __construct($csvPath)
	{
		$this->loadAppsFromCsv($csvPath);
		$this->_logFile = fopen(__DIR__ . '/log.txt', 'w+');
		$this->_logErrors = fopen(__DIR__ . '/errors.txt', 'w+');
	}

	public function loadAppsFromCsv($path)
	{
		$apps = array();
		$fp = fopen($path, 'r');
		while (($data = fgetcsv($fp, 1000, ";")) !== FALSE) {
            $apps[] = array(
            	'url' => $data[0],
            	'text' => $data[1]
            );
		}

		$this->_apps = $apps;
	}

	public function getByCurl($url)
	{
	    $this->logString('Open ' . $url);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url); 
	    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HEADER, true); 
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

	    $segments = parse_url($url);
	    $host = $segments['host'];

	    $headers = array(
	    	'Host: ' . $host,
	    );

	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);     
	    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt'); 
	    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 4.4; en-us; Nexus 4 Build/JOP24G) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/F69E90');

	    $content = curl_exec($ch);
	    $info = curl_getinfo($ch);

	    if (!$content) {
	    	$error = curl_error($ch);
	    	$this->logString('Error! ' . $error ? $error : '');
	    	$this->logError($url . ' - ' . $error);
	    	return false;
	    }

	    if ($info['http_code'] !== 200 && $info['http_code'] !== 301 && $info['http_code'] !== 302) {
	    	$this->logString('Error! Response code - ' . $info['http_code']);
	    	$this->logError($url . ' - Response code ' . $info['http_code']);
	    	return false;
	    }

	    return array(
	    	'info' => $info,
	    	'content' => $content
	    ); 
	}

	public function logString($string)
	{
		if (!$this->_logFile) {
			die('Error! Can\'t open log.txt');
		}
		fwrite($this->_logFile, date('Y-m-d H:i:s') . ' - ' . $string . "\n");
	}

	public function logError($string)
	{
	    if (!$this->_logErrors) {
	    	die('Ошибка! Не удалось открыть файл errors.txt');
	    }
	    fwrite($this->_logErrors, date('Y-m-d H:i:s') . ' - ' . $string . "\n");	
	}

	public function checkRedirect($response)
	{
        if ($response['http_code'] == 301 || $response['http_code'] == 302) {
        	return $response['redirect_url'];
        } else {
        	return false;
        }
	}

	public function testAppUrl($app)
	{
		$appInfo['text'] = $app['text'];
		$appInfo['offer_url'] = $app['url'];

        $url = $app['url'];
		$data = $this->getByCurl($url);

		if (!$data) return false;

		if ($data) {
		    $content = $data['content'];
		    $response = $data['info'];
		   
            while ($redirectUrl = $this->checkRedirect($response)) {
            	$this->logString($url . ' redirects to ' . $redirectUrl);
            	$appInfo['redirect_urls'][] = $redirectUrl;
            	
                if (preg_match('|market:\/\/|', $redirectUrl) || preg_match('|http(s)?:\/\/play\.google\.com|', $redirectUrl)) {
                    $this->logString('Finish link detected: ' . $redirectUrl);
                    $appInfo['finish_url'] = $redirectUrl;
                    $appInfo['market_or_google'] = true;
                    $this->_results[] = $appInfo;
                    return true;
                }

                $url = $redirectUrl;

                $data = $this->getByCurl($redirectUrl);
                if ($data) {
                    $content = $data['content'];
		            $response = $data['info'];	
                }
            }

            $this->logString('Finish link detected: ' . $url);
            $appInfo['finish_url'] = $url;
            $appInfo['market_or_google'] = false;
            $this->_results[] = $appInfo;
            return true;
		}
	}

	public function test()
	{
		foreach ($this->_apps as $app) {
			$this->logString($app['url'] . ' - ' . $app['text']);
			$this->testAppUrl($app);
		}

		return $this->_results;
	}
}