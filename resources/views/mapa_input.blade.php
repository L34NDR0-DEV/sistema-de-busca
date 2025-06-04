<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação de Rotas</title>

    {{-- Adicionando o ícone na aba do navegador (favicon) --}}
    {{-- Certifique-se de que o caminho para o ícone está correto em public/images/icone.jpg ou .png --}}
    <link rel="icon" href="{{ asset('images/icone.jpg') }}" type="image/jpeg">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Estilos Globais */
        * { box-sizing: border-box; }
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #181818; /* Fundo escuro */
            color: #eee;
            overflow: hidden;
            transition: background-color 0.3s ease;
        }

        #layout-container {
            display: flex;
            height: 100%;
            width: 100%;
        }

        /* Painel Lateral (Input e Detalhes) */
        .side-panel {
            width: 320px; /* Largura reduzida para o painel */
            min-width: 300px;
            padding: 20px; /* Padding reduzido */
            background-color: #222; /* Preto mais claro para os painéis */
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            overflow-y: auto; /* Permite scroll se o conteúdo for grande */
            display: flex;
            flex-direction: column;
            z-index: 10;
            /* Transição para aparecer/desaparecer */
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
            opacity: 1;
            transform: translateX(0);
        }
        .side-panel.hidden-panel { /* Classe para esconder o painel com animação */
            opacity: 0;
            transform: translateX(-100%);
            position: absolute; /* Para que o outro painel ocupe o espaço */
            pointer-events: none; /* Desabilita cliques no painel escondido */
        }

        .side-panel h1 {
            font-size: 1.5em; /* Título um pouco menor */
            color: #ff8c00; /* Laranja vibrante */
            margin-top: 0;
            margin-bottom: 15px;
            font-weight: 700;
            border-bottom: 2px solid #333; /* Linha divisória mais sutil */
            padding-bottom: 10px;
        }
        #quote-details-panel {
            display: none; /* Inicia oculto, JS vai controlar a visibilidade */
            height: auto;
            max-height: 100%;
            overflow-y: hidden;
            justify-content: space-between;
        }

        /* Campos de Input e Select */
        .input-group { margin-bottom: 15px; position: relative; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #ddd; font-size: 0.85em; }
        .input-group input::placeholder { color: #777; }
        .input-group input, .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            font-size: 0.9em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: #333; /* Fundo escuro para inputs */
            color: #eee; /* Texto claro */
        }
        .input-group input:focus, .input-group select:focus { border-color: #ff8c00; outline: none; box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.25); }
        .input-group select {
            appearance: none; -webkit-appearance: none; -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='20' height='20' fill='%23ff8c00'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 18px; padding-right: 30px;
        }

        /* Sugestões de Endereço */
        .suggestions-list { list-style-type: none; padding: 0; margin: 3px 0 0 0; position: absolute; background-color: #333; border: 1px solid #444; width: 100%; max-height: 150px; overflow-y: auto; z-index: 1001; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
        .suggestions-list li { padding: 8px 10px; cursor: pointer; font-size: 0.85em; border-bottom: 1px solid #444; color: #ddd; }
        .suggestions-list li:last-child { border-bottom: none; }
        .suggestions-list li:hover { background-color: #444; }
        .suggestions-list li.loading-suggestion { color: #777; font-style: italic; padding: 8px 10px; }

        /* Botões de Interação do Mapa */
        .map-interaction-buttons { display: flex; justify-content: space-between; margin-top: 5px; }
        .map-interaction-buttons button { flex-grow: 1; margin-right: 5px; padding: 8px; font-size: 0.75em; background-color: #444; color: #eee; border: none; border-radius: 3px; cursor: pointer; transition: background-color 0.2s; text-transform: uppercase; letter-spacing: 0.5px; }
        .map-interaction-buttons button:last-child { margin-right: 0; }
        .map-interaction-buttons button:hover { background-color: #555; }
        .map-interaction-buttons button.active-mode { background-color: #ff8c00; color: #222; box-shadow: 0 0 6px rgba(255, 140, 0, 0.4); }

        /* Endereços Confirmados no Painel de Cotação */
        .confirmed-address { background-color: #333; padding: 10px; border-radius: 4px; margin-bottom: 12px; font-size: 0.9em; border-left: 3px solid #ff8c00; color: #ddd; word-break: break-word; }
        .confirmed-address strong { color: #eee; }

        /* Resumo dos Resultados da Cotação */
        #results-summary {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #444;
            display: flex;
            flex-direction: column;
            gap: 8px; /* Espaçamento entre os parágrafos */
        }
        #results-summary p {
            margin: 0; /* Remover margem padrão */
            font-size: 0.9em;
            color: #bbb;
            display: flex;
            justify-content: space-between; /* Alinha o texto à esquerda e o valor à direita */
            align-items: baseline;
        }
        #results-summary p span {
            font-weight: 500;
            color: #eee;
            text-align: right; /* Alinha o valor à direita */
            flex-grow: 1; /* Permite que o span ocupe o espaço restante */
            margin-left: 10px; /* Espaçamento entre o label e o valor */
        }

        /* Destaque para Duração e Preço */
        #duration, #calculated_price_display {
            font-size: 1.1em; /* Um pouco maior */
            font-weight: 700; /* Mais forte */
            color: #ff8c00; /* Laranja para destaque */
            display: block; /* Para garantir que o span ocupe a linha */
            transition: color 0.3s ease-in-out; /* Animação suave na cor */
        }
        #calculated_price_display {
             font-size: 1.6em; /* Preço ainda maior */
             color: #28a745; /* Verde vibrante para o preço final */
        }
        .price-changed {
            animation: pricePulse 0.6s ease-out; /* Animação para mudança de preço */
        }
        @keyframes pricePulse {
            0% { transform: scale(1); color: #ff8c00; }
            50% { transform: scale(1.05); color: #ffc107; } /* Cor de pulso */
            100% { transform: scale(1); color: #28a745; }
        }

        #alternative-routes-info { margin-top: 8px; font-size: 0.8em; color: #999; padding: 8px; background-color: #2a2a2a; border-radius: 3px; }

        /* Mensagens de Erro */
        #error_message_quote, #error_message_input {
            color: #ff6b6b; font-weight: 500; margin-top: 10px; background-color: #402b2b;
            border: 1px solid #8b0000; padding: 8px; border-radius: 3px;
            word-break: break-word;
        }
        #error_message_input { margin-top: auto; } /* Empurra o erro para o final no painel de input */

        /* Mapa */
        #mapContainer {
            flex-grow: 1;
            height: 100%;
            cursor: default;
            transition: filter 0.3s ease;
            position: relative; /* Necessário para posicionar o ícone do mapa */
        }
        #mapContainer.blur { filter: blur(5px); }
        #mapContainer.crosshair-cursor { cursor: crosshair !important; }

        /* Ícone de marca d'água no Mapa - REMOVIDO PARA ESTA VERSÃO */
        /* #map-image-icon { ... } */


        /* Spinner de Carregamento */
        .spinner-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 10000; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; }
        .loader { border: 5px solid #555; border-top: 5px solid #ff8c00; border-radius: 50%; width: 40px; height: 40px; animation: spin 0.8s linear infinite; margin-bottom: 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .spinner-text { font-weight: 500; font-size: 1em; color: #ddd; }

        /* Botões de Ação */
        .action-button {
            width: 100%; padding: 10px 12px; font-size: 0.9em; font-weight: 500; cursor: pointer;
            color: #222; border: none; border-radius: 3px; text-transform: uppercase;
            letter-spacing: 0.5px; transition: background-color 0.2s ease-in-out; margin-top: 8px;
            background-color: #ff8c00; /* Laranja forte para botões primários */
        }
        .action-button:hover { background-color: #e07a00; }
        .action-button:disabled { background-color: #555; cursor: not-allowed; color: #999; } /* Estilo para botões desabilitados */
        #showAlternativesBtn { display: none; margin-top: 10px; background-color: #5cb85c; color: #fff; } /* Verde para alternativas */
        #showAlternativesBtn:hover { background-color: #4cae4c; }
    </style>
</head>
<body>

    <div id="layout-container">
        <div id="input-panel" class="side-panel">
            <h1>Definir Rota</h1>
            <div id="address-input-section">
                <div class="address-input-section">
                    <div class="input-group">
                        <label for="origin_address">Origem</label>
                        <input type="text" id="origin_address" name="origin_address" placeholder="Digite a origem..." autocomplete="off">
                        <ul class="suggestions-list" id="origin_suggestions"></ul>
                    </div>
                    <div class="map-interaction-buttons">
                        <button id="setOriginByMapBtn">Marcar no Mapa</button>
                    </div>
                </div>

                <div class="address-input-section">
                    <div class="input-group">
                        <label for="destination_address">Destino</label>
                        <input type="text" id="destination_address" name="destination_address" placeholder="Digite o destino..." autocomplete="off">
                        <ul class="suggestions-list" id="destination_suggestions"></ul>
                    </div>
                    <div class="map-interaction-buttons">
                        <button id="setDestinationByMapBtn">Marcar no Mapa</button>
                    </div>
                </div>
            </div>
            <button id="confirmAddressesBtn" class="action-button primary" style="margin-top: auto;">Confirmar Endereços</button>
            <p id="error_message_input"></p>
        </div>

        <div id="mapContainer">
            {{-- Ícone de mapa como marca d'água no fundo do mapa foi REMOVIDO AQUI --}}
        </div>

        <div id="quote-details-panel" class="side-panel">
            <h1>Detalhes</h1>
            <div class="confirmed-address">
                <strong>Origem:</strong> <span id="confirmed_origin_address_quote">---</span>
            </div>
            <div class="confirmed-address">
                <strong>Destino:</strong> <span id="confirmed_destination_address_quote">---</span>
            </div>

            <div class="input-group">
                <label for="category_select">Modalidade:</label>
                <select id="category_select" name="category">
                    <option value="">-- Selecione --</option>
                    <option value="economico">Econômico</option>
                    <option value="conforto">Conforto</option>
                    <option value="premium">Premium</option>
                </select>
            </div>

            <div id="results-summary">
                <p><span>Modalidade:</span> <span id="category_name_display">---</span></p>
                <p><span>Distância:</span> <span id="distance">---</span> km</p>
                <p><span>Duração:</span> <span id="duration">---</span></p>
                <p><span>Preço:</span> <span id="calculated_price_display">R$ ---</span></p>
            </div>

            <div id="alternative-routes-info"></div>
            <button id="showAlternativesBtn" class="action-button secondary" style="display: none;">Ver Alternativas</button>
            <button id="editAddressesBtnQuote" class="action-button secondary" style="margin-top: 10px;">Editar Endereços</button>
            <p id="error_message_quote"></p>
        </div>
    </div>

    <div id="spinner" class="spinner-overlay" style="display: none;">
        <div class="loader"></div>
        <span class="spinner-text">Carregando...</span>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    @vite('resources/js/mapa_input.js')


</body>
</html>
