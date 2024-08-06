<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
                'estado' => $medicion->estado_id,
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
        // Verificar si se recibe un request multipart/form-data
        if (!$request->isMethod('post') || !$request->hasFile('images')) {
            Log::error('Se esperaba un request multipart/form-data con imágenes');
            return response()->json([
                'status' => 'error',
                'message' => 'Se esperaba un request multipart/form-data con imágenes'
            ], 400);
        }

        // Verificar y decodificar JSON
        $medicionesJson = $request->input('mediciones');
        if (!$medicionesJson) {
            Log::error('Se esperaba JSON en el campo "mediciones"');
            return response()->json([
                'status' => 'error',
                'message' => 'Se esperaba JSON en el campo "mediciones"'
            ], 400);
        }

        $data = json_decode($medicionesJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Error al decodificar JSON', ['error' => json_last_error_msg()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al decodificar JSON',
                'error' => json_last_error_msg()
            ], 400);
        }

        // Validar la solicitud
        $validator = Validator::make($data, [
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
            Log::error('Datos de entrada inválidos', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        // Guardar las mediciones
        $responses = [];

        foreach ($data as $medicionData) {
            Log::info('Procesando medición', ['data' => $medicionData]);

            $mappedData = [
                'nro_cuenta' => $medicionData['nroCuenta'],
                'ruta' => $medicionData['ruta'],
                'orden' => $medicionData['orden'],
                'medicion' => $medicionData['medicion'],
                'consumo' => $medicionData['consumo'] ?? null,
                'fecha' => isset($medicionData['fecha']) ? Carbon::parse($medicionData['fecha'])->format('Y-m-d') : null,
                'estado_id' => $medicionData['estadoId'],
            ];

            try {
                $medicion = Medicion::create($mappedData);
                Log::info('Medición guardada', ['medicion_id' => $medicion->id]);

                $responses[] = [
                    'original_id' => $medicionData['id'] ?? null,
                    'created_id' => $medicion->id,
                    'status' => 'created',
                    'subida' => true
                ];
            } catch (\Exception $e) {
                Log::error('Error al guardar medición', ['error' => $e->getMessage()]);
                $responses[] = [
                    'original_id' => $medicionData['id'] ?? null,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'subida' => false
                ];
            }
        }

        // Manejo de archivos subidos
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            foreach ($files as $file) {
                // Extraer el nombre del archivo original
                $originalName = $file->getClientOriginalName();
                // Generar un nombre único para evitar conflictos
                $filename = $originalName;

                // Determinar el número de cuenta correspondiente
                $nroCuenta = null;
                foreach ($data as $medicionData) {
                    // Extraer solo el nombre del archivo de la ruta completa
                    $fotoMedidorName = basename($medicionData['fotoMedidor']);
                    if (isset($medicionData['fotoMedidor']) && $fotoMedidorName === $originalName) {
                        $nroCuenta = $medicionData['nroCuenta'];
                        break;
                    }
                }

                if ($nroCuenta) {
                    // Crear la carpeta si no existe
                    $directory = 'mediciones/' . $nroCuenta;
                    if (!Storage::disk('public')->exists($directory)) {
                        Storage::disk('public')->makeDirectory($directory);
                    }

                    // Guardar el archivo en el directorio correspondiente
                    $path = $file->storeAs($directory, $filename, 'public');

                    Log::info('Archivo guardado', ['filename' => $filename, 'directory' => $directory]);
                } else {
                    Log::error('No se encontró la cuenta para la imagen', ['filename' => $originalName]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Mediciones procesadas',
            'responses' => $responses
        ]);
    }

    // guarda las url:
    // public function upload(Request $request)
    // {
    //     // Verificar si se recibe un request multipart/form-data
    //     if (!$request->isMethod('post') || !$request->hasFile('images')) {
    //         Log::error('Se esperaba un request multipart/form-data con imágenes');
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Se esperaba un request multipart/form-data con imágenes'
    //         ], 400);
    //     }

    //     // Verificar y decodificar JSON
    //     $medicionesJson = $request->input('mediciones');
    //     if (!$medicionesJson) {
    //         Log::error('Se esperaba JSON en el campo "mediciones"');
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Se esperaba JSON en el campo "mediciones"'
    //         ], 400);
    //     }

    //     $data = json_decode($medicionesJson, true);
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         Log::error('Error al decodificar JSON', ['error' => json_last_error_msg()]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error al decodificar JSON',
    //             'error' => json_last_error_msg()
    //         ], 400);
    //     }

    //     // Validar la solicitud
    //     $validator = Validator::make($data, [
    //         '*.id' => 'nullable|integer',
    //         '*.nroCuenta' => 'required|integer',
    //         '*.ruta' => 'required|integer',
    //         '*.orden' => 'required|integer',
    //         '*.medicion' => 'required|numeric',
    //         '*.consumo' => 'nullable|numeric',
    //         '*.fecha' => 'nullable|string',
    //         '*.fotoMedidor' => 'nullable|string',
    //         '*.estadoId' => 'required|integer',
    //     ]);

    //     if ($validator->fails()) {
    //         Log::error('Datos de entrada inválidos', ['errors' => $validator->errors()]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Datos de entrada inválidos',
    //             'errors' => $validator->errors()
    //         ], 422); // Unprocessable Entity
    //     }

    //     // Guardar las mediciones y asignar un marcador de estado para actualización de la URL
    //     $responses = [];
    //     $pendingUpdates = [];

    //     foreach ($data as $medicionData) {
    //         Log::info('Procesando medición', ['data' => $medicionData]);

    //         $mappedData = [
    //             'nro_cuenta' => $medicionData['nroCuenta'],
    //             'ruta' => $medicionData['ruta'],
    //             'orden' => $medicionData['orden'],
    //             'medicion' => $medicionData['medicion'],
    //             'consumo' => $medicionData['consumo'] ?? null,
    //             'fecha' => isset($medicionData['fecha']) ? Carbon::parse($medicionData['fecha'])->format('Y-m-d') : null,
    //             'foto_medidor' => $medicionData['fotoMedidor'] ?? 'pending',  // Marcador de estado para actualizar más tarde
    //             'estado_id' => $medicionData['estadoId'],
    //             'subida' => false
    //         ];

    //         try {
    //             $medicion = Medicion::create($mappedData);
    //             Log::info('Medición guardada', ['medicion_id' => $medicion->id]);

    //             $responses[] = [
    //                 'original_id' => $medicionData['id'] ?? null,
    //                 'created_id' => $medicion->id,
    //                 'status' => 'created',
    //                 'subida' => true
    //             ];

    //             // Guardar el nombre de la foto para la actualización posterior
    //             if (isset($medicionData['fotoMedidor'])) {
    //                 $pendingUpdates[$medicionData['fotoMedidor']] = $medicion->id;
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('Error al guardar medición', ['error' => $e->getMessage()]);
    //             $responses[] = [
    //                 'original_id' => $medicionData['id'] ?? null,
    //                 'status' => 'error',
    //                 'message' => $e->getMessage(),
    //                 'subida' => false
    //             ];
    //         }
    //     }

    //     // Manejo de archivos subidos
    //     if ($request->hasFile('images')) {
    //         $files = $request->file('images');
    //         foreach ($files as $file) {
    //             // Extraer el nombre del archivo original
    //             $originalName = $file->getClientOriginalName();
    //             // Generar un nombre único para evitar conflictos
    //             $filename = time() . '-' . $originalName;
    //             // Guardar el archivo en el directorio 'public/mediciones'
    //             $path = $file->storeAs('public/mediciones', $filename);

    //             // Obtener la URL pública del archivo guardado
    //             $publicUrl = Storage::url('mediciones/' . $filename);
    //             Log::info('Archivo guardado', ['filename' => $filename, 'public_url' => $publicUrl]);

    //             // Actualizar las mediciones con la URL del archivo
    //             foreach ($pendingUpdates as $fotoMedidor => $medicionId) {
    //                 if ($fotoMedidor === $originalName) {
    //                     try {
    //                         Medicion::where('id', $medicionId)->update([
    //                             'foto_medidor' => $publicUrl
    //                         ]);
    //                         Log::info('URL de imagen actualizada', ['medicion_id' => $medicionId, 'public_url' => $publicUrl]);
    //                     } catch (\Exception $e) {
    //                         Log::error('Error al actualizar URL de imagen', ['error' => $e->getMessage()]);
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Mediciones procesadas',
    //         'responses' => $responses
    //     ]);
    // }




    // Funcion vieja:
    // public function upload(Request $request)
    // {
    //     // Verificar si se recibe JSON
    //     if (!$request->isJson()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Se esperaba JSON'
    //         ], 400);
    //     }

    //     // Validar la solicitud
    //     $validator = Validator::make($request->all(), [
    //         '*.id' => 'nullable|integer',
    //         '*.nroCuenta' => 'required|integer',
    //         '*.ruta' => 'required|integer',
    //         '*.orden' => 'required|integer',
    //         '*.medicion' => 'required|numeric',
    //         '*.consumo' => 'nullable|numeric',
    //         '*.fecha' => 'nullable|string',
    //         '*.fotoMedidor' => 'nullable|string',
    //         '*.estadoId' => 'required|integer',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Datos de entrada inválidos',
    //             'errors' => $validator->errors()
    //         ], 422); // Unprocessable Entity
    //     }

    //     $data = $request->all();
    //     $responses = [];

    //     foreach ($data as $medicionData) {
    //         $mappedData = [
    //             'nro_cuenta' => $medicionData['nroCuenta'],
    //             'ruta' => $medicionData['ruta'],
    //             'orden' => $medicionData['orden'],
    //             'medicion' => $medicionData['medicion'],
    //             'consumo' => $medicionData['consumo'] ?? null,
    //             'fecha' => isset($medicionData['fecha']) ? Carbon::parse($medicionData['fecha'])->format('Y-m-d') : null,
    //             'foto_medidor' => $medicionData['fotoMedidor'] ?? null,
    //             'estado_id' => $medicionData['estadoId'],
    //             'subida' => false
    //         ];

    //         try {
    //             $medicion = Medicion::create($mappedData);

    //             $responses[] = [
    //                 'original_id' => $medicionData['id'] ?? null,
    //                 'created_id' => $medicion->id,
    //                 'status' => 'created',
    //                 'subida' => true
    //             ];
    //         } catch (\Exception $e) {
    //             $responses[] = [
    //                 'original_id' => $medicionData['id'] ?? null,
    //                 'status' => 'error',
    //                 'message' => $e->getMessage(),
    //                 'subida' => false
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Mediciones procesadas',
    //         'responses' => $responses
    //     ]);
    // }
}
