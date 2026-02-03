<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add Brocade stack topology and member tracking tables
 *
 * Enables visual stack topology mapping and per-unit hardware inventory
 * for Brocade/Ruckus FCX and ICX series switches
 *
 * Compatible with Brocade IronWare and Ruckus FastIron platforms
 *
 * Tables:
 * - brocade_stack_topologies: Overall stack configuration
 * - brocade_stack_members: Individual stack member details
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brocade_stack_topologies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('device_id')->unique();
            $table->enum('topology', ['ring', 'chain', 'standalone', 'unknown'])->default('unknown');
            $table->tinyInteger('unit_count')->unsigned()->default(0);
            $table->tinyInteger('master_unit')->unsigned()->nullable();
            $table->string('stack_mac', 17)->nullable();
            $table->timestamps();

            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('cascade');

            $table->index('device_id');
            $table->index('topology');
        });

        Schema::create('brocade_stack_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('device_id');
            $table->tinyInteger('unit_id')->unsigned();
            $table->enum('role', ['master', 'member', 'standalone', 'unknown'])->default('unknown');
            $table->enum('state', ['active', 'remote', 'reserved', 'empty', 'unknown'])->default('unknown');
            $table->string('serial_number', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('version', 64)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->tinyInteger('priority')->unsigned()->default(0);
            $table->timestamps();

            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('cascade');

            $table->unique(['device_id', 'unit_id']);
            $table->index('device_id');
            $table->index('role');
            $table->index('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brocade_stack_members');
        Schema::dropIfExists('brocade_stack_topologies');
    }
};
