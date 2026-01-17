<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * IronwareStackTopology Model
 *
 * Represents stack topology for IronWare switches (FCX/ICX series)
 * Tracks overall stack configuration: ring, chain, or standalone
 *
 * @property int $id
 * @property int $device_id
 * @property string $topology (ring|chain|standalone|unknown)
 * @property int $unit_count
 * @property int|null $master_unit
 * @property string|null $stack_mac
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class IronwareStackTopology extends Model
{
    protected $table = 'ironware_stack_topology';

    protected $fillable = [
        'device_id',
        'topology',
        'unit_count',
        'master_unit',
        'stack_mac',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'unit_count' => 'integer',
        'master_unit' => 'integer',
    ];

    /**
     * Get the device that owns this stack topology
     *
     * @return BelongsTo
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    /**
     * Get all stack members for this device
     *
     * @return HasMany
     */
    public function members(): HasMany
    {
        return $this->hasMany(IronwareStackMember::class, 'device_id', 'device_id')
                    ->orderBy('unit_id');
    }

    /**
     * Check if this is a stacked configuration
     *
     * @return bool
     */
    public function isStacked(): bool
    {
        return $this->unit_count > 1;
    }

    /**
     * Check if stack is in ring topology
     *
     * @return bool
     */
    public function isRing(): bool
    {
        return $this->topology === 'ring';
    }

    /**
     * Check if stack is in chain topology
     *
     * @return bool
     */
    public function isChain(): bool
    {
        return $this->topology === 'chain';
    }

    /**
     * Check if device is standalone (not stacked)
     *
     * @return bool
     */
    public function isStandalone(): bool
    {
        return $this->topology === 'standalone' || $this->unit_count <= 1;
    }

    /**
     * Get the master unit details
     *
     * @return IronwareStackMember|null
     */
    public function getMasterUnit(): ?IronwareStackMember
    {
        if (!$this->master_unit) {
            return null;
        }

        return $this->members()
                    ->where('unit_id', $this->master_unit)
                    ->first();
    }
}
