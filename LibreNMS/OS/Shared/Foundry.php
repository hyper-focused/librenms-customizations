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

use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;
use LibreNMS\OS;

class Foundry extends OS implements ProcessorDiscovery
{
    /**
     * Discover processors for Foundry-based devices
     * Uses FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable for per-slot/module CPU monitoring
     *
     * @return void
     */
    public function discoverProcessors(): void
    {
        $device = $this->getDevice();

        // Get CPU utilization data from snAgentCpuUtilTable
        // This provides per-module/slot CPU utilization
        $cpuData = \SnmpQuery::walk('FOUNDRY-SN-AGENT-MIB::snAgentCpuUtilTable')->table();

        if (!empty($cpuData)) {
            foreach ($cpuData as $index => $data) {
                // Index format: slot.module (e.g., 1.1, 2.1)
                // Use 5-minute average (300 seconds) for stability
                $util5min = $data['snAgentCpuUtilValue'] ?? null;

                if ($util5min !== null) {
                    // Create processor entry
                    // Index represents the slot/module combination
                    $this->discoverProcessor(
                        'FOUNDRY-SN-AGENT-MIB',
                        $index,
                        $util5min,
                        'snAgentCpuUtilValue',
                        "Slot {$index}",
                        1, // Precision
                        $index, // hrDeviceIndex (reuse index)
                        null, // entPhysicalIndex
                        true // perc is oid
                    );
                }
            }
        }
    }
}