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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let map;
            try {
                map = L.map('mapContainer').setView([-21.3758, -46.5289], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);
                console.log('Mapa Leaflet (OpenStreetMap) inicializado com sucesso.');

                map.createPane('alternativeRoutePane');
                if (map.getPane('alternativeRoutePane')) {
                    map.getPane('alternativeRoutePane').style.zIndex = 620;
                    map.getPane('alternativeRoutePane').style.pointerEvents = 'auto';
                } else {
                    console.error('Falha ao criar alternativeRoutePane');
                }

                map.createPane('primaryRoutePane');
                if (map.getPane('primaryRoutePane')) {
                    map.getPane('primaryRoutePane').style.zIndex = 630;
                    map.getPane('primaryRoutePane').style.pointerEvents = 'auto';
                } else {
                    console.error('Falha ao criar primaryRoutePane');
                }

            } catch (e) {
                console.error('ERRO CRÍTICO AO INICIALIZAR O MAPA LEAFLET:', e);
                const mapDiv = document.getElementById('mapContainer');
                if(mapDiv) mapDiv.innerHTML = '<p style="text-align:center; padding-top:50px; color:red;">Não foi possível carregar o mapa. Verifique o console (F12).</p>';
                return;
            }

            const originAddressInput = document.getElementById('origin_address');
            const destinationAddressInput = document.getElementById('destination_address');
            const originSuggestionsUl = document.getElementById('origin_suggestions');
            const destinationSuggestionsUl = document.getElementById('destination_suggestions');
            const setOriginByMapBtn = document.getElementById('setOriginByMapBtn');
            const setDestinationByMapBtn = document.getElementById('setDestinationByMapBtn');
            const confirmAddressesBtn = document.getElementById('confirmAddressesBtn');
            const errorMessageInputP = document.getElementById('error_message_input');

            const inputPanel = document.getElementById('input-panel');
            const quoteDetailsPanel = document.getElementById('quote-details-panel');
            const confirmedOriginSpanQuote = document.getElementById('confirmed_origin_address_quote');
            const confirmedDestinationSpanQuote = document.getElementById('confirmed_destination_address_quote');
            const categorySelect = document.getElementById('category_select');
            const categoryNameDisplay = document.getElementById('category_name_display');
            const calculatedPriceDisplay = document.getElementById('calculated_price_display');
            const editAddressesBtnQuote = document.getElementById('editAddressesBtnQuote');
            const alternativeRoutesInfoDiv = document.getElementById('alternative-routes-info');
            const showAlternativesBtn = document.getElementById('showAlternativesBtn');
            const errorMessageQuoteP = document.getElementById('error_message_quote');

            const distanceSpan = document.getElementById('distance');
            const durationSpan = document.getElementById('duration');
            const spinnerDiv = document.getElementById('spinner');

            let mapClickMode = null;

            // Ícones dos Marcadores (Pinheiros) - Ajustados para Laranja
            const greenIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            const orangeIcon = L.icon({ // Novo ícone laranja para o destino
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            const tollMarkerIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/2972/2972481.png',
                iconSize: [24, 24],
                iconAnchor: [12, 24],
                popupAnchor: [0, -24]
            });
            const routeColors = ['#ff8c00', '#00bcd4', '#9c27b0', '#ffeb3b', '#4caf50']; // Laranja, Ciano, Roxo, Amarelo, Verde para alternativas

            let activeRouteLayers = [];
            let activeTollMarkers = [];
            let originMapMarker = null;
            let destinationMapMarker = null;
            let originCoords = null;
            let destinationCoords = null;
            let allFetchedRoutesData = [];

            function setLoading(isLoading, message = "Buscando informações...") {
                const spinnerSpan = spinnerDiv.querySelector('.spinner-text');
                if (spinnerSpan) spinnerSpan.textContent = message;
                spinnerDiv.style.display = isLoading ? 'flex' : 'none';
                
                // Adiciona ou remove blur do mapa
                if (map) {
                    const mapDiv = document.getElementById('mapContainer');
                    if (mapDiv) {
                        if (isLoading) {
                            mapDiv.classList.add('blur');
                        } else {
                            mapDiv.classList.remove('blur');
                        }
                    }
                }

                // Desabilitar/habilitar controles
                confirmAddressesBtn.disabled = isLoading;
                originAddressInput.disabled = isLoading;
                destinationAddressInput.disabled = isLoading;
                setOriginByMapBtn.disabled = isLoading;
                setDestinationByMapBtn.disabled = isLoading;
                categorySelect.disabled = isLoading;
                editAddressesBtnQuote.disabled = isLoading;
                showAlternativesBtn.disabled = isLoading;
            }

            function debounce(func, delay) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), delay);
                };
            }

            function clearQuoteDetailsAndMapRoutes() {
                if (!map) return;
                distanceSpan.textContent = '---';
                durationSpan.textContent = '---';
                categoryNameDisplay.textContent = '---';
                calculatedPriceDisplay.textContent = 'R$ ---';
                calculatedPriceDisplay.classList.remove('price-changed'); // Remove a classe de animação
                errorMessageQuoteP.textContent = '';
                alternativeRoutesInfoDiv.innerHTML = '';
                showAlternativesBtn.style.display = 'none';

                activeRouteLayers.forEach(layerObj => {
                    if(map.hasLayer(layerObj.polyline)) map.removeLayer(layerObj.polyline);
                });
                activeRouteLayers = [];
                activeTollMarkers.forEach(marker => map.removeLayer(marker));
                activeTollMarkers = [];
            }

            // Funções de transição
            function hidePanel(panelElement, callback) {
                panelElement.classList.add('hidden-panel');
                panelElement.addEventListener('transitionend', function handler() {
                    panelElement.style.display = 'none';
                    panelElement.removeEventListener('transitionend', handler);
                    if (callback) callback();
                });
            }

            function showPanel(panelElement) {
                panelElement.style.display = 'flex';
                // Força reflow para garantir que a transição ocorra
                void panelElement.offsetWidth;
                panelElement.classList.remove('hidden-panel');
            }

            function switchToInputPanel() {
                hidePanel(quoteDetailsPanel, function() {
                    showPanel(inputPanel);
                    clearQuoteDetailsAndMapRoutes();
                    confirmAddressesBtn.disabled = !(originCoords && destinationCoords);
                });
            }

            function switchToQuotePanel() {
                 if (originCoords && destinationCoords) {
                    hidePanel(inputPanel, function() {
                        showPanel(quoteDetailsPanel);
                        confirmedOriginSpanQuote.textContent = originCoords.displayName || `${originCoords.lat.toFixed(5)}, ${originCoords.lon.toFixed(5)}`;
                        confirmedDestinationSpanQuote.textContent = destinationCoords.displayName || `${destinationCoords.lat.toFixed(5)}, ${destinationCoords.lon.toFixed(5)}`;
                        clearQuoteDetailsAndMapRoutes(); // Limpa para nova cotação
                        categorySelect.value = "";
                        errorMessageInputP.textContent = "";
                    });
                } else {
                     errorMessageInputP.textContent = "Por favor, defina origem e destino válidos.";
                }
            }

            editAddressesBtnQuote.addEventListener('click', switchToInputPanel);
            confirmAddressesBtn.addEventListener('click', function() {
                 if (originCoords && destinationCoords) {
                    switchToQuotePanel();
                } else {
                    errorMessageInputP.textContent = "Defina a origem e o destino antes de continuar.";
                }
            });

            async function fetchAndDisplaySuggestions(query, suggestionsUl, inputField, type) {
                if (query.length < 3) {
                    suggestionsUl.innerHTML = '';
                    suggestionsUl.style.display = 'none';
                    if (type === 'origin') {
                        originCoords = null;
                        if(originMapMarker && map && map.hasLayer(originMapMarker)) { map.removeLayer(originMapMarker); originMapMarker = null; }
                    } else {
                        destinationCoords = null;
                        if(destinationMapMarker && map && map.hasLayer(destinationMapMarker)) { map.removeLayer(destinationMapMarker); destinationMapMarker = null; }
                    }
                    confirmAddressesBtn.disabled = !(originCoords && destinationCoords);
                    if (!originCoords || !destinationCoords) {
                        quoteDetailsPanel.style.display = 'none';
                        if(inputPanel.style.display === 'none' && quoteDetailsPanel.style.display === 'none') inputPanel.style.display = 'flex';
                    }
                    return;
                }
                suggestionsUl.innerHTML = '<li class="loading-suggestion">Buscando...</li>';
                suggestionsUl.style.display = 'block';
                try {
                    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&countrycodes=br&limit=5`;
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Falha na busca de sugestões');
                    const data = await response.json();
                    suggestionsUl.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            let addressString = item.address.road || '';
                            if (item.address.house_number) { addressString += `, ${item.address.house_number}`; }
                            const city = item.address.city || item.address.town || item.address.village || item.address.suburb || '';
                            if (city) { addressString += ` - ${city}`; }
                            if (!addressString && item.display_name) { addressString = item.display_name.split(',').slice(0,3).join(',');}
                            const li = document.createElement('li');
                            li.textContent = addressString;
                            li.addEventListener('click', () => {
                                inputField.value = addressString;
                                const selectedCoords = { lat: parseFloat(item.lat), lon: parseFloat(item.lon), displayName: addressString };
                                if (type === 'origin') {
                                    originCoords = selectedCoords;
                                    updateMapMarker('origin', selectedCoords.lat, selectedCoords.lon, selectedCoords.displayName);
                                } else {
                                    destinationCoords = selectedCoords;
                                    updateMapMarker('destination', selectedCoords.lat, selectedCoords.lon, selectedCoords.displayName);
                                }
                                suggestionsUl.innerHTML = '';
                                suggestionsUl.style.display = 'none';
                                confirmAddressesBtn.disabled = !(originCoords && destinationCoords);
                            });
                            suggestionsUl.appendChild(li);
                        });
                    } else { suggestionsUl.innerHTML = '<li>Nenhuma sugestão encontrada.</li>'; }
                } catch (error) { console.error('Erro ao buscar sugestões:', error); suggestionsUl.innerHTML = '<li>Erro ao buscar.</li>';}
            }

            originAddressInput.addEventListener('input', debounce(function() { fetchAndDisplaySuggestions(originAddressInput.value, originSuggestionsUl, originAddressInput, 'origin'); }, 500));
            destinationAddressInput.addEventListener('input', debounce(function() { fetchAndDisplaySuggestions(destinationAddressInput.value, destinationSuggestionsUl, destinationAddressInput, 'destination'); }, 500));
            document.addEventListener('click', function(event) {
                if (!originAddressInput.contains(event.target) && !originSuggestionsUl.contains(event.target) &&
                    !destinationAddressInput.contains(event.target) && !destinationSuggestionsUl.contains(event.target) ) {
                    originSuggestionsUl.style.display = 'none';
                    destinationSuggestionsUl.style.display = 'none';
                }
            });

            async function reverseGeocode(lat, lon) {
                setLoading(true, "Obtendo endereço do ponto...");
                try {
                    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`;
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Falha na geocodificação reversa');
                    const data = await response.json();
                    if (data && data.display_name) {
                        let addressString = data.address.road || '';
                        if (data.address.house_number) { addressString += `, ${data.address.house_number}`; }
                        const city = data.address.city || data.address.town || data.address.village || data.address.suburb || '';
                        if (city && addressString) { addressString += ` - ${city}`; }
                        else if (city) { addressString = city; }
                        return addressString || data.display_name;
                    } else { return `${lat.toFixed(5)}, ${lon.toFixed(5)}`; }
                } catch (error) { console.error("Erro na geocodificação reversa:", error); errorMessageInputP.textContent = "Não foi possível obter o endereço para o ponto clicado."; return `${lat.toFixed(5)}, ${lon.toFixed(5)}`; }
                finally { setLoading(false); }
            }

            function updateMapMarker(type, lat, lon, displayName) {
                if (!map) { console.error("Objeto map não inicializado em updateMapMarker"); return; }
                const icon = type === 'origin' ? greenIcon : orangeIcon; // Usa orangeIcon para destino
                let currentMarker = (type === 'origin') ? originMapMarker : destinationMapMarker;

                if (currentMarker) {
                    currentMarker.setLatLng([lat, lon]).setPopupContent(`<b>${type === 'origin' ? 'Origem' : 'Destino'}:</b><br>${displayName}`).update();
                } else {
                    currentMarker = L.marker([lat, lon], { icon: icon }).addTo(map).bindPopup(`<b>${type === 'origin' ? 'Origem' : 'Destino'}:</b><br>${displayName}`);
                    if (type === 'origin') originMapMarker = currentMarker; else destinationMapMarker = currentMarker;
                }
                map.flyTo([lat, lon], map.getZoom() < 15 ? 15 : map.getZoom(), {duration: 1});
                if (currentMarker) currentMarker.openPopup();
                confirmAddressesBtn.disabled = !(originCoords && destinationCoords);
            }

            setOriginByMapBtn.addEventListener('click', () => {
                mapClickMode = 'origin';
                const mapDiv = document.getElementById('mapContainer');
                if (mapDiv) mapDiv.classList.add('crosshair-cursor');
                setOriginByMapBtn.classList.add('active-mode');
                setDestinationByMapBtn.classList.remove('active-mode');
                errorMessageInputP.textContent = 'Clique no mapa para definir a ORIGEM.';
            });
            setDestinationByMapBtn.addEventListener('click', () => {
                mapClickMode = 'destination';
                const mapDiv = document.getElementById('mapContainer');
                if (mapDiv) mapDiv.classList.add('crosshair-cursor');
                setDestinationByMapBtn.classList.add('active-mode');
                setOriginByMapBtn.classList.remove('active-mode');
                errorMessageInputP.textContent = 'Clique no mapa para definir o DESTINO.';
            });

            if (map) {
                map.on('click', async function(e) {
                    if (!mapClickMode) return;
                    const { lat, lng } = e.latlng;
                    const address = await reverseGeocode(lat, lng);
                    const coordsData = { lat: lat, lon: lng, displayName: address };

                    if (mapClickMode === 'origin') {
                        originAddressInput.value = address;
                        originCoords = coordsData;
                        updateMapMarker('origin', lat, lng, address);
                    } else if (mapClickMode === 'destination') {
                        destinationAddressInput.value = address;
                        destinationCoords = coordsData;
                        updateMapMarker('destination', lat, lng, address);
                    }

                    const mapDiv = document.getElementById('mapContainer');
                    if (mapDiv) mapDiv.classList.remove('crosshair-cursor');
                    setOriginByMapBtn.classList.remove('active-mode');
                    setDestinationByMapBtn.classList.remove('active-mode');
                    errorMessageInputP.textContent = '';
                    mapClickMode = null;
                    confirmAddressesBtn.disabled = !(originCoords && destinationCoords);
                });
            }

            categorySelect.addEventListener('change', function() {
                if (this.value && originCoords && destinationCoords) {
                    fetchAndDisplayPriceQuote(this.value);
                } else if (!this.value) {
                    clearQuoteDetailsAndMapRoutes(); // Limpa se "--Selecione--" for escolhido
                }
            });

            // Função para display de rota e preço (com animação)
            function displayRouteDetailsInPanel(routeData) {
                if (!routeData || !map) return;

                // Animação para o preço
                const oldPrice = parseFloat(calculatedPriceDisplay.textContent.replace('R$ ', '').replace(',', '.'));
                const newPrice = routeData.calculated_price;

                categoryNameDisplay.textContent = routeData.category_name;
                distanceSpan.textContent = routeData.distance_km;
                durationSpan.textContent = routeData.duration_formatted; // Usa o campo formatado

                calculatedPriceDisplay.textContent = `${routeData.currency_symbol || 'R$'} ${newPrice.toFixed(2).replace('.', ',')}`; // Formato BR

                if (oldPrice !== newPrice) {
                    calculatedPriceDisplay.classList.remove('price-changed'); // Resetar animação
                    void calculatedPriceDisplay.offsetWidth; // Força reflow para reiniciar animação
                    calculatedPriceDisplay.classList.add('price-changed');
                }

                activeRouteLayers.forEach(layerObj => {
                    if (!map.hasLayer(layerObj.polyline)) return;
                    const isSelected = layerObj.id === routeData.id;
                    layerObj.polyline.setStyle({
                        weight: isSelected ? 9 : (layerObj.is_primary ? 7 : 5),
                        opacity: isSelected ? 1 : (layerObj.is_primary ? 0.95 : 0.7)
                    });
                    if (isSelected) {
                        layerObj.polyline.bringToFront();
                    }
                });
            }


            function drawRoutesOnMap(routesData, initialDraw = false) {
                if (!map) { console.error("Objeto map não inicializado em drawRoutesOnMap"); return; }
                // Limpa camadas de rota anteriores SEMPRE antes de desenhar novas
                activeRouteLayers.forEach(layerObj => { if (map.hasLayer(layerObj.polyline)) map.removeLayer(layerObj.polyline); });
                activeRouteLayers = [];

                let bounds = L.latLngBounds([]);

                routesData.forEach((route, index) => {
                    const routeId = route.id || `route-idx-${Date.now()}-${index}`;
                    route.id = routeId;

                    if (route.geometry && route.geometry.coordinates) {
                        const latLngs = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);

                        const isCurrentlyPrimary = initialDraw ? true : route.is_primary;
                        const paneName = isCurrentlyPrimary ? 'primaryRoutePane' : 'alternativeRoutePane';

                        let color;
                        if (initialDraw) { // Desenhando apenas a primária ou a primeira da lista se for a única
                            color = routeColors[0];
                        } else { // Desenhando todas (primária e alternativas)
                            // Para garantir cores distintas e que a primária seja laranja
                            const altRoutesDrawn = activeRouteLayers.filter(r => !r.is_primary).length;
                            color = route.is_primary ? routeColors[0] : routeColors[(altRoutesDrawn % (routeColors.length - 1)) + 1];
                        }

                        const polylineOptions = {
                            color: color,
                            weight: isCurrentlyPrimary ? 7 : 5,
                            opacity: isCurrentlyPrimary ? 0.95 : 0.7,
                            pane: paneName,
                        };

                        const polyline = L.polyline(latLngs, polylineOptions).addTo(map);

                        activeRouteLayers.push({polyline: polyline, routeData: route, id: routeId, is_primary: route.is_primary});

                        bounds.extend(polyline.getBounds());

                        // O popup também usa o campo formatado
                        polyline.bindPopup(`<b>${route.is_primary ? 'Rota Principal' : `Alternativa`}</b><br>Distância: ${route.distance_km} km<br>Duração: ${route.duration_formatted}<br>Preço (${route.category_name}): ${route.currency_symbol || 'R$'} ${route.calculated_price.toFixed(2).replace('.', ',')}`);

                        polyline.on('click', function(e) {
                            L.DomEvent.stopPropagation(e);
                            displayRouteDetailsInPanel(route);
                        });
                    }
                });
                if (bounds.isValid()) { map.flyToBounds(bounds, { padding: [50, 50], duration: 1.5 }); }
            }

            async function fetchAndDisplayPriceQuote(selectedCategory) {
                if (!map) { console.error("Objeto map não inicializado antes de fetchAndDisplayPriceQuote"); return; }
                if (!selectedCategory || !originCoords || !destinationCoords) {
                    errorMessageQuoteP.textContent = "Defina origem, destino e selecione uma modalidade.";
                    return;
                }
                setLoading(true, "Calculando preço e rotas...");
                // Limpa apenas os resultados da cotação e as rotas do mapa, não os marcadores O/D
                clearQuoteDetailsAndMapRoutes(); // Essa função já limpa as rotas e os detalhes do painel

                try {
                    const response = await fetch('/api/price-quote', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                        },
                        body: JSON.stringify({
                            origin_lat: originCoords.lat,
                            origin_lon: originCoords.lon,
                            destination_lat: destinationCoords.lat,
                            destination_lon: destinationCoords.lon,
                            category: selectedCategory
                        })
                    });
                    const data = await response.json();

                    if (response.ok && data.routes && data.routes.length > 0) {
                        allFetchedRoutesData = data.routes.map((route, index) => ({...route, id: route.id || `route-idx-${Date.now()}-${index}`}));
                        const primaryRoute = allFetchedRoutesData.find(r => r.is_primary) || allFetchedRoutesData[0];

                        displayRouteDetailsInPanel(primaryRoute); // Mostra detalhes da primária
                        drawRoutesOnMap([primaryRoute], true); // Desenha SÓ a primária inicialmente

                        // Desenha marcadores de pedágio para a rota principal, se houver
                        if (primaryRoute.toll_points && primaryRoute.toll_points.length > 0) {
                            primaryRoute.toll_points.forEach(toll => {
                                const marker = L.marker([toll.lat, toll.lon], { icon: tollMarkerIcon })
                                    .addTo(map)
                                    .bindPopup(`Pedágio: ${toll.name || 'Informação não disponível'}`);
                                activeTollMarkers.push(marker);
                            });
                        }

                        if (originMapMarker) { originMapMarker.setPopupContent(`<b>Origem:</b><br>${originCoords.displayName}`).openPopup(); }
                        if (destinationMapMarker) { destinationMapMarker.setPopupContent(`<b>Destino:</b><br>${destinationCoords.displayName}`); }

                        if (allFetchedRoutesData.length > 1) {
                            showAlternativesBtn.style.display = 'block';
                            alternativeRoutesInfoDiv.textContent = `${allFetchedRoutesData.length -1} rota(s) alternativa(s) disponível(is).`;
                        } else {
                            showAlternativesBtn.style.display = 'none';
                            alternativeRoutesInfoDiv.textContent = 'Apenas uma rota principal encontrada.';
                            if (primaryRoute.toll_points && primaryRoute.toll_points.length > 0) {
                                alternativeRoutesInfoDiv.textContent += ` Contém ${primaryRoute.toll_points.length} pedágio(s) identificado(s).`;
                            }
                        }

                    } else {
                        if (data.errors) { let errorsText = 'Erros: '; for (const field in data.errors) { errorsText += `${data.errors[field].join(', ')} `; } errorMessageQuoteP.textContent = errorsText; }
                        else if (data.error) { errorMessageQuoteP.textContent = `Erro: ${data.error}`; }
                        else { errorMessageQuoteP.textContent = 'Erro ao calcular cotação (status: ' + response.status + ')';}
                    }
                } catch (error) {
                    console.error('Erro ao buscar cotação:', error);
                    errorMessageQuoteP.textContent = error.message || 'Ocorreu um erro ao buscar a cotação.';
                } finally {
                    setLoading(false);
                }
            }

            // Adiciona o listener para o botão de alternativas, se ele existir no HTML
            if (showAlternativesBtn) {
                showAlternativesBtn.addEventListener('click', function() {
                    if (allFetchedRoutesData.length > 0) {
                        drawRoutesOnMap(allFetchedRoutesData, false);
                        alternativeRoutesInfoDiv.textContent = `Mostrando todas as ${allFetchedRoutesData.length} rotas. Clique nelas no mapa para detalhes.`;
                        if (allFetchedRoutesData[0].toll_points && allFetchedRoutesData[0].toll_points.length > 0) {
                             alternativeRoutesInfoDiv.textContent += ` A rota principal contém ${allFetchedRoutesData[0].toll_points.length} pedágio(s) identificado(s).`;
                        }
                        this.style.display = 'none';
                    }
                });
            }

            confirmAddressesBtn.disabled = true;
        });
    </script>

</body>
</html>