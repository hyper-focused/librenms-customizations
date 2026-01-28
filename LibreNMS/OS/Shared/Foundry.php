<?php

/**
 * Foundry.php
 *
 * Shared base class for Foundry Networks and Brocade/Ruckus IronWare-based devices
 * Provides common CPU discovery functionality using FOUNDRY-SN-AGENT-MIB
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 Enhanced Stack Discovery
 * @author     LibreNMS Community
 */

namespace LibreNMS\OS\Shared;

use LibreNMS\Device\Processor;
use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;
use LibreNMS\OS;

class Foundry extends OS implements ProcessorDiscovery
{
    /**
     * Discover processors for Foundry-based devices
     * Uses FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable for per-slot/module CPU monitoring.
     * Returns an array of Processor objects (required by ProcessorDiscovery interface).
     *
     * @return array<int, Processor>
     */
    public function discoverProcessors(): array
    {
        $processors = [];

        // Get CPU utilization data from snAgentCpuUtilTable (per-module/slot)
        $cpuData = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable')->table();

        if (empty($cpuData)) {
            return $processors;
        }

        foreach ($cpuData as $index => $data) {
            $util5min = $data['snAgentCpuUtilValue'] ?? null;
            if ($util5min === null || ! is_numeric($util5min)) {
                continue;
            }

            // OID for this index (snAgentCpuUtilValue.INDEX)
            $oid = '.1.3.6.1.4.1.1991.1.1.2.1.1.1.' . $index;

            $processor = Processor::discover(
                'FOUNDRY-SN-AGENT-MIB',
                $this->getDeviceId(),
                $oid,
                (string) $index,
                "Slot {$index}",
                1,
                (int) $util5min,
                null,
                null,
                (string) $index
            );

            if ($processor->isValid()) {
                $processors[] = $processor;
            }
        }

        return $processors;
    }
}