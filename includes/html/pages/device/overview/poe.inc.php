<?php
/**
 * PoE Power Budget overview module — displayed in the LEFT column of the device overview page.
 * Shows unit-level PoE capacity/consumption with progress bar and port summary.
 * Renders for any device with PoE-grouped sensors (group prefix 'PoE').
 */

$poeSensors = DeviceCache::getPrimary()->sensors->filter(fn ($s) => str_starts_with($s->group ?? '', 'PoE'));

if ($poeSensors->isNotEmpty()) {
    // Separate unit-level and port-level sensors by group and description keywords
    $unitCapacity = $poeSensors->filter(fn ($s) => $s->group === 'PoE Power Budget' && str_contains($s->sensor_descr, 'Capacity'));
    $unitAvailable = $poeSensors->filter(fn ($s) => $s->group === 'PoE Power Budget' && str_contains($s->sensor_descr, 'Available'));
    $portLimitSensors = $poeSensors->filter(fn ($s) => $s->group === 'PoE Port Power' && str_contains($s->sensor_descr, 'Limit'));
    $portConsumptionSensors = $poeSensors->filter(fn ($s) => $s->group === 'PoE Port Power' && str_contains($s->sensor_descr, 'Consumption'));

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
    // Extract unit numbers from sensor descriptions (e.g., "Unit 1 PoE Capacity" → 1)
    $unitIndexes = $unitCapacity->map(function ($s) {
        preg_match('/Unit\s+(\d+)/i', $s->sensor_descr, $m);

        return (int) ($m[1] ?? 0);
    })->unique()->sort();

    foreach ($unitIndexes as $unitNum) {
        $capSensor = $unitCapacity->first(fn ($s) => str_contains($s->sensor_descr, "Unit {$unitNum}"));
        $availSensor = $unitAvailable->first(fn ($s) => str_contains($s->sensor_descr, "Unit {$unitNum}"));

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
