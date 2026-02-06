{{-- PoE sub-tab for the Ports listing page --}}
{{-- Shows a table of PoE-enabled ports with limit, consumption, usage, and mini-graphs --}}

@if(($data['poeSensors'] ?? collect())->isEmpty())
    <div class="alert alert-info">No PoE data available for this device.</div>
@else
    {{-- Unit-level PoE summary --}}
    @if(($data['unitSensors'] ?? collect())->isNotEmpty())
        <div class="panel panel-default panel-condensed">
            <div class="panel-heading">
                <i class="fa fa-bolt fa-lg icon-theme" aria-hidden="true"></i>
                <strong> PoE Power Budget</strong>
            </div>
            <div class="panel-body">
                @foreach($data['unitSensors'] as $unitNum => $unit)
                    @php
                        $capacity = $unit['capacity'] ?? 0;
                        $consumption = $unit['consumption'] ?? 0;
                        $usage = $capacity > 0 ? round(($consumption / $capacity) * 100, 1) : 0;
                        $barClass = $usage > 80 ? 'progress-bar-danger' : ($usage > 50 ? 'progress-bar-warning' : 'progress-bar-success');
                    @endphp
                    @if(count($data['unitSensors']) > 1)
                        <strong>Unit {{ $unitNum }}</strong><br />
                    @endif
                    <div style="display: flex; align-items: center; margin-bottom: 5px;">
                        <div style="flex: 1;">
                            <div class="progress" style="margin-bottom: 0;">
                                <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ $usage }}%; min-width: 2em;">
                                    {{ $usage }}%
                                </div>
                            </div>
                        </div>
                        <div style="margin-left: 10px; white-space: nowrap;">
                            <strong>{{ round($consumption, 1) }}W</strong> / {{ round($capacity, 1) }}W
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Per-port PoE table --}}
    <div class="table-responsive">
        <table class="table table-hover table-condensed table-striped">
            <thead>
                <tr>
                    <th>Port</th>
                    <th>Description</th>
                    <th class="text-right">PoE Limit</th>
                    <th class="text-right">Consumption</th>
                    <th style="min-width: 120px;">Usage</th>
                    <th>Graph</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['ports'] as $port)
                    @php
                        $portPoe = ($data['poeSensors'][$port->ifIndex] ?? collect());
                        $poeLimit = $portPoe->first(fn($s) => str_ends_with($s->sensor_index, '.limit'));
                        $poeConsumption = $portPoe->first(fn($s) => str_ends_with($s->sensor_index, '.consumption'));
                        $limitVal = $poeLimit?->sensor_current ?? 0;
                        $consumeVal = $poeConsumption?->sensor_current ?? 0;
                        $usage = $limitVal > 0 ? round(($consumeVal / $limitVal) * 100, 1) : 0;
                        $barClass = $usage > 80 ? 'progress-bar-danger' : ($usage > 50 ? 'progress-bar-warning' : 'progress-bar-success');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ \LibreNMS\Util\Url::portUrl($port) }}">
                                {{ \LibreNMS\Util\Rewrite::shortenIfName($port->getShortLabel()) }}
                            </a>
                        </td>
                        <td>{{ $port->ifAlias }}</td>
                        <td class="text-right">{{ round($limitVal, 1) }}W</td>
                        <td class="text-right">{{ round($consumeVal, 1) }}W</td>
                        <td>
                            <div class="progress" style="margin-bottom: 0;">
                                <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ $usage }}%; min-width: 2em;">
                                    {{ $usage }}%
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($poeConsumption)
                                {!! \LibreNMS\Util\Url::lazyGraphTag([
                                    'id' => $poeConsumption->sensor_id,
                                    'type' => 'sensor_power',
                                    'from' => \App\Facades\LibrenmsConfig::get('time.day'),
                                    'to' => \App\Facades\LibrenmsConfig::get('time.now'),
                                    'width' => 100,
                                    'height' => 20,
                                    'bg' => 'ffffff00',
                                    'legend' => 'no',
                                ]) !!}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
