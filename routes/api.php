<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PriceQuoteController; // Usaremos este para a funcionalidade principal

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Nova rota para calcular o preço da corrida, incluindo a categoria
Route::post('/price-quote', [PriceQuoteController::class, 'calculate']);

// A rota antiga Route::get('/calculate-route', [RouteController::class, 'getRoute']);
// provavelmente não será mais necessária se /price-quote retornar todos os dados,
// incluindo a geometria para desenhar a rota. Se você não precisar mais dela, pode remover.
// Se decidir manter, certifique-se que RouteController está atualizado para pegar parâmetros dinâmicos.