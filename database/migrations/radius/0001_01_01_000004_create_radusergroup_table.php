<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radusergroup', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64);
            $table->string('groupname', 64);
            $table->unsignedInteger('priority')->default(1);
            $table->timestamps();

            $table->index(['username', 'groupname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radusergroup');
    }
};
