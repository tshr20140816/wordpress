<?php


include(dirname(__FILE__) . '/../classes/MyUtils.php');
$mu = new MyUtils();
$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);
error_log($res);
$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
error_log(print_r($matches, TRUE));
$filePath = '/tmp/sample_image.jpg';
$res = file_get_contents($matches[1]);
error_log(strlen($res));
file_put_contents($filePath, $res);
$res = file_get_contents($filePath);
error_log(strlen($res));

$username = getenv('OCRWEBSERVICE_USER');
$license_code = getenv('OCRWEBSERVICE_LICENSE_CODE');


        // Extraction text with English language
        $url = 'http://www.ocrwebservice.com/restservices/processDocument?gettext=true';

        // Extraction text with English and german language using zonal OCR
        // $url = 'http://www.ocrwebservice.com/restservices/processDocument?language=english,german&zone=0:0:600:400,500:1000:150:400';

        // Convert first 5 pages of multipage document into doc and txt
        // $url = 'http://www.ocrwebservice.com/restservices/processDocument?language=english&pagerange=1-5&outputformat=doc,txt';
      
  
        $fp = fopen($filePath, 'r');
        $session = curl_init();

        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_USERPWD, "$username:$license_code");

        curl_setopt($session, CURLOPT_UPLOAD, true);
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($session, CURLOPT_TIMEOUT, 200);
        curl_setopt($session, CURLOPT_HEADER, false);


        // For SSL using
        //curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);

        // Specify Response format to JSON or XML (application/json or application/xml)
        curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
 
        curl_setopt($session, CURLOPT_INFILE, $fp);
        curl_setopt($session, CURLOPT_INFILESIZE, filesize($filePath));

        $result = curl_exec($session);

  	$httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        fclose($fp);

error_log($httpCode);
        if($httpCode == 401) 
	{
           // Please provide valid username and license code
           die('Unauthorized request');
        }

        // Output response
	$data = json_decode($result);
error_log(print_r($data, TRUE));

        if($httpCode != 200) 
	{
	   // OCR error
           die($data->ErrorMessage);
        }

        // Task description
	echo 'TaskDescription:'.$data->TaskDescription."\r\n";

        // Available pages 
	echo 'AvailablePages:'.$data->AvailablePages."\r\n";

        // Extracted text
        echo 'OCRText='.$data->OCRText[0][0]."\r\n";

        // For zonal OCR: OCRText[z][p]    z - zone, p - pages

        // Get First zone from each page 
        //echo 'OCRText[0][0]='.$data->OCRText[0][0]."\r\n";
        //echo 'OCRText[0][1]='.$data->OCRText[0][1]."\r\n";


        // Get second zone from each page
        //echo 'OCRText[1][0]='.$data->OCRText[1][0]."\r\n";
        //echo 'OCRText[1][1]='.$data->OCRText[1][1]."\r\n";


        // Download output file (if outputformat was specified)

        //$url = $data->OutputFileUrl;   
        //$content = file_get_contents($url);
        //file_put_contents('converted_document.doc', $content);

        // End recognition

?>
