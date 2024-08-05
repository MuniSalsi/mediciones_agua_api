<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Http\Request;

class EstadoController extends Controller
{
    public function index()
    {
        $estados = Estado::all();

        if ($estados->isEmpty()) {
            return response()->json(['error' => 'No se cargaron mediciones'], 404);
        }

        return response()->json($estados);
    }
}
