<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Rota padrão do Laravel que mostra a página de boas-vindas
Route::get('/', function () {
    return view('welcome');
});

// Nossa nova rota para exibir o formulário de entrada do mapa
Route::get('/mapa', function () {
    return view('mapa_input'); // Carrega a view 'resources/views/mapa_input.blade.php'
});

// Você pode adicionar outras rotas para páginas web aqui no futuro