<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radreply', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64);
            $table->string('attribute', 64);
            $table->string('op', 2)->default('=');
            $table->text('value');
            $table->timestamps();

            $table->index(['username', 'attribute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radreply');
    }
};
