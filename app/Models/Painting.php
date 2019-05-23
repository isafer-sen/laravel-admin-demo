<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Painting extends Model
{
    protected $table = 'demo_paintings';

    protected $fillable = ['title', 'body', 'completed_at'];

    public function painter()
    {
        return $this->belongsTo(Painter::class, 'painter_id');
    }
}