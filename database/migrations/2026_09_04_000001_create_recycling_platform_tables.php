<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name', 150);
            $table->string('business_type', 50);
            $table->text('materials_handled')->nullable();
            $table->string('nit_ruc', 50)->nullable();
            $table->string('phone_whatsapp', 30);
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('set null');
            $table->string('title', 150);
            $table->text('description');
            $table->string('category', 50);
            $table->string('quantity', 50)->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_donation')->default(false);
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('cloudinary_public_id', 255)->nullable();
            $table->string('image_url', 255);
            $table->string('status', 20)->default('activo');
            $table->timestamps();
        });

        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('machine_telemetry', function (Blueprint $table) {
            $table->id();
            $table->decimal('oil_weight_grams', 10, 2);
            $table->decimal('oil_volume_liters', 10, 2);
            $table->decimal('temperature_celsius', 5, 2);
            $table->integer('agitator_rpm')->default(0);
            $table->boolean('heater_status')->default(false);
            $table->boolean('pump_status')->default(false);
            $table->boolean('trace_detected')->default(false);
            $table->timestamps();
        });

        Schema::create('soap_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->decimal('oil_used_liters', 10, 2);
            $table->decimal('naoh_grams', 10, 2);
            $table->decimal('water_ml', 10, 2);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('status', 30)->default('en_proceso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soap_batches');
        Schema::dropIfExists('machine_telemetry');
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('businesses');
    }
};
