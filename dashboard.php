<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Explora Talara | Panel Interactivo</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Aplicar tema guardado
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

    <nav class="navbar">
        <strong class="brand">📍 Explora Talara</strong>
        
        <div class="user-menu-container">
            <div class="user-pill" onclick="toggleDropdown(event)">
                <span id="nav-user-email"><?php echo htmlspecialchars($_SESSION['correo']); ?></span>
                <span>▼</span>
            </div>
            <div class="dropdown-menu" id="userDropdown">
                <a href="cuenta.php" class="dropdown-item">👤 Mi Cuenta</a>
                <a href="#" class="dropdown-item" onclick="toggleTheme(event)">🌗 Cambiar Tema</a>
                <a href="logout.php" class="dropdown-item text-danger">🚪 Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="main-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <input type="text" id="txtBuscar" placeholder="Buscar por nombre..." onkeyup="filtrarPorTexto()">
            </div>
            
            <div class="category-menu" id="menuCategorias">
                <button class="cat-btn active" onclick="filtrarPorCategoria('todos', this)">🌟 Todos</button>
            </div>

            <div class="places-list" id="listaLugares">
                </div>
        </aside>

        <div id="map"></div>

        <aside class="details-panel" id="panelDetalles" style="display:none;">
            <span class="close-btn" onclick="cerrarDetalles()">&times;</span>
            <h2 id="det-nombre"></h2>
            <span class="badge" id="det-cat"></span>
            <p id="det-desc" style="line-height: 1.6; margin-top:15px;"></p>
            <p style="margin-top: 10px;"><strong>Distrito:</strong> <span id="det-distrito" style="font-style: italic;"></span></p>
            <hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--border);">
            
            <h3>Estadísticas de Reseñas</h3>
            <div class="chart-container">
                <canvas id="ratingChart"></canvas>
            </div>

            <h3>Reseñas Recientes</h3>
            <div id="resenas-list"></div>

            <div class="add-review-box">
                <h4>Deja tu opinión</h4>
                <div class="star-rating" id="selectorEstrellas">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <textarea id="nuevo-comentario" placeholder="Escribe tu experiencia..."></textarea>
                <button onclick="guardarMiResena()" id="btn-enviar" class="btn-pub">Publicar Reseña</button>
            </div>
        </aside>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, marcadores = [], lugaresFull = [], legendControl;
        let categoriaSeleccionada = 'todos';
        let lugarIdActual = null;
        let puntuacionSeleccionada = 5;
        let ratingChartInstance = null;

        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, function (match) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match];
            });
        }

        // --- MANEJO DE DROPDOWN Y TEMA ---
        function toggleDropdown(e) {
            e.stopPropagation();
            document.getElementById('userDropdown').classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.user-menu-container')) {
                const dropdowns = document.getElementsByClassName("dropdown-menu");
                for (let i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].classList.remove('show');
                }
            }
        }

        function toggleTheme(e) {
            if(e) e.preventDefault();
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Actualizar Chart si existe
            if(ratingChartInstance) {
                const textColor = newTheme === 'dark' ? '#f8fafc' : '#334155';
                ratingChartInstance.options.plugins.legend.labels.color = textColor;
                ratingChartInstance.options.scales.x.ticks.color = textColor;
                ratingChartInstance.options.scales.y.ticks.color = textColor;
                ratingChartInstance.update();
            }
        }

        // 1. Inicializar Mapa
        function init() {
            map = L.map('map').setView([-4.5772, -81.2719], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            
            cargarDatos();
            configurarEstrellas();
        }

        async function cargarDatos() {
            try {
                const res = await fetch('api/obtener_ubicaciones.php?v=' + Date.now());
                lugaresFull = await res.json();
                
                if(lugaresFull.error) throw new Error(lugaresFull.error);

                generarMenuCategorias();
                renderizarInterfaz(lugaresFull);
                crearLeyenda();
            } catch (e) {
                console.error("Error al cargar datos de la API:", e);
                alert("Error al cargar el mapa. Verifica la conexión a la base de datos.");
            }
        }

        // 2. Filtros y Menú
        function generarMenuCategorias() {
            const menu = document.getElementById('menuCategorias');
            const cats = [...new Map(lugaresFull.map(item => [item.categoria, item])).values()];
            
            cats.forEach(c => {
                const btn = document.createElement('button');
                btn.className = 'cat-btn';
                btn.innerHTML = `${c.icono} ${c.categoria}`;
                btn.onclick = () => filtrarPorCategoria(c.categoria, btn);
                menu.appendChild(btn);
            });
        }

        function filtrarPorCategoria(cat, elemento) {
            categoriaSeleccionada = cat;
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            elemento.classList.add('active');
            ejecutarFiltros();
        }

        function filtrarPorTexto() { ejecutarFiltros(); }

        function ejecutarFiltros() {
            const texto = document.getElementById('txtBuscar').value.toLowerCase();
            const filtrados = lugaresFull.filter(l => {
                const matchTexto = l.nombre.toLowerCase().includes(texto);
                const matchCat = (categoriaSeleccionada === 'todos' || l.categoria === categoriaSeleccionada);
                return matchTexto && matchCat;
            });
            renderizarInterfaz(filtrados);
        }

        // 3. Renderizado y Detalles
        function renderizarInterfaz(datos) {
            marcadores.forEach(m => map.removeLayer(m));
            marcadores = [];

            const listaHTML = document.getElementById('listaLugares');
            listaHTML.innerHTML = "";

            datos.forEach(l => {
                const m = L.marker([l.latitud, l.longitud]).addTo(map);
                m.on('click', () => abrirDetalle(l));
                marcadores.push(m);

                const item = document.createElement('div');
                item.className = 'place-item';
                item.innerHTML = `<h4>${escapeHTML(l.icono)} ${escapeHTML(l.nombre)}</h4><small>${escapeHTML(l.distrito)} | ${escapeHTML(l.num_reviews)} reseñas</small>`;
                item.onclick = () => { map.flyTo([l.latitud, l.longitud], 16); abrirDetalle(l); };
                listaHTML.appendChild(item);
            });
        }

        function abrirDetalle(l) {
            lugarIdActual = l.id;
            document.getElementById('panelDetalles').style.display = 'block';
            document.getElementById('det-nombre').innerText = l.nombre;
            document.getElementById('det-cat').innerText = l.categoria;
            document.getElementById('det-desc').innerText = l.descripcion;
            document.getElementById('det-distrito').innerText = l.distrito;
            cargarResenas(l.id);
        }

        function cargarResenas(id) {
            const list = document.getElementById('resenas-list');
            list.innerHTML = "Cargando reseñas...";

            fetch(`api/obtener_comentarios.php?id=${id}&t=${Date.now()}`)
                .then(res => res.json())
                .then(datos => {
                    // Renderizar reseñas
                    list.innerHTML = datos.length ? datos.map(r => `
                        <div class="r-item">
                            <div style="color: #f59e0b; margin-bottom:5px;">${"★".repeat(r.puntuacion)}${"☆".repeat(5-r.puntuacion)}</div>
                            <strong>${escapeHTML(r.correo)}</strong>
                            <p style="margin:5px 0;">${escapeHTML(r.comentario)}</p>
                            <small class="text-muted">${new Date(r.fecha).toLocaleDateString()}</small>
                        </div>
                    `).join('') : "<p>Aún no hay reseñas.</p>";

                    // Generar Chart
                    generarGrafico(datos);
                });
        }

        function generarGrafico(resenas) {
            const ctx = document.getElementById('ratingChart').getContext('2d');
            
            // Conteo de estrellas
            let conteo = { 5:0, 4:0, 3:0, 2:0, 1:0 };
            resenas.forEach(r => {
                if (conteo[r.puntuacion] !== undefined) {
                    conteo[r.puntuacion]++;
                }
            });

            const data = [conteo[5], conteo[4], conteo[3], conteo[2], conteo[1]];
            
            if(ratingChartInstance) {
                ratingChartInstance.destroy();
            }

            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const textColor = currentTheme === 'dark' ? '#f8fafc' : '#334155';

            ratingChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['5 Estrellas', '4 Estrellas', '3 Estrellas', '2 Estrellas', '1 Estrella'],
                    datasets: [{
                        label: 'Cantidad de Reseñas',
                        data: data,
                        backgroundColor: '#f59e0b',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y', // Gráfico de barras horizontal
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { 
                            ticks: { stepSize: 1, color: textColor },
                            grid: { color: currentTheme === 'dark' ? '#334155' : '#e2e8f0' }
                        },
                        y: {
                            ticks: { color: textColor },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 4. Funciones Especiales: Leyenda y Estrellas
        function crearLeyenda() {
            if (legendControl) map.removeControl(legendControl);
            legendControl = L.control({position: 'bottomright'});
            legendControl.onAdd = function () {
                const div = L.DomUtil.create('div', 'info legend');
                const cats = [...new Map(lugaresFull.map(i => [i.categoria, i])).values()];
                div.innerHTML = '<h4>Leyenda</h4>';
                cats.forEach(c => {
                    div.innerHTML += `<div class="legend-item" onclick="forzarFiltro('${c.categoria}')"><span class="legend-icon">${c.icono}</span> ${c.categoria}</div>`;
                });
                return div;
            };
            legendControl.addTo(map);
        }

        function forzarFiltro(cat) {
            const btns = document.querySelectorAll('.cat-btn');
            btns.forEach(b => { if(b.innerText.includes(cat)) b.click(); });
        }

        function configurarEstrellas() {
            const stars = document.querySelectorAll('.star');
            stars.forEach(s => {
                s.onclick = () => {
                    puntuacionSeleccionada = s.dataset.value;
                    stars.forEach(x => {
                        if (x.dataset.value <= puntuacionSeleccionada) {
                            x.classList.add('active');
                        } else {
                            x.classList.remove('active');
                        }
                    });
                };
            });
            stars[4].click(); // Iniciar en 5 estrellas
        }

        async function guardarMiResena() {
            const txt = document.getElementById('nuevo-comentario').value.trim();
            if (!txt) return alert("Escribe un comentario.");
            const btn = document.getElementById('btn-enviar');
            btn.disabled = true;

            try {
                const response = await fetch('api/guardar_resena.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ubicacion_id: lugarIdActual, comentario: txt, puntuacion: puntuacionSeleccionada })
                });
                const res = await response.json();
                if (res.status === 'success') {
                    document.getElementById('nuevo-comentario').value = "";
                    cargarResenas(lugarIdActual);
                }
            } catch (e) { alert("Error de conexión"); }
            finally { btn.disabled = false; }
        }

        function cerrarDetalles() { document.getElementById('panelDetalles').style.display = 'none'; }

        init();
    </script>
</body>
</html>