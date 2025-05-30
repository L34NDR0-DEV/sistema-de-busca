<?php

namespace App\Http\Controllers; // CORREÇÃO APLICADA AQUI

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PriceQuoteController extends Controller
{
    private function getRouteDetailsArray($originLat, $originLon, $destinationLat, $destinationLon)
    {
        $coordinates = "{$originLon},{$originLat};{$destinationLon},{$destinationLat}";
        $osrmBaseUrl = 'http://router.project-osrm.org/route/v1/driving/';
        // Garantir que 'steps=true' está na URL para termos os detalhes dos passos
        $url = "{$osrmBaseUrl}{$coordinates}?overview=full&geometries=geojson&alternatives=true&steps=true";

        try {
            $response = Http::timeout(15)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['routes']) && count($data['routes']) > 0) {
                    return $data['routes']; // Retorna o array de todas as rotas encontradas
                }
            }
            Log::warning('OSRM: Nenhuma rota encontrada ou resposta não bem-sucedida.', ['url' => $url, 'status_code' => $response->status()]);
            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Erro de CONEXÃO ao buscar rota do OSRM: " . $e->getMessage(), ['url' => $url]);
            return null;
        } catch (\Exception $e) {
            Log::error("Erro GERAL ao buscar rota do OSRM: " . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }

    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_lat'      => 'required|numeric|between:-90,90',
            'origin_lon'      => 'required|numeric|between:-180,180',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lon' => 'required|numeric|between:-180,180',
            'category'        => 'required|string|in:economico,conforto,premium',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $originLat = $request->input('origin_lat');
        $originLon = $request->input('origin_lon');
        $destinationLat = $request->input('destination_lat');
        $destinationLon = $request->input('destination_lon');
        $category = $request->input('category');

        $allRouteDetails = $this->getRouteDetailsArray($originLat, $originLon, $destinationLat, $destinationLon);

        if (!$allRouteDetails || count($allRouteDetails) === 0) {
            return response()->json(['error' => 'Não foi possível obter os detalhes da rota para os pontos fornecidos.'], 502);
        }

        $processedRoutes = [];
        $isFirstRouteIteration = true;

        foreach ($allRouteDetails as $routeDetail) {
            if (!isset($routeDetail['distance'], $routeDetail['duration'], $routeDetail['geometry'], $routeDetail['legs'])) {
                Log::warning('OSRM: Rota individual não contém os campos esperados.', ['route_detail_summary' => [
                    'has_distance' => isset($routeDetail['distance']),
                    'has_duration' => isset($routeDetail['duration']),
                    'has_geometry' => isset($routeDetail['geometry']),
                    'has_legs' => isset($routeDetail['legs'])
                ]]);
                continue; 
            }

            $distanceMeters = $routeDetail['distance'];
            $durationSeconds = $routeDetail['duration'];
            $geometry = $routeDetail['geometry'];
            $legs = $routeDetail['legs'];

            $distanceKm = round($distanceMeters / 1000, 2);
            $durationMinutes = round($durationSeconds / 60, 2);

            $pricePerKm = 0; $pricePerMinute = 0; $baseFare = 0; $categoryName = '';
            switch ($category) {
                case 'economico':
                    $pricePerKm = 1.50; $pricePerMinute = 0.25; $baseFare = 3.00; $categoryName = 'Econômico';
                    break;
                case 'conforto':
                    $pricePerKm = 2.20; $pricePerMinute = 0.35; $baseFare = 5.00; $categoryName = 'Conforto';
                    break;
                case 'premium':
                    $pricePerKm = 3.00; $pricePerMinute = 0.50; $baseFare = 7.00; $categoryName = 'Premium';
                    break;
            }
            $calculatedPrice = $baseFare + ($distanceKm * $pricePerKm) + ($durationMinutes * $pricePerMinute);

            $toll_points = [];
            if ($isFirstRouteIteration && isset($legs[0]) && isset($legs[0]['steps'])) {
                foreach ($legs[0]['steps'] as $step) {
                    if (isset($step['name'])) {
                        if (stripos($step['name'], 'Pedágio') !== false || stripos($step['name'], 'Toll') !== false) {
                            if (isset($step['maneuver']) && isset($step['maneuver']['location'])) {
                                $toll_points[] = [
                                    'lat' => $step['maneuver']['location'][1],
                                    'lon' => $step['maneuver']['location'][0],
                                    'name' => $step['name'] 
                                ];
                            }
                        }
                    }
                }
            }

            $processedRoutes[] = [
                'is_primary'       => $isFirstRouteIteration,
                'category_name'    => $categoryName,
                'distance_km'      => $distanceKm,
                'duration_minutes' => $durationMinutes,
                'calculated_price' => round($calculatedPrice, 2),
                'currency_symbol'  => 'R$',
                'geometry'         => $geometry,
                'toll_points'      => $isFirstRouteIteration ? $toll_points : [],
            ];
            $isFirstRouteIteration = false;
        }

        if (count($processedRoutes) === 0) {
             return response()->json(['error' => 'Nenhuma rota válida processada.'], 500);
        }

        return response()->json([
            'message' => 'Cotações calculadas com sucesso!',
            'routes'  => $processedRoutes,
        ]);
    }
}