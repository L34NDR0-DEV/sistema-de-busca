<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Busca de Rotas Avançado</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body, html { height: 100%; margin: 0; font-family: 'Roboto', sans-serif; background-color: #eef1f5; overflow: hidden; }
        
        #layout-container {
            display: flex;
            height: 100%;
            width: 100%;
        }

        .side-panel {
            width: 360px; 
            min-width: 320px;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            z-index: 10; 
        }
        .side-panel h1 { font-size: 1.7em; color: #2c3e50; margin-top: 0; margin-bottom: 25px; font-weight:700; }
        .side-panel h2 { font-size: 1.3em; margin-top: 0; margin-bottom: 15px; color: #34495e; font-weight:500; border-bottom: 1px solid #eee; padding-bottom:10px;}
        
        #quote-details-panel {
            display: none;
        }

        .input-group { margin-bottom: 18px; position: relative; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #34495e; font-size: 0.9em; }
        .input-group input::placeholder { color: #bdc3c7; }
        .input-group input, .input-group select { width: 100%; padding: 12px; border: 1px solid #dce4ec; border-radius: 6px; font-size: 0.95em; transition: border-color 0.3s ease, box-shadow 0.3s ease; background-color: #fdfdfd; }
        .input-group input:focus, .input-group select:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15); }
        .input-group select { appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24' fill='%2334495e'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 20px; padding-right: 35px; }

        .suggestions-list { list-style-type: none; padding: 0; margin: 5px 0 0 0; position: absolute; background-color: white; border: 1px solid #dce4ec; width: 100%; max-height: 220px; overflow-y: auto; z-index: 1001; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .suggestions-list li { padding: 12px 15px; cursor: pointer; font-size: 0.9em; border-bottom: 1px solid #f0f0f0; }
        .suggestions-list li:last-child { border-bottom: none; }
        .suggestions-list li:hover { background-color: #f8f9fa; }
        .suggestions-list li.loading-suggestion { color: #888; font-style: italic; padding: 12px 15px; }

        .map-interaction-buttons { display: flex; justify-content: space-between; margin-top: 8px; }
        .map-interaction-buttons button { flex-grow: 1; margin-right: 8px; padding: 10px; font-size: 0.8em; background-color: #6c757d; color:white; border:none; border-radius:4px; cursor:pointer; transition: background-color 0.2s; text-transform: uppercase; letter-spacing: 0.5px; }
        .map-interaction-buttons button:last-child { margin-right: 0; }
        .map-interaction-buttons button:hover { background-color: #5a6268; }
        .map-interaction-buttons button.active-mode { background-color: #007bff; color: white; box-shadow: 0 0 8px rgba(0,123,255,0.4); }
        
        .confirmed-address { background-color: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 18px; font-size: 0.95em; border-left: 4px solid #3498db; color: #34495e; word-break: break-word; }
        .confirmed-address strong { color: #2c3e50; }

        #results-summary { margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef; }
        #results-summary p { margin: 10px 0; font-size: 1em; color: #4a5568; }
        #results-summary p span { font-weight: 500; color: #2d3748; }
        #calculated_price_display { font-size: 1.5em; font-weight: 700; color: #28a745; margin-top:5px; display:block; }
        #alternative-routes-info { margin-top:10px; font-size:0.9em; color: #555; padding:10px; background-color:#f9f9f9; border-radius:4px; }
        
        #error_message_quote, #error_message_input { 
            color: #e53e3e; font-weight: 500; margin-top: 15px; background-color: #fff5f5; 
            border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px;
            word-break: break-word; 
        }
        #error_message_input { margin-top: auto; }
        
        #mapContainer { flex-grow: 1; height: 100%; cursor: default; }
        #mapContainer.crosshair-cursor { cursor: crosshair !important; }

        .spinner-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255,255,255,0.9); z-index: 10000; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #333; }
        
        .loader {
            border: 6px solid #f3f3f3; 
            border-top: 6px solid #007bff; 
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 0.8s linear infinite; 
            margin-bottom: 20px;
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner-text { font-weight: 500; font-size: 1.2em; color: #34495e; }

        .action-button { 
            width: 100%; padding: 12px 15px; font-size: 1em; font-weight: 500; cursor: pointer; 
            color: white; border: none; border-radius: 4px; text-transform: uppercase; 
            letter-spacing: 0.5px; transition: background-color 0.2s ease-in-out; margin-top: 10px; 
        }
        .action-button.primary { background-color: #007bff; }
        .action-button.primary:hover { background-color: #0056b3; }
        .action-button.secondary { background-color: #6c757d; }
        .action-button.secondary:hover { background-color: #5a6268; }
        .action-button:disabled { background-color: #ccc; cursor: not-allowed; }
        #showAlternativesBtn { display: none; margin-top: 15px; background-color: #f39c12; } /* Começa escondido */
        #showAlternativesBtn:hover { background-color: #e67e22; }

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
                        <input type="text" id="origin_address" name="origin_address" placeholder="Digite o endereço de origem..." autocomplete="off">
                        <ul class="suggestions-list" id="origin_suggestions"></ul>
                    </div>
                    <div class="map-interaction-buttons">
                        <button id="setOriginByMapBtn">Marcar Origem no Mapa</button>
                    </div>
                </div>

                <div class="address-input-section">
                    <div class="input-group">
                        <label for="destination_address">Destino</label>
                        <input type="text" id="destination_address" name="destination_address" placeholder="Digite o endereço de destino..." autocomplete="off">
                        <ul class="suggestions-list" id="destination_suggestions"></ul>
                    </div>
                    <div class="map-interaction-buttons">
                        <button id="setDestinationByMapBtn">Marcar Destino no Mapa</button>
                    </div>
                </div>
            </div>
            <button id="confirmAddressesBtn" class="action-button primary" style="margin-top: auto;">Confirmar e Ver Opções</button>
            <p id="error_message_input"></p>
        </div>

        <div id="mapContainer"></div> 

        <div id="quote-details-panel" class="side-panel">
            <h1>Detalhes da Cotação</h1>
            <div class="confirmed-address">
                <strong>Origem:</strong> <span id="confirmed_origin_address_quote">---</span>
            </div>
            <div class="confirmed-address">
                <strong>Destino:</strong> <span id="confirmed_destination_address_quote">---</span>
            </div>

            <div class="input-group">
                <label for="category_select">Escolha a Modalidade:</label>
                <select id="category_select" name="category">
                    <option value="">-- Selecione --</option>
                    <option value="economico">Econômico</option>
                    <option value="conforto">Conforto</option>
                    <option value="premium">Premium</option>
                </select>
            </div>
            <div id="results-summary">
                <p>Modalidade: <span id="category_name_display">---</span></p>
                <p>Distância: <span id="distance">---</span> km</p>
                <p>Duração Estimada: <span id="duration">---</span> minutos</p>
                <p>Preço Estimado: <span id="calculated_price_display">R$ ---</span></p>
            </div>
            <div id="alternative-routes-info"></div>
            <button id="showAlternativesBtn" class="action-button secondary" style="display: none;">Ver Rotas Alternativas</button>
            <button id="editAddressesBtnQuote" class="action-button secondary" style="margin-top: 10px;">Voltar e Editar Endereços</button>
            <p id="error_message_quote"></p>
        </div>
    </div>
    
    <div id="spinner" class="spinner-overlay" style="display: none;">
        <div class="loader"></div> 
        <span class="spinner-text">Buscando informações...</span>
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
                } else { console.error('Falha ao criar alternativeRoutePane'); }

                map.createPane('primaryRoutePane');
                if (map.getPane('primaryRoutePane')) { 
                    map.getPane('primaryRoutePane').style.zIndex = 630; 
                    map.getPane('primaryRoutePane').style.pointerEvents = 'auto';
                } else { console.error('Falha ao criar primaryRoutePane'); }

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

            const greenIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            const redIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            const tollMarkerIcon = L.icon({ // Ícone para pedágios
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/2972/2972481.png', // Um ícone genérico de pedágio/dinheiro
                iconSize: [24, 24],
                iconAnchor: [12, 24],
                popupAnchor: [0, -24]
            });
            const routeColors = ['#007bff', '#28a745', '#ffc107', '#fd7e14', '#6f42c1']; 

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
            
            function switchToInputPanel() {
                quoteDetailsPanel.style.display = 'none';
                inputPanel.style.display = 'flex'; 
                clearQuoteDetailsAndMapRoutes();
                // Não limpa marcadores de origem/destino nem coordenadas, permite edição
                confirmAddressesBtn.disabled = !(originCoords && destinationCoords); 
            }

            function switchToQuotePanel() {
                 if (originCoords && destinationCoords) {
                    inputPanel.style.display = 'none'; 
                    quoteDetailsPanel.style.display = 'flex'; 
                    confirmedOriginSpanQuote.textContent = originCoords.displayName || `${originCoords.lat.toFixed(5)}, ${originCoords.lon.toFixed(5)}`;
                    confirmedDestinationSpanQuote.textContent = destinationCoords.displayName || `${destinationCoords.lat.toFixed(5)}, ${destinationCoords.lon.toFixed(5)}`;
                    clearQuoteDetailsAndMapRoutes(); // Limpa para nova cotação
                    categorySelect.value = ""; 
                    errorMessageInputP.textContent = ""; 
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
                const icon = type === 'origin' ? greenIcon : redIcon;
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

            async function geocodeForSubmit(address, type) {
                const currentCoords = type === 'origin' ? originCoords : destinationCoords;
                if (currentCoords && address === currentCoords.displayName) return currentCoords;
                try {
                    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&addressdetails=1`;
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`Falha na geocodificação de ${type}`);
                    const data = await response.json();
                    if (data && data.length > 0) {
                        return { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon), displayName: data[0].display_name };
                    } else {
                        throw new Error(`Endereço de ${type} não encontrado: ${address}`);
                    }
                } finally { /* setLoading é controlado pelo fluxo principal */ }
            }
            
            function displayRouteDetailsInPanel(routeData) {
                if (!routeData || !map) return;
                categoryNameDisplay.textContent = routeData.category_name;
                distanceSpan.textContent = routeData.distance_km;
                durationSpan.textContent = routeData.duration_minutes;
                calculatedPriceDisplay.textContent = `${routeData.currency_symbol || 'R$'} ${routeData.calculated_price.toFixed(2)}`;
                
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
                            // Para garantir cores distintas e que a primária seja azul
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
                        
                        polyline.bindPopup(`<b>${route.is_primary ? 'Rota Principal' : `Alternativa`}</b><br>Distância: ${route.distance_km} km<br>Duração: ${route.duration_minutes} min<br>Preço (${route.category_name}): ${route.currency_symbol || 'R$'} ${route.calculated_price.toFixed(2)}`);
                        
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