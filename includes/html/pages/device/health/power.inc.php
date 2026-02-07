<?php

$class = 'power';

// brocade-poe sensors are displayed on port pages instead
$sensor_exclude_types = ['brocade-poe'];

// Check if any non-excluded power sensors exist before rendering
$has_standard_power = \App\Models\Sensor::where('sensor_class', $class)
    ->where('device_id', $device['device_id'])
    ->whereNotIn('sensor_type', $sensor_exclude_types)
    ->exists();

if ($has_standard_power) {
    require 'sensors.inc.php';
} else {
    echo '<div class="alert alert-info">';
    echo 'PoE power sensors for this device are displayed on the <strong>Ports &rarr; PoE</strong> tab.';
    echo '</div>';
}
