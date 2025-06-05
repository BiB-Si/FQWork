<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHardwareReportsTable extends Migration
{
    public function up()
    {
        Schema::create('hardware_reports', function (Blueprint $table) {
            $table->id();                                    
            $table->foreignId('build_id')->constrained();   
            $table->json('report_data')->nullable();        
            $table->enum('status', ['parsed', 'failed', 'pending'])->default('pending'); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hardware_reports');
    }
}
