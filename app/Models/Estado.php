<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    use HasFactory;
    protected $table = 'estados';
    protected $fillabe = ['tipo'];

    public function mediciones(): HasMany
    {
        return $this->hasMany(Medicion::class, 'estado_id');
    }
}