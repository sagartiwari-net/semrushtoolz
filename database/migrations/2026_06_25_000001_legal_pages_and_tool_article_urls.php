<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('html_body');
            $table->string('seo_title');
            $table->text('seo_description');
            $table->string('seo_keywords')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $map = [
            'semrush-group-buy' => 'tools/semrush-group-buy',
            'ahrefs-group-buy' => 'tools/ahrefs-group-buy',
        ];

        foreach ($map as $from => $to) {
            DB::table('articles')->where('url_path', $from)->update(['url_path' => $to]);
        }
    }

    public function down(): void
    {
        $map = [
            'tools/semrush-group-buy' => 'semrush-group-buy',
            'tools/ahrefs-group-buy' => 'ahrefs-group-buy',
        ];

        foreach ($map as $from => $to) {
            DB::table('articles')->where('url_path', $from)->update(['url_path' => $to]);
        }

        Schema::dropIfExists('legal_pages');
    }
};
