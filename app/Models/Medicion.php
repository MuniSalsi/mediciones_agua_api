<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medicion extends Model
{
    use HasFactory;

    // Define el nombre de la tabla
    protected $table = 'mediciones';

    // Define los campos que se pueden llenar mediante asignación masiva
    protected $fillable = [
        'id',
        'nro_cuenta',
        'ruta',
        'orden',
        'medicion',
        'consumo',
        'fecha',
        'foto_medidor',
        'estado_id'
    ];

    // Define la relación con el modelo Estado
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}
