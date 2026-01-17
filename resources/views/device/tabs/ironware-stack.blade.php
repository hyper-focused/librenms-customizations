{{-- IronWare Stack Topology Tab --}}
{{-- Displays stack configuration, topology, and per-unit inventory --}}

@extends('layouts.librenmsv1')

@section('title', 'Stack Topology')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Stack Topology - {{ $device->hostname }}</h3>
            </div>
            <div class="panel-body">
                
                @if($topology && $topology->isStacked())
                    {{-- Stack Information Summary --}}
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-blue"><i class="fa fa-layer-group"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Topology</span>
                                    <span class="info-box-number">{{ ucfirst($topology->topology) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-green"><i class="fa fa-server"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Unit Count</span>
                                    <span class="info-box-number">{{ $topology->unit_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-yellow"><i class="fa fa-crown"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Master Unit</span>
                                    <span class="info-box-number">Unit {{ $topology->master_unit ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-aqua"><i class="fa fa-network-wired"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Stack MAC</span>
                                    <span class="info-box-number" style="font-size: 14px;">{{ $topology->stack_mac ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stack Topology Visualization --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Stack Topology Diagram</h4>
                                </div>
                                <div class="panel-body text-center">
                                    @if($topology->isRing())
                                        <div class="stack-diagram-ring">
                                            {{-- Ring topology visualization --}}
                                            <svg width="600" height="400" viewBox="0 0 600 400">
                                                @php
                                                    $count = $topology->unit_count;
                                                    $centerX = 300;
                                                    $centerY = 200;
                                                    $radius = 120;
                                                @endphp
                                                
                                                @foreach($topology->members as $index => $member)
                                                    @php
                                                        $angle = (360 / $count) * $index - 90;
                                                        $x = $centerX + $radius * cos(deg2rad($angle));
                                                        $y = $centerY + $radius * sin(deg2rad($angle));
                                                        $color = $member->isMaster() ? '#3c8dbc' : '#00a65a';
                                                    @endphp
                                                    
                                                    {{-- Unit circle --}}
                                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="40" fill="{{ $color }}" stroke="#000" stroke-width="2"/>
                                                    <text x="{{ $x }}" y="{{ $y }}" text-anchor="middle" dy=".3em" fill="white" font-weight="bold" font-size="16">
                                                        Unit {{ $member->unit_id }}
                                                    </text>
                                                    
                                                    @if($member->isMaster())
                                                        <text x="{{ $x }}" y="{{ $y - 50 }}" text-anchor="middle" fill="#3c8dbc" font-weight="bold">MASTER</text>
                                                    @endif
                                                @endforeach
                                                
                                                {{-- Ring connections --}}
                                                <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $radius }}" fill="none" stroke="#ddd" stroke-width="2" stroke-dasharray="5,5"/>
                                            </svg>
                                        </div>
                                    @elseif($topology->isChain())
                                        <div class="stack-diagram-chain">
                                            {{-- Chain topology visualization --}}
                                            <svg width="800" height="200" viewBox="0 0 800 200">
                                                @foreach($topology->members as $index => $member)
                                                    @php
                                                        $x = 100 + ($index * 150);
                                                        $y = 100;
                                                        $color = $member->isMaster() ? '#3c8dbc' : '#00a65a';
                                                    @endphp
                                                    
                                                    {{-- Unit box --}}
                                                    <rect x="{{ $x - 50 }}" y="{{ $y - 30 }}" width="100" height="60" fill="{{ $color }}" stroke="#000" stroke-width="2" rx="5"/>
                                                    <text x="{{ $x }}" y="{{ $y }}" text-anchor="middle" dy=".3em" fill="white" font-weight="bold" font-size="14">
                                                        Unit {{ $member->unit_id }}
                                                    </text>
                                                    
                                                    @if($member->isMaster())
                                                        <text x="{{ $x }}" y="{{ $y - 45 }}" text-anchor="middle" fill="#3c8dbc" font-weight="bold">MASTER</text>
                                                    @endif
                                                    
                                                    {{-- Connection line to next unit --}}
                                                    @if($index < count($topology->members) - 1)
                                                        <line x1="{{ $x + 50 }}" y1="{{ $y }}" x2="{{ $x + 100 }}" y2="{{ $y }}" stroke="#000" stroke-width="3" marker-end="url(#arrowhead)"/>
                                                    @endif
                                                @endforeach
                                                
                                                {{-- Arrow marker --}}
                                                <defs>
                                                    <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                                                        <polygon points="0 0, 10 3, 0 6" fill="#000"/>
                                                    </marker>
                                                </defs>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stack Members Table --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Stack Members</h4>
                                </div>
                                <div class="panel-body">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Unit ID</th>
                                                <th>Role</th>
                                                <th>State</th>
                                                <th>Model</th>
                                                <th>Serial Number</th>
                                                <th>Firmware</th>
                                                <th>MAC Address</th>
                                                <th>Priority</th>
                                                <th>Last Updated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topology->members as $member)
                                                <tr class="{{ $member->isActive() ? '' : 'warning' }}">
                                                    <td><strong>{{ $member->unit_id }}</strong></td>
                                                    <td>
                                                        <span class="badge {{ $member->getRoleBadgeClass() }}">
                                                            {{ strtoupper($member->role) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $member->getStateBadgeClass() }}">
                                                            {{ ucfirst($member->state) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $member->model ?? 'N/A' }}</td>
                                                    <td><code>{{ $member->serial_number ?? 'N/A' }}</code></td>
                                                    <td>{{ $member->version ?? 'N/A' }}</td>
                                                    <td><code>{{ $member->mac_address ?? 'N/A' }}</code></td>
                                                    <td>{{ $member->priority }}</td>
                                                    <td>{{ $member->updated_at->diffForHumans() }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif($topology && $topology->isStandalone())
                    {{-- Standalone Configuration --}}
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> This device is in <strong>standalone</strong> mode (not stacked).
                    </div>

                    @if($topology->members->isNotEmpty())
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Model</th>
                                <td>{{ $topology->members->first()->model ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Serial Number</th>
                                <td><code>{{ $topology->members->first()->serial_number ?? 'N/A' }}</code></td>
                            </tr>
                            <tr>
                                <th>Firmware Version</th>
                                <td>{{ $topology->members->first()->version ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>MAC Address</th>
                                <td><code>{{ $topology->members->first()->mac_address ?? 'N/A' }}</code></td>
                            </tr>
                        </table>
                    @endif

                @else
                    {{-- No Stack Information --}}
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> No stack topology information available for this device.
                        <br><br>
                        This may indicate:
                        <ul>
                            <li>Stacking is not enabled on the device</li>
                            <li>Device does not support stacking</li>
                            <li>SNMP community string does not have access to stack MIBs</li>
                            <li>Stack discovery has not run yet (wait for next discovery cycle)</li>
                        </ul>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<style>
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        border-radius: 2px;
        margin-bottom: 15px;
    }
    .info-box-icon {
        border-top-left-radius: 2px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0,0,0,0.2);
    }
    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }
    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 18px;
    }
    .info-box-text {
        display: block;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bg-blue { background-color: #3c8dbc !important; color: #fff; }
    .bg-green { background-color: #00a65a !important; color: #fff; }
    .bg-yellow { background-color: #f39c12 !important; color: #fff; }
    .bg-aqua { background-color: #00c0ef !important; color: #fff; }
    
    .stack-diagram-ring, .stack-diagram-chain {
        padding: 20px;
    }
</style>
@endsection
