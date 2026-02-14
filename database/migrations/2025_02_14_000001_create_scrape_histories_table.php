<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_histories', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64)->unique()->comment('release, jpx 等');
            $table->text('last_value')->nullable()->comment('重複判定用の最後に処理した値');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_histories');
    }
};
