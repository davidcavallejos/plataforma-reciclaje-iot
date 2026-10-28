<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 40)->nullable()->after('email');
            $table->string('cargo', 80)->nullable()->after('telefono');
            $table->string('avatar', 40)->nullable()->after('cargo');
            $table->text('bio')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'cargo', 'avatar', 'bio']);
        });
    }
};
