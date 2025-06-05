<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Build\Build;          // ← импортируем Build

class HardwareReport extends Model
{
    protected $fillable = ['build_id', 'report_data', 'status'];

    protected $casts = [
        'report_data' => 'array', // Laravel превратит JSON в PHP-массив
    ];


    public function build()
    {
        return $this->belongsTo(Build::class);
    }
}
