<?php


///////////////////////////PLEASE PROVIDE THE FOLLOWING INPUTS ///////////////////////////
// The URL PerfServlet  
// Make certain it has &version=5
// e.g. $PERFSERVLET_URL="https://demo-websphere61:9443/wasPerfTool/servlet/perfservlet?&version=5";
//$PERFSERVLET_URL=getenv('UPTIME_PERFSERVLET_URL');
$WEBSPHERE_HOST=getenv('UPTIME_HOSTNAME');
$WEBSPHERE_PORT=getenv('UPTIME_WEBSPHERE_PORT');
$WEBSPHERE_SSL=getenv('UPTIME_WEBSPHERE_SSL');
$USERNAME=getenv('UPTIME_USERNAME');
$PASSWORD=getenv('UPTIME_PASSWORD');

// The type of security this script uses to login to WebSphere
// 0 accepts any certificate [Default]
// 1 requires an exported certificate
$SECURITY_LEVEL="0";	

// This can be ommitted if the above is 0
$SSL_CERTIFICATE="demo-websphere61.uptimedemo.pem";

// The JVM you want to monitor
$JVM=getenv('UPTIME_JVM');

// The name of the written XML file
$OUTPUT_XML_FILE="perfservlet-curl.xml";
///////////////////////////////////////////////////////////////////////////////////////////


if ($SECURITY_LEVEL == "" | $OUTPUT_XML_FILE == "" | $WEBSPHERE_HOST == "" | $WEBSPHERE_PORT == "" | $WEBSPHERE_SSL == "") {
	echo "ERROR:Please fill the parameters at the top of the script.\n";
	echo "SECURITY_LEVEL:$SECURITY_LEVEL\n";
	echo "OUTPUT_XML_FILE:$OUTPUT_XML_FILE\n";
	echo "WEBSPHERE_HOST:$WEBSPHERE_HOST\n";
	echo "WEBSPHERE_PORT:$WEBSPHERE_PORT\n";
	echo "WEBSPHERE_SSL:$WEBSPHERE_SSL\n";
	exit(1);
}

if ($WEBSPHERE_SSL == "true") {
	$PERFSERVLET_URL="https://".$WEBSPHERE_HOST.":".$WEBSPHERE_PORT."/wasPerfTool/servlet/perfservlet?&version=5";
} else {
	$PERFSERVLET_URL="http://".$WEBSPHERE_HOST.":".$WEBSPHERE_PORT."/wasPerfTool/servlet/perfservlet?&version=5";
}

$metrics = array(
	"CPUUsage"				=> "/systemModule/CPUUsageSinceLastMeasurement/PerfNumericInfo/@val",
	"HeapSize"				=> "/jvmRuntimeModule/HeapSize/PerfLoadInfo/@currentValue",
	"UsedMemory"			=> "/jvmRuntimeModule/UsedMemory/PerfNumericInfo/@val",
	"ActiveCount"			=> "/transactionModule/ActiveCount/PerfNumericInfo/@val",
	"CommittedCount"		=> "/transactionModule/CommittedCount/PerfNumericInfo/@val",
	"RolledBackCount"		=> "/transactionModule/RolledbackCount/PerfNumericInfo/@val",
);

$multiInstanceMetric["LiveCount"]["/servletSessionsModule/"] = "/LiveCount/PerfLoadInfo/@currentValue";
//$multiInstanceMetric["TimeSinceLastCreated"]["/servletSessionsModule/"] = "/LiveCount/PerfLoadInfo/@timeSinceCreate";
$multiInstanceMetric["ConnectionPoolSize"]["/connectionPoolModule/*/"] = "/PoolSize/PerfLoadInfo/@currentValue";
$multiInstanceMetric["ConnectionPoolFreePoolSize"]["/connectionPoolModule/*/"] = "/FreePoolSize/PerfLoadInfo/@currentValue";
$multiInstanceMetric["ConnectionPoolPercentUsed"]["/connectionPoolModule/*/"] = "/PercentUsed/PerfLoadInfo/@currentValue";
$multiInstanceMetric["ConnectionPoolWaitTime"]["/connectionPoolModule/*/"] = "/WaitTime/PerfStatInfo/@mean";
$multiInstanceMetric["ConnectionPoolCloseCount"]["/connectionPoolModule/*/"] = "/CloseCount/PerfNumericInfo/@val";
$multiInstanceMetric["ConnectionPoolWaitingThreadCount"]["/connectionPoolModule/*/"] = "/WaitingThreadCount/PerfLoadInfo/@currentValue";
$multiInstanceMetric["ConnectionPoolUseTime"]["/connectionPoolModule/*/"] = "/UseTime/PerfStatInfo/@mean";


try {

// Get XML file and write to disk
$ch = curl_init();
$fp=fopen($OUTPUT_XML_FILE,"w");
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch,CURLOPT_URL,$PERFSERVLET_URL); 

if ($USERNAME != "" && $PASSWORD != "") {
	curl_setopt($ch,CURLOPT_USERPWD, $USERNAME . ":" . $PASSWORD);
}

if ($WEBSPHERE_SSL == true) {
	curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
	if ($SECURITY_LEVEL == 1) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);  <--- current accepts any SSL cert
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/" . $SSL_CERTIFICATE);
	} else {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	}
} 

$result = curl_exec($ch);
if(empty($result)) { echo "Error obtaining XML file.<BR>"; /* error: nothing returned */ } else { /* success! */ }
curl_close($ch);
fclose($fp);



$xml = simplexml_load_file($OUTPUT_XML_FILE);
$result = $xml->xpath("//Server/@name");
if (!in_array($JVM,$result)) {
	echo "JVM $JVM does not exist\n";
	exit (2);
}

////  The code with "////" will enable the code to get metrics for each JVM
////foreach ($result as $JVM) {
	////echo $JVM."<BR>";	
	
	// Get single instance metrics
	foreach ($metrics as $metricName=>$metricPath) {
		// Special case for CPUUsage, not per JVM
		if ( $metricName == "CPUUsage") {
			$result3 = $xml->xpath("/".$metricPath);
			echo $metricName." ".$result3[0]."\n";	
		} else {
			$result3 = $xml->xpath("//Server[@name='$JVM']".$metricPath);			
			if ($result3[0] != "") {
				echo $metricName." ".$result3[0]."\n";				
			}
		}
	}
	
	// Get multi-instance metrics
	foreach ($multiInstanceMetric as $metricName => $xpaths) {
		foreach ($xpaths as $multiInstanceNameXPath => $multiInstanceMetricXPath) {			
			// Get instance names
			$getInstancesResults = $xml->xpath("/PerformanceMonitor/Node/Server[@name='$JVM']".$multiInstanceNameXPath."*");
			$instances = array();
			foreach ($getInstancesResults as $instance) {
				$instances[$instance->getName()] = true;
			}
			$instances = array_keys($instances);			
			
			// Output instance metric
			foreach($instances as $instance) {
				$getInstanceMetricResult = $xml->xpath("/PerformanceMonitor/Node/Server[@name='$JVM']".$multiInstanceNameXPath.$instance.$multiInstanceMetricXPath);
				if ($getInstanceMetricResult[0] != "") {
					$instance = str_replace(".", "_", $instance);
					$instance = str_replace(" ", "_", $instance);
					echo $instance.".".$metricName." ".$getInstanceMetricResult[0];
					echo "\n";
				}
			}
			
		}
	}
////}
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>

