<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exploramos Talara</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script>
        // Aplicar tema guardado lo antes posible para evitar parpadeos
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

    <div class="index-sidebar">
        <div class="index-header">
            <h1>📍 Exploramos</h1>
            <p>Guía exclusiva de Talara y sus distritos</p>
        </div>
        <div class="index-category-list" id="category-list">
            </div>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // 1. Inicializar el mapa centrado en Talara
        const map = L.map('map').setView([-4.5772, -81.2719], 12);
        
        // Capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let markers = []; // Para guardar los marcadores del mapa

        // 2. Obtener los datos de la base de datos
        fetch('api/obtener_ubicaciones.php')
            .then(res => res.json())
            .then(data => {
                construirMenu(data);
                mostrarMarcadores(data); // Mostrar todos al inicio
            })
            .catch(error => console.error('Error fetching data:', error));

        // 3. Construir la lista de categorías y sus lugares
        function construirMenu(lugares) {
            const listDiv = document.getElementById('category-list');
            
            // Agrupar lugares por categoría
            const categoriasAgrupadas = lugares.reduce((acc, lugar) => {
                const catName = lugar.icono + ' ' + lugar.categoria;
                if (!acc[catName]) acc[catName] = [];
                acc[catName].push(lugar);
                return acc;
            }, {});

            // Generar el HTML
            for (const [categoria, lugaresCat] of Object.entries(categoriasAgrupadas)) {
                // Botón de categoría
                const btn = document.createElement('button');
                btn.className = 'index-category-btn';
                btn.innerText = categoria;
                
                // Contenedor de los lugares (oculto por defecto)
                const placesDiv = document.createElement('div');
                placesDiv.className = 'index-places-container';

                // Llenar el contenedor con los lugares
                lugaresCat.forEach(l => {
                    const placeItem = document.createElement('div');
                    placeItem.className = 'index-place-item';
                    placeItem.innerHTML = `
                        <h4>${l.nombre}</h4>
                        <p>📍 ${l.distrito}</p>
                        <p>${l.descripcion}</p>
                    `;
                    // Al hacer clic en el lugar, la cámara del mapa viaja hacia allá
                    placeItem.onclick = () => {
                        map.flyTo([l.latitud, l.longitud], 16);
                    };
                    placesDiv.appendChild(placeItem);
                });

                // Lógica del acordeón (Mostrar/Ocultar y filtrar mapa)
                btn.onclick = () => {
                    const isActive = placesDiv.classList.contains('active');
                    
                    // Cerrar todos
                    document.querySelectorAll('.index-places-container').forEach(d => d.classList.remove('active'));
                    document.querySelectorAll('.index-category-btn').forEach(b => b.classList.remove('active'));
                    
                    if (!isActive) {
                        placesDiv.classList.add('active');
                        btn.classList.add('active');
                        mostrarMarcadores(lugaresCat); // Filtrar mapa por categoría
                    } else {
                        mostrarMarcadores(lugares); // Si se cierra, mostrar todos
                    }
                };

                listDiv.appendChild(btn);
                listDiv.appendChild(placesDiv);
            }
        }

        // 4. Función para poner marcadores en el mapa
        function mostrarMarcadores(lugaresA_Mostrar) {
            // Limpiar marcadores anteriores
            markers.forEach(m => map.removeLayer(m));
            markers = [];

            // Añadir nuevos marcadores
            lugaresA_Mostrar.forEach(lugar => {
                const marker = L.marker([lugar.latitud, lugar.longitud])
                    .addTo(map)
                    .bindPopup(`<b>${lugar.icono} ${lugar.nombre}</b><br>${lugar.descripcion}<br><i>${lugar.distrito}</i>`);
                markers.push(marker);
            });
        }
    </script>
</body>
</html>