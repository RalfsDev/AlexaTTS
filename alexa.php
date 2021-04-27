<?php
/*        TODO

    * manage Errors in Functions

*/

    //Clears a String for the Alexa output
    function clear_string($str, $how = '_'){
        $search = array("ä", "ö", "ü", "ß", "Ä", "Ö",
                  "Ü", "&", "é", "á", "ó",
                  " :)", " :D", " :-)", " :P",
                  " :O", " ;D", " ;)", " ^^",
                  " :|", " :-/", ":)", ":D",
                  ":-)", ":P", ":O", ";D", ";)",
                  "^^", ":|", ":-/", "(", ")", "[", "]",
                  "<", ">", "!", "\"", "§", "$", "%", "&",
                  "/", "(", ")", "=", "?", "`", "´", "*", "'",
                  "_", ":", ";", "²", "³", "{", "}",
                  "\\", "~", "#", "+", ".", ",",
                  "=", ":", "=)");

        $replace = array("ae", "oe", "ue", "ss", "Ae", "Oe",
                   "Ue", "und", "e", "a", "o", "", "",
                   "", "", "", "", "", "", "", "", "",
                   "", "", "", "", "", "", "", "", "",
                   "", "", "", "", "", "", "", "", "",
                   "", "", "", "", "", "", "", "", "",
                   "", "", "", "", "", "", "", "", "",
                   "", "", "", "", "", "", "", "", "", "");

        $str = str_replace($search, $replace, $str);
        $str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
        return $str;
      }
    //Precheck the Cookies.txt file
    function ALEXA_precheckCookies($cookies){
        if (!file_exists($cookies))
            die($cookies.' is not available in the order please attach and follow our instructions:');

        $cookie_file_ok = 0;
        if ( $check_cookie_file = file($cookies) ){
            foreach($check_cookie_file as $check_cookie_file_data){
                if (strpos($check_cookie_file_data,".amazon.de")!==false){
                    $cookie_file_ok = 1;
                }
            }
        }
        if ($cookie_file_ok == 0)
            die('Your '.$cookies.' does not seem to be okay Please check');
    }
    //Function for Alexa get Devices
    function ALEXA_getDevices($cookies){
		$basic_url = 'https://alexa.amazon.de';
		
        $devices = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,''.$basic_url.'/api/devices-v2/device?cached=false');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $get_devices = curl_exec($ch);
        $get_devices_info = curl_getinfo($ch);
        curl_close($ch);

        if($get_devices_info['http_code'] == 200) {
            if(strpos($get_devices,"Anmelden")!==false) {
                echo 'Your cookie information has expired or wrong please renew the cookies.txt If this does not help please delete in the browser all cookies then log in again and then you create a new cookies.txt and upload them! When you use Google Chrome and it isn`t work try it with Firefox ';
                echo '<br><br>';
                die(print_r($get_devices_info));
            }
            
            $user = json_decode($get_devices);

             foreach($user->devices as $mydata) {
                if($mydata->deviceFamily == 'WHA') continue;
                if($mydata->deviceFamily == 'VOX') continue;
                if($mydata->deviceFamily == 'FIRE_TV') continue;
                if($mydata->deviceFamily == 'TABLET') continue;
                if(strpos($mydata->deviceFamily, "MEDIA_DISPLAY") !== false) continue;    //For BOSE, there are 2 devices for every. One "Sonos" and one media_display. You will only need the Sonos one
                
                //add to array
                array_push($devices, array(
                    "AccountName" => clear_string($mydata->accountName),
                    "SerialNumber" => $mydata->serialNumber,
                    "DeviceFamily" => $mydata->deviceFamily,
                    "DeviceType" => $mydata->deviceType,
                    "DeviceOwnerId" => $mydata->deviceOwnerCustomerId,
                ));
            }
        }else{
            echo "an unknown error has occurred";
        }
        return $devices;
    }
    //Function for Alexa tts
    function ALEXA_TTS($cookies, $devices, $device_name, $text_tts){
        //variables
		$basic_url = 'https://alexa.amazon.de';
        $browser = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:88.0) Gecko/20100101 Firefox/88.0";
        $user_lang = 'de,en-US;q=0.7,en;q=0.3';
        
        $cookie_amazon_csrf = 0;
        if ( $get_cookies = file($cookies) ){
            foreach ($get_cookies as $data){
                if (strpos($data,".amazon.de")!==false){
                    if (strpos($data,"csrf")!==false){
                        $explode = explode('csrf',$data);
                        $value = (int)str_replace('-','',$explode[1]);
                        if ($value != 0 ){
                            $cookie_amazon_csrf = $explode[1];
                        }
                    }
                }
            }
        }
        
        //get the device
        foreach($devices as $device){
            if($device_name == $device["AccountName"]) {
                $serialNumber = $device["SerialNumber"];
                $deviceType = $device["DeviceType"];
                $deviceOwnerCustomerId = $device["DeviceOwnerId"];
            }
        }
        
        if ($cookie_amazon_csrf == 0)
            die('Your "csrf" cookie is not available! The request can not be processed - your cookie.txt is wrong');

        $headers = [
            'Host: '.str_replace('https://','',$basic_url).'',
            'User-Agent: '.$browser.'',
            'Accept: */*',
            'Accept-Encoding: deflate, gzip',
            'DNT: 1',
            'Connection: keep-alive',
            'Content-Type: application/json; charset=UTF-8',
            'Accept-Language: '.$user_lang.'',
            'Referer: '.$basic_url.'/spa/index.html',
            'Origin: '.$basic_url.'',
            'csrf: '.(int)$cookie_amazon_csrf.'',
            'Cache-Control: no-cache'
        ];

        $curl_tts = curl_init();
        curl_setopt($curl_tts, CURLOPT_URL,''.$basic_url.'/api/behaviors/preview');
        curl_setopt($curl_tts, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_tts, CURLINFO_HEADER_OUT, 1);
        curl_setopt($curl_tts, CURLOPT_POST, 1);
        curl_setopt($curl_tts, CURLOPT_POSTFIELDS, '{"behaviorId":"PREVIEW","sequenceJson":"{\"@type\":\"com.amazon.alexa.behaviors.model.Sequence\",\"startNode\":{\"@type\":\"com.amazon.alexa.behaviors.model.OpaquePayloadOperationNode\",\"type\":\"Alexa.Speak\",\"operationPayload\":{\"deviceType\":\"'.$deviceType.'\",\"deviceSerialNumber\":\"'.$serialNumber.'\",\"locale\":\"de-DE\",\"customerId\":\"'.$deviceOwnerCustomerId.'\",\"textToSpeak\":\"'.$text_tts.'\"}}}","status":"ENABLED"}');
        curl_setopt($curl_tts, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_tts, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($curl_tts, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($curl_tts, CURLOPT_FOLLOWLOCATION, true);

        //making the curl request
        $send_tts = curl_exec($curl_tts);
        $status_tts = curl_getinfo($curl_tts);

        if ($status_tts['http_code'] == 200){
            echo "<b>Your Text ".$text_tts." was sent to the following device: Name: ".$device_name.", serialNumber: ".$serialNumber.", deviceType: ".$deviceType.", deviceOwnerCustomerId: ".$deviceOwnerCustomerId."</b> ";
        }
        else{
            echo "Unfortunately, an error has leaked!";
            print_r($status_tts);
        }
    }
    
?>


<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Alexa Text to Speech (TTS) by Lets Smart Home</title>
    </head>
    <body>
    <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
         
        //Variablen setzen
        $cookies="cookies.txt";
        
        echo "Your Browser is ".$_SERVER['HTTP_USER_AGENT']."<br>Your User Lang is: ".$_SERVER['HTTP_ACCEPT_LANGUAGE']."<br> When you have any trouble with this script open Alexa.php and replace this two data in the alexa.php -> user_lang and browser <br><br>";
        
        //check if the curl library exists
        if (!function_exists('curl_init')){
            echo "Curl is not enabled or installed on your web server at Synlogy: Webstation -> Php Settings -> Edit Php Profile -> choose the checkbox at extensions & curl";
        }

        //load and check cookies
        ALEXA_precheckCookies($cookies);

        //read devices and print them in a table
        $devices = ALEXA_getDevices($cookies);

        echo "<h1>The following devices are available in your Amazon account</h1>";
        echo '<table border="1"><tr><th>Name</th><th>serialNumber</th><th>deviceFamily</th><th>deviceType</th><th>deviceOwnerCustomerId</th><th>HTTP REQUEST URL</th></tr>';
        foreach($devices as $device){
            echo "<tr>";
            echo "<td>".$device["AccountName"]."</td>";
            echo "<td>".$device["SerialNumber"]."</td>";
            echo "<td>".$device["DeviceFamily"]."</td>";
            echo "<td>".$device["DeviceType"]."</td>";
            echo "<td>".$device["DeviceOwnerId"]."</td>";
            echo "<td>http://".$_SERVER['HTTP_HOST']."/".$_SERVER['SCRIPT_NAME']."?device_name=".$device["AccountName"]."&text_tts=here you can add your text</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<BR><BR>";
		
		//form for direct GUI use
		?>
		<h3>TTS text</h3>
		<form action="alexa.php?" method="get" target="_blank">
		<select name="device_name" id="device_name">
			<?php
				foreach($devices as $dev)
					echo '<option value="'.$dev["AccountName"].'">'.$dev["AccountName"].'</option>';
			?>
		</select><br>
		<textarea name="text_tts" id="text_tts"></textarea><br>
		<input type="hidden" name="GUIAPI" id="GUIAPI" value="true"></input>
		<input type="submit">
		</form>
        
        <?php
        //get values for the tts send
        if (isset($_GET['device_name'])){
            $device_name = $_GET['device_name'];
        }else{
            die('No Device Name (device_name) selected!');
        }

        if (isset($_GET['text_tts'])){
            $text_tts = $_GET['text_tts'];
            if (strlen($text_tts) >= 1000)
                die('You really should not use more than 1000 characters in your text ');
        }else{
            die('No Text (text_tts) selected!');
        }

        //TTS an amazon Senden
        ALEXA_TTS($cookies, $devices, $device_name, $text_tts);
		
		
		if($_GET["GUIAPI"] == "true")
			echo '<script>window.close();</script>';
    ?>
    </body>
</html>
