<?php
//###################################### Ochsner web2com auslesen ######################################
//##                                       Christian Fenzl 2015                                       ##
//######################################################################################################

// Für die Verwendung von cURL muss unter Linux installiert werden:
// sudo apt-get install php5-curl


// require '/var/www/kint/Kint.class.php';

// Error-Handling
    // error_reporting(-1);
    // ini_set('display_errors', 'On');

    $getoid = htmlspecialchars(($_GET["getoid"]));
    $setoid = htmlspecialchars(($_GET["setoid"]));
	$setvalue = htmlspecialchars(($_GET["value"]));
    $host = htmlspecialchars(($_GET["host"]));
    $user = htmlspecialchars(($_GET["user"]));
    $pass = htmlspecialchars(($_GET["pass"]));

	$errors = "";
	if (empty($_GET)) 
		help();
	if (empty($host)) 
		$errors .= "Der Parameter HOST muss angegeben werden.<br>";
	if (empty($getoid) AND empty($setoid))
		$errors .= "Es muss eine der Funktionen GETOID oder SETOID als Parameter übergeben werden.<br>";
	if (!empty($setoid) AND empty($setvalue))
		$errors .= "Bei der Funktion SETOID muss ein Wert als VALUE-Parameter übergeben werden.<br>";
	if (!empty($errors))
			help();
    if (!empty($getoid)) 
        get_oidvalues($host, $user, $pass, $getoid);
    elseif (!empty($setoid))
        set_oidvalue($host, $user, $pass, $setoid, $setvalue);

		
function get_oidvalues($host, $user, $pass, $getoid)
{
    $oids = explode(";", str_replace(",", ";", $getoid ));
	
    $url = "http://$host/ws";
	
    $jsonresponse = "{";

    $ch = curl_init();
    $curlverbose = fopen('php://temp', 'w+');
	
    foreach ($oids as $oid) {
		    $xml_post_string  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
			$xml_post_string .= "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:ns=\"http://ws01.lom.ch/soap/\">";
			$xml_post_string .= "<SOAP-ENV:Body><ns:getDpRequest><ref><oid>$oid</oid><prop/></ref><startIndex>0</startIndex><count>-1</count></ns:getDpRequest></SOAP-ENV:Body></SOAP-ENV:Envelope>";
			
			$headers = array(
                        "Content-Type: text/xml; charset=utf-8",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        "SOAPAction: http://ws01.lom.ch/soap/listDP", 
                        "Content-length: " . strlen($xml_post_string)
                    ); 

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERPWD, $user.":".$pass); 
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $curlverbose);
            $response = curl_exec($ch);
			
			if ($response === FALSE) {
				printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
				htmlspecialchars(curl_error($ch)));
				rewind($verbose);
				$verboseLog = stream_get_contents($verbose);
				echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
			}
			
		$value = get_string_between($response, "<value>", "</value>"); 
        $jsonresponse .= "\n \"$oid\": $value,";
    }

    curl_close($ch);
	echo rtrim($jsonresponse, ",") . "\n}";
} 

function set_oidvalue($host, $user, $pass, $setoid, $value)
{
    $value = str_replace(",", ".", $value);
	
	$url = "http://$host/ws";
	$index = substr($setoid, -1, strrpos($setoid, '/')-1);
 
	
	$ch = curl_init();
    $curlverbose = fopen('php://temp', 'w+');
	
	$xml_post_string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
					"<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:ns=\"http://ws01.lom.ch/soap/\">" .
					"<SOAP-ENV:Body><ns:writeDpRequest><ref><oid>$setoid</oid><prop/></ref>" .
					"<dp><index>$index</index><name/><prop/><desc/><value>$value</value><unit/><timestamp>0</timestamp></dp>" .
					"</ns:writeDpRequest></SOAP-ENV:Body></SOAP-ENV:Envelope>";
	
	$headers = array(
                "Content-Type: text/xml; charset=UTF-8",
				"Accept: text/xml",
				"Cache-Control: no-cache",
				"Pragma: no-cache",
				"SOAPAction: http://ws01.lom.ch/soap/writeDP", 
				"Content-length: " . strlen($xml_post_string)
    ); 

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERPWD, $user.":".$pass); 
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_STDERR, $curlverbose);
	$response = curl_exec($ch);
	
	//if ($response === FALSE) {
		printf("Response: %s<br>\n", $response);
		printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
		htmlspecialchars(curl_error($ch)));
		rewind($verbose);
		$verboseLog = stream_get_contents($verbose);
		echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
	//}
 
	curl_close($ch);

} 

