<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas', function (Blueprint $table) {
            $table->id();
            $table->string('nasname');
            $table->string('shortname')->nullable();
            $table->string('type')->default('other');
            $table->unsignedInteger('ports')->nullable();
            $table->string('secret');
            $table->string('server')->nullable();
            $table->string('community')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas');
    }
};
