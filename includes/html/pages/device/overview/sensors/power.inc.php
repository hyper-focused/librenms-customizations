<?php

$graph_type = 'sensor_power';
$sensor_class = 'power';
$sensor_unit = 'W';
$sensor_type = 'Power';

// brocade-poe sensors are displayed in the left-column PoE overview module
$sensor_exclude_types = ['brocade-poe'];

require 'includes/html/pages/device/overview/generic/sensor.inc.php';
