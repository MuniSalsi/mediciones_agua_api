<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicionController extends Controller
{
    public function index()
    {
        $listadoMediciones = Medicion::all();

        if ($listadoMediciones->isEmpty()) {
            return response()->json(['error' => 'No se cargaron mediciones'], 404);
        }

        $mediciones = [];
        foreach ($listadoMediciones as $medicion) {
            $data = [
                'id' => $medicion->id,
                'nro_cuenta' => $medicion->nro_cuenta,
                'ruta' => $medicion->ruta,
                'orden' => $medicion->orden,
                'medicion' => $medicion->medicion,
                'consumo' => $medicion->consumo,
                'fecha' => $medicion->fecha,
                'foto_medidor' => $medicion->foto_medidor,
                'estado' => $medicion->estado->tipo,
            ];

            $mediciones[] = $data;
        }

        return response()->json($mediciones);
    }

    public function store(Request $request)
    {
        // Validar los datos de entrada
        $validatedData = $request->validate([
            'mediciones' => 'required|array',
            'mediciones.*.nroCuenta' => 'required|integer',
            'mediciones.*.ruta' => 'required|integer',
            'mediciones.*.orden' => 'required|integer',
            'mediciones.*.medicion' => 'required|numeric',
            'mediciones.*.consumo' => 'nullable|numeric',
            'mediciones.*.fecha' => 'nullable|date_format:Y-m-d',
            'mediciones.*.fotoMedidor' => 'nullable|string',
            'mediciones.*.estadoId' => 'required|integer'
        ]);

        $resultados = []; // Array para almacenar los resultados de cada medición

        try {
            // Recorrer el array de mediciones
            foreach ($validatedData['mediciones'] as $data) {
                // Formatear la fecha correctamente, si está presente
                $formattedDate = isset($data['fecha']) ? Carbon::createFromFormat('Y-m-d', $data['fecha'])->format('Y-m-d') : null;

                // Crear una nueva medición
                $medicion = new Medicion();

                // Asignar los datos validados a la medición
                $medicion->nroCuenta = $data['nroCuenta'];
                $medicion->ruta = $data['ruta'];
                $medicion->orden = $data['orden'];
                $medicion->medicion = $data['medicion'];
                $medicion->consumo = $data['consumo'] ?? null;
                $medicion->fecha = $formattedDate;
                $medicion->fotoMedidor = $data['fotoMedidor'] ?? null;
                $medicion->estadoId = $data['estadoId'];

                // Guardar la medición en la base de datos
                if ($medicion->save()) {
                    $resultados[] = [
                        'id' => $medicion->id, // Obtener el ID generado automáticamente
                        'subida' => true
                    ];
                } else {
                    $resultados[] = [
                        'id' => null,
                        'subida' => false
                    ];
                }
            }

            // Retornar la respuesta exitosa con el estado de cada medición
            return response()->json($resultados, 201);
        } catch (\Exception $e) {
            // Manejar cualquier error inesperado
            return response()->json(['error' => 'Error al crear las mediciones', 'message' => $e->getMessage()], 500);
        }
    }

    public function upload(Request $request)
    {
        // Verificar si se recibe JSON
        if (!$request->isJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Se esperaba JSON'
            ], 400);
        }

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            '*.id' => 'nullable|integer',
            '*.nroCuenta' => 'required|integer',
            '*.ruta' => 'required|integer',
            '*.orden' => 'required|integer',
            '*.medicion' => 'required|numeric',
            '*.consumo' => 'nullable|numeric',
            '*.fecha' => 'nullable|string',
            '*.fotoMedidor' => 'nullable|string',
            '*.estadoId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        $data = $request->all();
        $responses = [];

        foreach ($data as $medicionData) {
            $mappedData = [
                'nro_cuenta' => $medicionData['nroCuenta'],
                'ruta' => $medicionData['ruta'],
                'orden' => $medicionData['orden'],
                'medicion' => $medicionData['medicion'],
                'consumo' => $medicionData['consumo'] ?? null,
                'fecha' => isset($medicionData['fecha']) ? Carbon::parse($medicionData['fecha'])->format('Y-m-d') : null,
                'foto_medidor' => $medicionData['fotoMedidor'] ?? null,
                'estado_id' => $medicionData['estadoId'],
                'subida' => false
            ];

            try {
                $medicion = Medicion::create($mappedData);

                $responses[] = [
                    'original_id' => $medicionData['id'] ?? null,
                    'created_id' => $medicion->id,
                    'status' => 'created',
                    'subida' => true
                ];
            } catch (\Exception $e) {
                $responses[] = [
                    'original_id' => $medicionData['id'] ?? null,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'subida' => false
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Mediciones procesadas',
            'responses' => $responses
        ]);
    }
}
