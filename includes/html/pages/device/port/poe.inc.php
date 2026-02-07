<?php
/**
 * PoE tab for individual port pages.
 * Shows PoE limit and consumption sensors with graphs.
 */

use LibreNMS\Enum\Severity;
use LibreNMS\Util\Html;

$sensors = \App\Models\Sensor::where('device_id', $device['device_id'])
    ->where('entPhysicalIndex', $port['ifIndex'])
    ->where('entPhysicalIndex_measured', 'ports')
    ->where('group', 'LIKE', 'PoE%')
    ->orderBy('sensor_descr')
    ->get();

if ($sensors->isEmpty()) {
    echo '<div class="alert alert-info">No PoE data available for this port.</div>';

    return;
}

$limitSensor = $sensors->first(fn ($s) => str_contains($s->sensor_descr, 'Limit'));
$consumptionSensor = $sensors->first(fn ($s) => str_contains($s->sensor_descr, 'Consumption'));

// Summary bar
if ($limitSensor) {
    $limit = $limitSensor->sensor_current;
    $consumption = $consumptionSensor ? $consumptionSensor->sensor_current : 0;
    $usage = $limit > 0 ? round(($consumption / $limit) * 100, 1) : 0;
    $barClass = $usage > 80 ? 'progress-bar-danger' : ($usage > 50 ? 'progress-bar-warning' : 'progress-bar-success');

    echo '<div class="panel panel-default">
            <div class="panel-heading">
              <h3 class="panel-title"><i class="fa fa-bolt" aria-hidden="true"></i> PoE Summary</h3>
            </div>
            <div class="panel-body">
              <div style="display: flex; align-items: center;">
                <div style="flex: 1;">
                  <div class="progress" style="margin-bottom: 0;">
                    <div class="progress-bar ' . $barClass . '" role="progressbar" style="width: ' . $usage . '%; min-width: 2em;">
                      ' . $usage . '%
                    </div>
                  </div>
                </div>
                <div style="margin-left: 10px; white-space: nowrap;">
                  <strong>' . round($consumption, 1) . 'W</strong> / ' . round($limit, 1) . 'W
                </div>
              </div>
            </div>
          </div>';
}

// Individual sensor panels with graphs
foreach ($sensors as $sensor) {
    $sensor_descr = $sensor->sensor_descr;
    $sensor_current = Html::severityToLabel($sensor->currentStatus(), $sensor->formatValue());

    echo "<div class='panel panel-default'>\n" .
         "    <div class='panel-heading'>\n" .
         "        <h3 class='panel-title'>$sensor_descr <div class='pull-right'>$sensor_current";

    if (! is_null($sensor->sensor_limit_low)) {
        echo ' ' . Html::severityToLabel(Severity::Unknown, 'low: ' . $sensor->formatValue('sensor_limit_low'));
    }
    if (! is_null($sensor->sensor_limit_low_warn)) {
        echo ' ' . Html::severityToLabel(Severity::Unknown, 'low_warn: ' . $sensor->formatValue('sensor_limit_low_warn'));
    }
    if (! is_null($sensor->sensor_limit_warn)) {
        echo ' ' . Html::severityToLabel(Severity::Unknown, 'high_warn: ' . $sensor->formatValue('sensor_limit_warn'));
    }
    if (! is_null($sensor->sensor_limit)) {
        echo ' ' . Html::severityToLabel(Severity::Unknown, 'high: ' . $sensor->formatValue('sensor_limit'));
    }

    echo '        </div></h3>' .
         "    </div>\n" .
         "    <div class='panel-body'>\n";

    $graph_array['id'] = $sensor->sensor_id;
    $graph_array['type'] = 'sensor_power';

    include 'includes/html/print-graphrow.inc.php';

    echo "    </div>\n" .
         "</div>\n";
}
