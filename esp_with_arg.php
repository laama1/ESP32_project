<?php
// This is a plugin for munin.
// LAama1 16.10.2017


if (isset($argv[1])) {
	$argument1 = $argv[1];
	if ($argument1 == "config") {
		echo "graph_title TemperatureEsp32\n
		graph_vlabel Temp1\n
		Temp1.label Temp1Esp32\n";
	}
    return 0;
} else {
	require_once('espdb.php');
	// TODO lots of things
	echo "Temp1Esp32.value " . 1 . "\n";
}

?>