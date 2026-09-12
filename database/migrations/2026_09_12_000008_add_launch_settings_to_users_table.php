<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('project_path', 2000)->nullable();
            $table->string('start_script_path', 2000)->nullable();
            $table->string('stop_script_path', 2000)->nullable();
            $table->unsignedSmallInteger('launch_port')->nullable();
            $table->boolean('open_browser')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'project_path',
                'start_script_path',
                'stop_script_path',
                'launch_port',
                'open_browser',
            ]);
        });
    }
};