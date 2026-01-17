<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IronwareStackMember Model
 *
 * Represents individual stack members in an IronWare stack
 * Tracks per-unit hardware, role, state, and configuration
 *
 * @property int $id
 * @property int $device_id
 * @property int $unit_id
 * @property string $role (master|member|standalone|unknown)
 * @property string $state (active|remote|reserved|empty|unknown)
 * @property string|null $serial_number
 * @property string|null $model
 * @property string|null $version
 * @property string|null $mac_address
 * @property int $priority
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class IronwareStackMember extends Model
{
    protected $table = 'ironware_stack_members';

    protected $fillable = [
        'device_id',
        'unit_id',
        'role',
        'state',
        'serial_number',
        'model',
        'version',
        'mac_address',
        'priority',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'unit_id' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the device that owns this stack member
     *
     * @return BelongsTo
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    /**
     * Get the stack topology this member belongs to
     *
     * @return BelongsTo
     */
    public function topology(): BelongsTo
    {
        return $this->belongsTo(IronwareStackTopology::class, 'device_id', 'device_id');
    }

    /**
     * Check if this unit is the stack master
     *
     * @return bool
     */
    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    /**
     * Check if this unit is a stack member (not master, not standalone)
     *
     * @return bool
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Check if this unit is standalone (not in a stack)
     *
     * @return bool
     */
    public function isStandalone(): bool
    {
        return $this->role === 'standalone';
    }

    /**
     * Check if this unit is active/operational
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->state === 'active';
    }

    /**
     * Check if this unit is a remote member (in stack, not local)
     *
     * @return bool
     */
    public function isRemote(): bool
    {
        return $this->state === 'remote';
    }

    /**
     * Check if this unit slot is empty (failed or removed)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->state === 'empty';
    }

    /**
     * Get a badge class for the role
     *
     * @return string CSS class for badge
     */
    public function getRoleBadgeClass(): string
    {
        return match ($this->role) {
            'master' => 'badge-primary',
            'member' => 'badge-secondary',
            'standalone' => 'badge-info',
            default => 'badge-default',
        };
    }

    /**
     * Get a badge class for the state
     *
     * @return string CSS class for badge
     */
    public function getStateBadgeClass(): string
    {
        return match ($this->state) {
            'active' => 'badge-success',
            'remote' => 'badge-info',
            'empty' => 'badge-danger',
            'reserved' => 'badge-warning',
            default => 'badge-default',
        };
    }
}
