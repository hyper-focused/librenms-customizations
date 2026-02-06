<?php
/**
 * PoE Power Budget overview module — displayed in the LEFT column of the device overview page.
 * Shows unit-level PoE capacity/consumption with progress bar and port summary.
 * Only renders for devices with brocade-poe sensors.
 */

$poeSensors = DeviceCache::getPrimary()->sensors->where('sensor_type', 'brocade-poe');

if ($poeSensors->isNotEmpty()) {
    // Separate unit-level and port-level sensors
    $unitCapacity = $poeSensors->filter(fn ($s) => str_starts_with($s->sensor_index, 'poe-unit-capacity-'));
    $unitAvailable = $poeSensors->filter(fn ($s) => str_starts_with($s->sensor_index, 'poe-unit-available-'));
    $portLimitSensors = $poeSensors->filter(fn ($s) => str_ends_with($s->sensor_index, '.limit'));
    $portConsumptionSensors = $poeSensors->filter(fn ($s) => str_ends_with($s->sensor_index, '.consumption')
        && ! str_starts_with($s->sensor_index, 'poe-unit-'));

    $portsWithPoE = $portLimitSensors->count();
    $portsConsuming = $portConsumptionSensors->filter(fn ($s) => $s->sensor_current > 0)->count();

    echo '<div class="row">
          <div class="col-md-12">
            <div class="panel panel-default panel-condensed">
              <div class="panel-heading">
                <i class="fa fa-bolt fa-lg icon-theme" aria-hidden="true"></i>
                <strong> PoE Power Budget</strong>
              </div>
              <div class="panel-body">';

    // Unit-level capacity with calculated consumption
    $unitIndexes = $unitCapacity->pluck('sensor_index')->map(fn ($idx) => (int) str_replace('poe-unit-capacity-', '', $idx))->unique()->sort();

    foreach ($unitIndexes as $unitNum) {
        $capSensor = $unitCapacity->firstWhere('sensor_index', "poe-unit-capacity-{$unitNum}");
        $availSensor = $unitAvailable->firstWhere('sensor_index', "poe-unit-available-{$unitNum}");

        $capacity = $capSensor ? $capSensor->sensor_current : 0;
        $available = $availSensor ? $availSensor->sensor_current : $capacity;
        // Consumption = total capacity - remaining available
        $consumption = max(0, $capacity - $available);
        $usage = $capacity > 0 ? round(($consumption / $capacity) * 100, 1) : 0;

        $barClass = $usage > 80 ? 'progress-bar-danger' : ($usage > 50 ? 'progress-bar-warning' : 'progress-bar-success');

        if ($unitIndexes->count() > 1) {
            echo "<strong>Unit {$unitNum}</strong><br />";
        }

        echo '<div style="display: flex; align-items: center; margin-bottom: 8px;">
                <div style="flex: 1;">
                  <div class="progress" style="margin-bottom: 0;">
                    <div class="progress-bar ' . $barClass . '" role="progressbar" style="width: ' . $usage . '%; min-width: 2em;">
                      ' . $usage . '%
                    </div>
                  </div>
                </div>
                <div style="margin-left: 10px; white-space: nowrap;">
                  <strong>' . round($consumption, 1) . 'W</strong> / ' . round($capacity, 1) . 'W
                </div>
              </div>';
    }

    // Port summary
    echo '<div style="margin-top: 8px;">
            <a class="btn btn-default btn-xs" role="button" href="' . \LibreNMS\Util\Url::deviceUrl($device['device_id'], ['tab' => 'ports', 'vars' => 'poe']) . '">
              PoE Ports: <span class="badge">' . $portsWithPoE . '</span>
            </a>
            <span style="margin-left: 5px; color: #777;">
              ' . $portsConsuming . ' consuming power
            </span>
          </div>';

    echo '    </div>
            </div>
          </div>
        </div>';
}
