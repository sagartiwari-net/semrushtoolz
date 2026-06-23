<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->renameColumn('shop_group', 'category');
        });

        DB::table('tools')->whereIn('category', ['semrush', 'ahrefs', 'extension'])->update(['category' => 'seo']);
        DB::table('tools')->where('category', 'other')->orWhereNull('category')->update(['category' => 'seo']);
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->renameColumn('category', 'shop_group');
        });
    }
};
