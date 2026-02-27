<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributable_attributes', function (Blueprint $table) {
            $table->id();
            // Create morph columns manually to control index names
            $table->string('attributable_type');
            $table->unsignedBigInteger('attributable_id');
            $table->unsignedBigInteger('attribute_id');

            // Separate columns for different data types for better indexing and querying
            $table->text('value_text')->nullable();
            $table->bigInteger('value_number')->nullable();
            $table->decimal('value_decimal', 15, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->time('value_time')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();

            // Keep original value for backward compatibility and fallback
            $table->text('value')->nullable();

            $table->timestamps();

            // Unique constraint to prevent duplicate attribute values per entity
            $table->unique(['attributable_type', 'attributable_id', 'attribute_id'], 'unique_attributable_attribute');

            // Indexes for better query performance
            $table->index(['attributable_type', 'attributable_id'], 'idx_attributable');
            $table->index('attribute_id', 'idx_attribute_id');
            $table->index('value_number', 'idx_value_number');
            $table->index('value_decimal', 'idx_value_decimal');
            $table->index('value_date', 'idx_value_date');
            $table->index('value_datetime', 'idx_value_datetime');
            $table->index('value_boolean', 'idx_value_boolean');

            // Composite index for common queries
            $table->index(['attribute_id', 'value_number'], 'idx_attr_number');
            $table->index(['attribute_id', 'value_date'], 'idx_attr_date');
            $table->index(['attribute_id', 'value_boolean'], 'idx_attr_boolean');

            $table->foreign('attribute_id')
                ->references('id')
                ->on('attributes')
                ->onDelete('cascade');
        });

        // Create prefix indexes for TEXT columns (MySQL requires key length for TEXT/BLOB columns)
        // Only MySQL supports prefix indexes, PostgreSQL and SQLite don't need/support them
        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                DB::statement('CREATE INDEX idx_value_text ON attributable_attributes (value_text(255))');
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }

            try {
                DB::statement('CREATE INDEX idx_attr_text ON attributable_attributes (attribute_id, value_text(255))');
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attributable_attributes');
    }
};
