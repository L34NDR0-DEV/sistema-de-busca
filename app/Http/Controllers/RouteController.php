<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Importe o Validator

class RouteController extends Controller
{
    public function getRoute(Request $request) // O $request já estava aqui, vamos usá-lo
    {
        // 1. Validar os parâmetros da requisição
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lon' => 'required|numeric|between:-180,180',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lon' => 'required|numeric|between:-180,180',
        ]);

        // Se a validação falhar, retorna um erro JSON automaticamente
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422); // 422 Unprocessable Entity
        }

        // Pega os dados validados da requisição
        $originLat = $request->query('origin_lat');
        $originLon = $request->query('origin_lon');
        $destinationLat = $request->query('destination_lat');
        $destinationLon = $request->query('destination_lon');

        // 2. Montar a URL da API do OSRM com as coordenadas dinâmicas
        $coordinates = "{$originLon},{$originLat};{$destinationLon},{$destinationLat}";
        $osrmBaseUrl = 'http://router.project-osrm.org/route/v1/driving/';
        $url = "{$osrmBaseUrl}{$coordinates}?overview=full&geometries=geojson&alternatives=false&steps=false";

        try {
            // 3. Fazer a requisição para o OSRM
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['routes']) && count($data['routes']) > 0) {
                    $route = $data['routes'][0];
                    $distanceMeters = $route['distance'];
                    $durationSeconds = $route['duration'];
                    $geometry = $route['geometry'];

                    return response()->json([
                        'message' => 'Rota calculada com sucesso!',
                        'distance_km' => round($distanceMeters / 1000, 2),
                        'duration_minutes' => round($durationSeconds / 60, 2),
                        'geometry' => $geometry,
                    ]);
                } else {
                    // Se o OSRM não encontrar rota mas a chamada for bem-sucedida (raro com coordenadas válidas)
                    return response()->json(['error' => 'Nenhuma rota encontrada entre os pontos fornecidos.'], 404);
                }
            } else {
                Log::error('Erro ao chamar OSRM: Status ' . $response->status() . ' - Corpo: ' . $response->body());
                return response()->json(['error' => 'Falha ao calcular a rota. O serviço de roteirização pode estar indisponível ou os dados são inválidos.'], $response->status());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Erro de conexão com OSRM: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível conectar ao serviço de roteirização.'], 503);
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao calcular rota: ' . $e->getMessage());
            return response()->json(['error' => 'Ocorreu um erro inesperado ao processar a rota.'], 500);
        }
    }
}