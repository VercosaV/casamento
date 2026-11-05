<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convidados', function (Blueprint $table) {
            $table->boolean('confirma_presenca')->nullable()->default(null)->change();
        });

        // Quem nunca respondeu: false com adultos > 0 (criado por uma contribuição)
        DB::table('convidados')
            ->where('confirma_presenca', false)
            ->where('adultos', '>', 0)
            ->update(['confirma_presenca' => null]);
    }

    public function down(): void
    {
        DB::table('convidados')->whereNull('confirma_presenca')->update(['confirma_presenca' => false]);

        Schema::table('convidados', function (Blueprint $table) {
            $table->boolean('confirma_presenca')->nullable(false)->default(false)->change();
        });
    }
};