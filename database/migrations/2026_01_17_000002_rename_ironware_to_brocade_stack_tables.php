<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to rename IronWare stack tables to Brocade stack tables
 *
 * Renames existing tables to maintain consistency with brocade-stack nomenclature:
 * - ironware_stack_topology → brocade_stack_topologies
 * - ironware_stack_members → brocade_stack_members
 *
 * This migration handles the transition from IronWare to Brocade naming convention.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename ironware_stack_topology to brocade_stack_topologies
        if (Schema::hasTable('ironware_stack_topology')) {
            Schema::rename('ironware_stack_topology', 'brocade_stack_topologies');
        }

        // Rename ironware_stack_members to brocade_stack_members
        if (Schema::hasTable('ironware_stack_members')) {
            Schema::rename('ironware_stack_members', 'brocade_stack_members');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the renames
        if (Schema::hasTable('brocade_stack_members')) {
            Schema::rename('brocade_stack_members', 'ironware_stack_members');
        }

        if (Schema::hasTable('brocade_stack_topologies')) {
            Schema::rename('brocade_stack_topologies', 'ironware_stack_topology');
        }
    }
};
