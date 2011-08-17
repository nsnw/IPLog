<?

// ws.php
//
// IPLog Web Services XML functions
//
// $Id: ws.php 5 2009-12-21 01:36:19Z andys $

include('iplog.inc.php');

// Time the function
$time_start = microtime(true);

if($_GET['plain'] != "1")
{
	header("Content-type: text/xml");
}
else
{
	echo "<pre>";
}

#$scriptNameLength = strlen($_SERVER['SCRIPT_NAME']);
#$wsQuery = substr($_SERVER['REQUEST_URI'], $scriptNameLength + 1, (strlen($_SERVER['REQUEST_URI']) - ($scriptNameLength + 1)));
$queryArgs = split("/", $_SERVER['REQUEST_URI']);
array_shift($queryArgs);
array_shift($queryArgs);

// Get the first element from the array, which should be our method name
array_shift($queryArgs);
array_shift($queryArgs);
$methodCall = array_shift($queryArgs);
$realMethodCall = $methodCall;

// Wrap the rest up into an array
$argArray = array();
$queryArgsString = "";
foreach ($queryArgs as $argPair) {
	$argParse = split("=", $argPair);
	$argArray[$argParse[0]] = str_replace("%20", " ", $argParse[1]);
	$queryArgsString .= "/".$argParse[0]."=".str_replace("%20", " ", $argParse[1]);
}

$xmlDoc = new DOMDocument("1.0");
//$xsl = $xmlDoc->createProcessingInstruction("xml-stylesheet", "type=\"text/xsl\" href=\"/iplog.xsl\"");
//$xmlDoc->appendChild($xsl);
$rootNode = $xmlDoc->createElement($methodCall."_Result");
$parentNode = $xmlDoc->appendChild($rootNode);

$i = new IPLog();

if(!method_exists($i, $realMethodCall)) {
	$node = $xmlDoc->createElement("queryInfo");
	$queryElement = $parentNode->appendChild($node);
	$queryElement->setAttribute("timeStamp", date("r", time()));
	$queryElement->setAttribute("calledMethod", $methodCall);
	$queryElement->setAttribute("queryArgs", $queryArgsString);
	$queryElement->setAttribute("clientVersion", $_SERVER['HTTP_USER_AGENT']);
	$queryElement->setAttribute("resultsCount", count($output['results']));

	$node = $xmlDoc->createElement("error");
	$errorElement = $parentNode->appendChild($node);
	$errorElement->setAttribute("errorMsg", "No such method '$methodCall'");
} else {
	$output = $i->$realMethodCall($argArray);
	$node = $xmlDoc->createElement("queryInfo");
	$queryElement = $parentNode->appendChild($node);
	$queryElement->setAttribute("timeStamp", date("r", time()));
	$queryElement->setAttribute("calledMethod", $methodCall);
	$queryElement->setAttribute("queryArgs", $queryArgsString);
	$queryElement->setAttribute("clientVersion", $_SERVER['HTTP_USER_AGENT']);
	$queryElement->setAttribute("resultsCount", count($output['results']));

	if($output['debug'])
	{
		$node = $xmlDoc->createElement("debugInfo");
		$debugElement = $parentNode->appendChild($node);
		while($attr = current($output['debug'])) {
			$debugElement->setAttribute(key($output['debug']), $attr);
			next($output['debug']);
		}
	}
	
	$node = $xmlDoc->createElement("elements");
	$elementsNode = $parentNode->appendChild($node);

	if($output['results'])
	{
		foreach($output['results'] as $e) {
			$node = $xmlDoc->createElement("element");
			$element = $elementsNode->appendChild($node);
	
			foreach($e as $attr) {
			#while($attr = current($e)) {
			    if(is_array($attr))
				{
				  foreach($attr as $subattr)
				  {
					$node = $xmlDoc->createElement(key($e));
					foreach($subattr as $subsubattr)
					{
					  $node->setAttribute(key($subattr), $subsubattr);
					  next($subattr);
					}
					$element->appendChild($node);
				  }
				}
				else
				{
				  $element->setAttribute(key($e), $attr);
				}
				next($e);
			}
		}
	}
}
$time_end = microtime(true);
$time = round($time_end - $time_start, 4);

$queryElement->setAttribute("executionTime", $time);

echo $xmlDoc->saveXML();

?>