function get_string_between($mystring, $start, $end)
{
    $string = " ".$mystring;
    $ini = strpos($mystring,$start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($mystring,$end,$ini) - $ini;
    return substr($mystring,$ini,$len);
}

function help() 
{
	global $errors;
	$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="de">
<head>
  <meta content="text/html; charset=UTF-8"
 http-equiv="content-type">
  <title>Ochsner web2com f&uuml;r Loxone</title>
  <style title="" type="text/css">
h1 { font-family: Arial,Helvetica,sans-serif;
font-size: 21pt;
font-weight: bold;
}
h2 { font-family: Arial,Helvetica,sans-serif;
font-size: 11pt;
font-weight: bold;
}
body { font-family: Arial,Helvetica,sans-serif;
font-size: 10pt;
}
  </style>
</head>
<body
 style="color: rgb(0, 0, 0); background-color: rgb(255, 255, 239); width: 898px;"
 alink="#000099" link="#000099" vlink="#990099">
<h1>Ochsner web2com vom Loxone Miniserver auslesen und schreiben</h1>
<?php 
	// echo "<br>" . $actual_link . "<br>";
	if ($errors !== "") 
		echo "<h2>Fehler:</h2>" .
			 "$errors;<br>";
?>
<h2>Kurze Funktionsbeschreibung</h2>
<p>Dieses PHP dient als Schnittstelle zwischen dem Ochsner
web2com Modul
und dem Loxone Miniserver.<br>
Das Script unterst&uuml;tzt zwei Funktionen: Auslesen von Werten
und Setzen von Werten. <br>
Eine detaillierte Anleitung dazu findest du <a
 href="https://docs.google.com/document/d/1yLv0sr7XpnxQWRG5UP7Pw57SWApBGzdQgvDRvWiWSgk/edit?usp=sharing"
 rel="me" target="_blank">unter diesem Link</a>.</p>
<h2>Auslesen von Werten (getoid)</h2>
<p><span style="text-decoration: underline;">Aufruf</span>
im <span style="font-weight: bold;">Virtuellen HTTP
Eingang</span> von Loxone:</p>
<p style="font-family: Courier New,Courier,monospace;"><?=$actual_link?>?host=<span
 style="color: rgb(204, 0, 0);">&lt;WEB2COM-IP&gt;</span>&amp;user=<span
 style="color: rgb(204, 0, 0);">&lt;USER&gt;</span>&amp;pass=<span
 style="color: rgb(204, 0, 0);">&lt;PASSWORD&gt;</span>&amp;getoid=<span
 style="color: rgb(204, 0, 0);">&lt;OID1&gt;;&lt;OID2&gt;;&lt;OID3&gt;;...</span></p>
<p><span style="font-family: Courier New,Courier,monospace;">HOST</span>
ist der Hostname oder IP deines web2com Moduls.<br>
<span style="font-family: Courier New,Courier,monospace;">USER</span>
und <span style="font-family: Courier New,Courier,monospace;">PASS</span>
sind die Anmeldedaten an dein web2com Modul.</p>
<span style="font-family: Courier New,Courier,monospace;">OID1;OID2;OID3</span>
usw. sind die abzufragenden Werte (bitte die detaillierte Doku lesen).
Die OIDs d&uuml;rfen durch Komma (,) oder Strichpunkt (;) getrennt
werden. <br>
<br>
<span style="text-decoration: underline;">Ausgabe</span><br>
Die Ausgabe erfolgt im JSON Format. Jeder Wert&nbsp;kann mit
jeweils einem Virtuellen HTTP Eingangs<span
 style="font-style: italic;">-Befehlen </span>abgefragt
werden:<br>
<pre id="line1"><span>{<br><span id="line6"></span> "/1/2/4/106/0": 15.0,<br><span
 id="line7"></span> "/1/2/4/3/0/0": 11.0<br><span
 id="line8"></span>}</span></pre>
<h2>Schreiben von Werten (setoid)</h2>
Aufruf in einem <span style="font-weight: bold;">Virtuellen
Ausgang</span> von Loxone:<br>
<p style="font-family: Courier New,Courier,monospace;"><?=$actual_link?>?host=<span
 style="color: rgb(204, 0, 0);">&lt;WEB2COM-IP&gt;</span>&amp;user=<span
 style="color: rgb(204, 0, 0);">&lt;USER&gt;</span>&amp;pass=<span
 style="color: rgb(204, 0, 0);">&lt;PASSWORD&gt;</span>&amp;setoid=<span
 style="color: rgb(204, 0, 0);">&lt;OID&gt;</span>&amp;value=<span
 style="color: rgb(204, 0, 0);">&lt;VALUE&gt;</span></p>
<p><span style="font-family: Courier New,Courier,monospace;">HOST</span>
ist der Hostname oder IP deines web2com Moduls.<br>
<span style="font-family: Courier New,Courier,monospace;">USER</span>
und <span style="font-family: Courier New,Courier,monospace;">PASS</span>
sind die Anmeldedaten an dein web2com Modul.</p>
<span style="font-family: Courier New,Courier,monospace;">OID</span>
ist die zu setzende Einstellung. Es ist nur eine OID erlaubt.<br>
<span style="font-family: Courier New,Courier,monospace;">VALUE</span>
ist der Wert, der gesetzt werden soll.<br>
<br>
<span style="text-decoration: underline;">Ausgabe</span><br>
Es wird die Antwort der Anfrage an die web2com&nbsp;direkt
angezeigt.<br>
<br>
<h2>F&uuml;r die Community, von der Community</h2>
F&uuml;r die Entwicklung und zum Testen neuer Funktionen mit Loxone kaufe ich des &Ouml;fteren Hardware, die ich selbst
gar nicht ben&ouml;tige. Daraus entstehen Wiki-Artikel und Tipps im Loxforum, sowie Plugins f&uuml;r LoxBerry. <br>
Diese Ochsner Web2Com Schnittstelle ist f&uuml;r Loxone-Benutzer mit dieser W&auml;rmepumpe entstanden, obwohl ich diese selbst nicht besitze.<br>
<br>
Wenn du mit diesem Plugin etwas anfangen kannst, freue ich mich &uuml;ber eine kleine Motivation. Verwendest du das Plugin f&uuml;r einen Kunden von dir,
darf diese Motivation nat&uuml;rlich auch gr&uuml;sser ausfallen. :-)<br>
Christian Fenzl<br><br>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="L8XHCPSHC64RL">
<input type="image" src="https://www.paypalobjects.com/de_DE/AT/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal.">
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1">
</form>

<h2>Hinweise</h2>
Christian Fenzl, christiantf (at) gmx.at 2016. Keine Garantie oder Gew&auml;hrleistung auf korrekte Funktion.<br>
Der Author steht weder mit Loxone noch mit Ochsner W&auml;rmepumpen in Beziehung.
</body>
</html>

<?php
	exit();
}
