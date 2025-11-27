<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StorySwitch Editor V11 (Server)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- DESIGN SYSTEM V11 (Copie Exacte index.html) --- */
        :root {
            --bg-deep: #09090b;
            --bg-panel: #18181b;
            --bg-node: #27272a;
            --border-dim: #3f3f46;
            --primary: #3b82f6; --primary-hover: #2563eb; --primary-glow: rgba(59,130,246,0.3);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #f4f4f5;
            --node-w: 280px;
        }

        body, html { width: 100%; height: 100%; margin: 0; background: var(--bg-deep); color: var(--text-main); font-family: 'Inter', sans-serif; overflow: hidden; user-select: none; }
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #18181b; } ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }

        /* CANVAS */
        #viewport { position: absolute; inset: 0; overflow: hidden; z-index: 0; cursor: default; background-image: radial-gradient(#333 1px, transparent 1px); background-size: 40px 40px; background-color: var(--bg-deep); }
        #world { position: absolute; top: 0; left: 0; transform-origin: 0 0; pointer-events: none; will-change: transform; }
        
        /* COUCHES */
        #connections-layer { position: absolute; top: -100000px; left: -100000px; width: 200000px; height: 200000px; overflow: visible; pointer-events: none; z-index: 5; }
        #nodes-layer { position: absolute; top: 0; left: 0; z-index: 10; pointer-events: none; }
        
        /* NOEUDS */
        .node {
            position: absolute; width: var(--node-w); background: var(--bg-node); border: 1px solid var(--border-dim);
            border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.6); display: flex; flex-direction: column;
            pointer-events: all;
            transition: box-shadow 0.2s, border-color 0.2s; animation: pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes pop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .node:hover { border-color: #71717a; z-index: 20; }
        .node.selected { border-color: var(--primary); box-shadow: 0 0 0 2px var(--primary-glow); z-index: 50; }
        
        .node-header { padding: 10px 12px; background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-dim); border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; cursor: grab; }
        .node-header:active { cursor: grabbing; }

        /* PORTS */
        .port { width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--bg-node); position: relative; z-index: 30; transition: transform 0.2s; }
        .port:hover { transform: scale(1.4); cursor: crosshair; }
        .port.input { background: var(--success); }
        .port.output { background: var(--warning); }

        /* CABLES */
        .cable { fill: none; stroke: #52525b; stroke-width: 2px; stroke-linecap: round; pointer-events: stroke; cursor: pointer; transition: stroke 0.2s, stroke-width 0.2s; }
        .cable:hover { stroke: var(--primary); stroke-width: 4px; }
        .cable.dragging { stroke: var(--warning); stroke-dasharray: 8; animation: flow 0.5s linear infinite; pointer-events: none; }
        @keyframes flow { to { stroke-dashoffset: -16; } }
        #arrow-marker path { fill: #52525b; transition: fill 0.2s; }
        .cable:hover ~ defs #arrow-marker path { fill: var(--primary); }

        /* UI */
        .toolbar { pointer-events: auto; background: rgba(24,24,27,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-dim); padding: 0 20px; height: 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 100; position: relative; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.15s; border: 1px solid transparent; }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--primary); color: white; } .btn-primary:hover { background: var(--primary-hover); }
        .btn-dark { background: #27272a; color: #e4e4e7; border-color: #3f3f46; } .btn-dark:hover { background: #3f3f46; }
        .btn-danger { background: rgba(239,68,68,0.1); color: var(--danger); } .btn-danger:hover { background: rgba(239,68,68,0.2); }

        /* DROPDOWN */
        .dropdown-menu { display: none; flex-direction: column; position: absolute; top: 100%; right: 0; margin-top: 8px; background: #18181b; border: 1px solid #3f3f46; border-radius: 8px; width: 220px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); z-index: 200; padding: 4px; }
        .dropdown-menu.active { display: flex; animation: slideDown 0.1s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* MINIMAP */
        .minimap { position: fixed; bottom: 50px; left: 20px; width: 220px; height: 150px; background: rgba(24,24,27,0.9); border: 1px solid var(--border-dim); border-radius: 8px; pointer-events: auto; overflow: hidden; z-index: 90; }
        #minimap-canvas { width: 100%; height: 100%; opacity: 0.8; }
        #minimap-viewport { position: absolute; border: 1px solid var(--primary); background: var(--primary-glow); pointer-events: none; }

        /* CONTEXT MENU */
        #context-menu { position: fixed; background: var(--bg-panel); border: 1px solid var(--border-dim); border-radius: 6px; padding: 4px; display: none; z-index: 2000; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .ctx-item { padding: 8px 12px; cursor: pointer; color: #e4e4e7; font-size: 0.85rem; display: flex; justify-content: space-between; border-radius: 4px; }
        .ctx-item:hover { background: #27272a; }

        /* MODALES */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); z-index: 3000; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--bg-panel); border: 1px solid var(--border-dim); border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.6); animation: popModal 0.2s ease-out; }
        @keyframes popModal { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        /* TOASTS */
        #toast-container { position: fixed; bottom: 80px; right: 20px; display: flex; flex-direction: column; gap: 10px; align-items: flex-end; pointer-events: none; z-index: 2000; }
        .toast { background: #18181b; border-left: 4px solid var(--primary); padding: 12px 20px; border-radius: 4px; color: white; box-shadow: 0 5px 15px rgba(0,0,0,0.5); animation: slideIn 0.3s ease-out; pointer-events: auto; display: flex; align-items: center; gap: 10px; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        kbd { background: #333; padding: 2px 5px; border-radius: 4px; font-family: monospace; font-size: 0.7rem; border-bottom: 2px solid #111; }

        /* Alerte Overlay pour erreur fatale */
        #fatal-error-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; text-align: center; }
    </style>
</head>
<body>

    <!-- Overlay Erreur Fatale (Protocole file://) -->
    <div id="fatal-error-overlay">
        <div class="bg-red-900 p-8 rounded-lg max-w-lg border border-red-500">
            <h2 class="text-3xl font-bold text-white mb-4">⛔ STOP !</h2>
            <p class="text-xl mb-4">Tu as ouvert ce fichier directement (file://).</p>
            <p class="mb-6 text-gray-300">Le PHP ne peut pas fonctionner comme ça. Tu dois passer par ton serveur Laragon.</p>
            <div class="bg-black p-4 rounded text-left font-mono text-sm text-green-400 mb-6">
                1. Lance Laragon.<br>
                2. Vérifie que ton dossier est dans C:\laragon\www\<br>
                3. Ouvre ton navigateur et tape :<br>
                http://localhost/ton_dossier/creation.php
            </div>
            <button onclick="location.reload()" class="bg-white text-red-900 font-bold py-2 px-6 rounded hover:bg-gray-200">J'ai compris, réessayer</button>
        </div>
    </div>

    <!-- ZONE DE TRAVAIL -->
    <div id="viewport">
        <div id="world">
            <svg id="connections-layer">
                <defs>
                    <marker id="arrow-marker" markerWidth="10" markerHeight="10" refX="8" refY="5" orient="auto" markerUnits="strokeWidth">
                        <path d="M0,0 L10,5 L0,10 L2,5 Z" fill="#71717a" />
                    </marker>
                </defs>
            </svg>
            <div id="nodes-layer"></div>
        </div>
        <div id="selection-box" style="position: absolute; border: 1px solid #3b82f6; background: rgba(59,130,246,0.1); display: none; z-index: 999; pointer-events: none;"></div>
    </div>

    <!-- UI -->
    <header class="toolbar">
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-lg flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-network-wired text-white text-sm"></i>
            </div>
            <h1 class="font-bold text-white text-lg">StorySwitch <span class="text-xs text-blue-400 ml-1 uppercase tracking-widest">V11 SERVER</span></h1>
            <div class="h-6 w-px bg-white/10 mx-2"></div>
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                <input type="text" id="search-input" placeholder="Filtrer..." class="bg-black/20 border border-white/10 text-gray-300 text-sm rounded-full pl-9 pr-4 py-1.5 focus:border-blue-500 outline-none w-64 transition-all">
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-white/5 rounded-lg border border-white/10 p-0.5">
                <button id="btn-undo" class="px-3 py-1.5 text-gray-400 hover:text-white hover:bg-white/5 rounded disabled:opacity-30"><i class="fa-solid fa-rotate-left"></i></button>
                <div class="w-px bg-white/10 my-1"></div>
                <button id="btn-redo" class="px-3 py-1.5 text-gray-400 hover:text-white hover:bg-white/5 rounded disabled:opacity-30"><i class="fa-solid fa-rotate-right"></i></button>
            </div>
            
            <button id="btn-videos" class="btn btn-dark relative"><i class="fa-regular fa-folder-open"></i> Vidéos <span id="badge-videos" class="absolute -top-2 -right-2 bg-blue-600 text-[10px] px-1.5 rounded-full hidden">0</span></button>
            <input type="file" id="input-videos" multiple accept="video/*" class="hidden">
            
            <button id="btn-add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Scène</button>
            
            <!-- Menu Données -->
            <div class="relative">
                <button id="btn-data-menu" class="btn btn-dark">
                    <i class="fa-solid fa-database"></i> Données <i class="fa-solid fa-caret-down ml-1 text-xs"></i>
                </button>
                <div id="menu-data" class="dropdown-menu">
                    <div class="px-3 py-2 hover:bg-[#27272a] cursor-pointer rounded text-sm text-gray-300 flex items-center gap-2" id="act-import"><i class="fa-solid fa-file-import text-green-500"></i> Importer / Charger</div>
                    <div class="px-3 py-2 hover:bg-[#27272a] cursor-pointer rounded text-sm text-gray-300 flex items-center gap-2" id="act-export"><i class="fa-solid fa-file-export text-yellow-500"></i> Voir JSON Brut</div>
                    <div class="h-px bg-[#3f3f46] my-1"></div>
                    <div class="px-3 py-2 hover:bg-[#27272a] cursor-pointer rounded text-sm text-red-400 flex items-center gap-2" id="act-clear"><i class="fa-solid fa-trash"></i> Nouveau Projet</div>
                </div>
            </div>

            <button id="btn-settings" class="btn btn-dark px-2"><i class="fa-solid fa-gear"></i></button>
            <div class="h-8 w-px bg-white/10 mx-1"></div>
            
            <button id="btn-save" class="btn" style="background:#7c3aed; color:white;"><i class="fa-solid fa-floppy-disk"></i> Sauver</button>
            <a id="player-link" href="#" target="_blank" class="btn" style="background:#059669; color:white; pointer-events: none; opacity: 0.5;"><i class="fa-solid fa-play"></i> Jouer</a>
        </div>
    </header>

    <!-- Outils Flottants -->
    <div class="minimap" id="minimap">
        <canvas id="minimap-canvas"></canvas>
        <div id="minimap-viewport"></div>
    </div>
    
    <div class="fixed bottom-0 left-0 right-0 bg-[#18181b] border-t border-[#333] px-6 py-1 flex justify-between items-center text-xs text-gray-500 z-50 pointer-events-auto">
        <div class="flex gap-4">
            <span><i class="fa-solid fa-mouse mr-1"></i> Zoom: <kbd>Molette</kbd></span>
            <span>Pan: <kbd>Clic Molette</kbd> ou <kbd>Espace+Drag</kbd></span>
            <span>Select: <kbd>Shift+Drag</kbd></span>
        </div>
        <div class="flex items-center gap-3">
            <span id="status-text" class="text-blue-400 italic">Prêt</span>
            <span id="zoom-level" class="font-mono text-white bg-white/5 px-2 rounded">100%</span>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="context-menu"></div>
    <div id="toast-container"></div>

    <!-- Modale Import / Chargement -->
    <div id="modal-json" class="modal-overlay">
        <div class="modal-box w-[800px] flex flex-col bg-[#18181b] max-h-[90vh]">
            <div class="flex justify-between p-4 border-b border-[#3f3f46]">
                <h3 class="font-bold text-white">Gestion de Projet</h3>
                <button class="text-gray-400 hover:text-white close-modal"><i class="fa-solid fa-times"></i></button>
            </div>
            
            <div class="p-4 bg-[#27272a] border-b border-[#3f3f46]">
                <h4 class="text-xs font-bold text-green-500 mb-2 uppercase">Charger depuis le serveur</h4>
                <div class="flex gap-2">
                    <select id="server-file-select" class="flex-1 bg-[#18181b] border border-[#3f3f46] text-white rounded p-2 text-sm outline-none">
                        <option value="">-- Sélectionner un fichier --</option>
                    </select>
                    <button id="btn-load-server" class="btn btn-primary">Charger</button>
                </div>
            </div>

            <div class="flex-1 p-4 overflow-hidden flex flex-col">
                <label class="text-xs text-gray-500 mb-1">Import / Export Manuel (JSON)</label>
                <textarea id="json-area" class="w-full h-48 bg-[#09090b] text-green-400 font-mono text-xs p-4 rounded border border-[#3f3f46] outline-none resize-none mb-2"></textarea>
            </div>
            <div class="p-4 border-t border-[#3f3f46] flex justify-between">
                <button id="btn-copy" class="btn btn-dark">Copier</button>
                <button id="btn-import-conf" class="btn btn-primary">Importer JSON manuel</button>
            </div>
        </div>
    </div>

    <!-- Modale Sauvegarde Serveur -->
    <div id="modal-save" class="modal-overlay">
        <div class="modal-box w-[400px] p-6 bg-[#18181b]">
            <h3 class="font-bold text-white mb-4">Sauvegarder sur le Serveur</h3>
            <p class="text-gray-400 text-sm mb-4">Nom du fichier (.json) :</p>
            <input type="text" id="save-filename" value="projet.json" class="w-full bg-[#222] border border-[#444] text-white rounded p-2 mb-6 outline-none focus:border-purple-500" autofocus>
            <div class="flex justify-end gap-2">
                <button class="btn btn-dark close-modal">Annuler</button>
                <button id="btn-save-confirm" class="btn" style="background:#7c3aed; color:white;">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Modale Rename -->
    <div id="modal-rename" class="modal-overlay">
        <div class="modal-box w-[350px] p-6 bg-[#18181b]">
            <h3 class="font-bold text-white mb-4">Renommer la Scène</h3>
            <input type="text" id="rename-input" class="w-full bg-[#222] border border-[#444] text-white rounded p-2 mb-6 outline-none focus:border-blue-500" autofocus>
            <div class="flex justify-end gap-2">
                <button class="btn btn-dark close-modal">Annuler</button>
                <button id="btn-rename-conf" class="btn btn-primary">Valider</button>
            </div>
        </div>
    </div>

    <!-- Modale Settings -->
    <div id="modal-settings" class="modal-overlay">
        <div class="modal-box w-[400px] bg-[#18181b]">
            <div class="p-4 border-b border-[#3f3f46] flex justify-between"><h3 class="font-bold text-white">Paramètres</h3><button class="close-modal text-gray-400"><i class="fa-solid fa-times"></i></button></div>
            <div class="p-6 flex flex-col gap-4">
                <div class="flex justify-between"><span class="text-sm text-gray-300">Minimap</span><input type="checkbox" id="set-minimap" checked class="accent-blue-500"></div>
                <div class="flex justify-between"><span class="text-sm text-gray-300">Snap Grille</span><input type="checkbox" id="set-snap" checked class="accent-blue-500"></div>
                <div class="flex justify-between"><span class="text-sm text-gray-300">Style Lignes</span><select id="set-style" class="bg-[#222] text-xs rounded p-1"><option value="bezier">Courbe</option><option value="straight">Droit</option></select></div>
            </div>
        </div>
    </div>

    <script type="module">
        /* CORE ENGINE V11 + PHP SERVER INTEGRATION */
        const PHP_SAVE_URL = 'save_json.php';
        const PHP_LIST_VIDEOS_URL = 'list_videos.php'; 
        const PHP_LIST_JSONS_URL = 'liste_jsons.php';   
        const PHP_UPLOAD_VIDEO_URL = 'upload_videos.php';

        const App = {
            nodes: {}, links: {}, videos: [],
            view: { x: 0, y: 0, z: 1 },
            nextId: 1, history: [], histIdx: -1, selection: new Set(),
            mouse: { x:0, y:0, wx:0, wy:0 },
            drag: null, linking: null, clipboard: null, keys: {},
            config: { gridSize: 40, snap: true, autoSave: false, showMinimap: true, lineStyle: 'bezier' },
            currentFile: "nouveau_projet.json",
            renId: null
        };

        const DOM = {
            vp: document.getElementById('viewport'),
            world: document.getElementById('world'),
            nLayer: document.getElementById('nodes-layer'),
            sLayer: document.getElementById('connections-layer'),
            selBox: document.getElementById('selection-box'),
            mini: document.getElementById('minimap'),
            miniC: document.getElementById('minimap-canvas'),
            miniV: document.getElementById('minimap-viewport'),
            ctx: document.getElementById('minimap-canvas').getContext('2d'),
            toasts: document.getElementById('toast-container'),
            playerLink: document.getElementById('player-link')
        };

        // --- INIT ---
        function init() {
            console.log("V11 Engine Started (Server Mode)");
            if (window.location.protocol === 'file:') { document.getElementById('fatal-error-overlay').style.display = 'flex'; return; }

            const cf = localStorage.getItem('storyswitch_config'); if(cf) App.config={...App.config, ...JSON.parse(cf)};
            
            centerCam(); bindEvents(); setupUI(); loop();
            
            // Chargement initial Serveur
            fetchVideos();
            
            // Créer une scène par défaut
            const start = createNode(innerWidth/2 - 140, innerHeight/2 - 100, {name:'intro'});
            saveState("Init");
        }

        // --- SERVER FUNCTIONS ---
        async function fetchVideos() {
            try {
                const res = await fetch(PHP_LIST_VIDEOS_URL);
                const txt = await res.text();
                // Extraction JSON robuste
                const jsonStart = txt.search(/\[|\{/);
                if(jsonStart === -1) throw new Error("Format invalide");
                const data = JSON.parse(txt.substring(jsonStart));
                
                if(Array.isArray(data)) {
                    App.videos = data.sort();
                    updateBadge();
                    // Rafraichir les dropdowns des nodes existants
                    Object.values(App.nodes).forEach(n => renderNode(n));
                    toast(`${App.videos.length} vidéos chargées`);
                }
            } catch(e) {
                console.error("Err vidéos:", e);
                toast("Erreur chargement vidéos", "error");
            }
        }

        async function fetchJsonList() {
            try {
                const res = await fetch(PHP_LIST_JSONS_URL);
                const txt = await res.text();
                const jsonStart = txt.search(/\[|\{/);
                const data = JSON.parse(txt.substring(jsonStart));
                
                const sel = document.getElementById('server-file-select');
                sel.innerHTML = '<option value="">-- Sélectionner un fichier --</option>';
                if(Array.isArray(data)) {
                    data.sort().forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f; opt.textContent = f;
                        sel.appendChild(opt);
                    });
                }
            } catch(e) { console.error(e); }
        }

        async function uploadFiles(files) {
            const fd = new FormData();
            for(let f of files) fd.append('videos[]', f);
            
            toast("Envoi en cours...", "info");
            try {
                const res = await fetch(PHP_UPLOAD_VIDEO_URL, { method:'POST', body:fd });
                const txt = await res.text();
                const jsonStart = txt.search(/\[|\{/);
                const data = JSON.parse(txt.substring(jsonStart));
                
                if(data && data.failed === 0) {
                    toast("Upload réussi !", "success");
                    fetchVideos();
                } else {
                    toast(data.message || "Erreur upload", "error");
                }
            } catch(e) {
                toast("Erreur connexion", "error");
            }
        }

        async function saveToServer() {
            const name = document.getElementById('save-filename').value.trim();
            if(!name) return;
            
            const df = buildDataFilm();
            // Clean des liens nulls pour le player
            Object.keys(df).forEach(k => {
                if(df[k].choix) {
                    const cleanC = {};
                    Object.keys(df[k].choix).forEach(ck => {
                        if(df[k].choix[ck][1] !== null) cleanC[ck] = df[k].choix[ck];
                    });
                    df[k].choix = cleanC;
                }
            });

            const content = {
                data_film: df,
                construct: { nodes: App.nodes, links: App.links, view: App.view, nextId: App.nextId }
            };

            try {
                const res = await fetch(PHP_SAVE_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ filename: name, content: content })
                });
                const txt = await res.text();
                const jsonStart = txt.search(/\[|\{/);
                const data = JSON.parse(txt.substring(jsonStart));

                if(res.ok) {
                    toast("Projet sauvegardé !", "success");
                    document.getElementById('modal-save').classList.remove('active');
                    App.currentFile = name;
                    updatePlayerLink(name);
                } else { throw new Error(data.message); }
            } catch(e) {
                toast("Erreur sauvegarde: " + e.message, "error");
            }
        }

        async function loadFromServer() {
            const name = document.getElementById('server-file-select').value;
            if(!name) return;
            
            try {
                const res = await fetch(`json/${name}`);
                if(!res.ok) throw new Error("Fichier introuvable");
                const data = await res.json();
                
                loadProject(data);
                App.currentFile = name;
                updatePlayerLink(name);
                document.getElementById('save-filename').value = name;
                document.getElementById('modal-json').classList.remove('active');
                toast(`Projet ${name} chargé`, "success");
            } catch(e) {
                toast("Erreur chargement: " + e.message, "error");
            }
        }

        function loadProject(d) {
            DOM.nLayer.innerHTML=''; DOM.sLayer.innerHTML=''; App.nodes={}; App.links={};
            if(d.construct) { 
                App.nodes=d.construct.nodes; App.links=d.construct.links; 
                App.view=d.construct.view || {x:0, y:0, z:1}; 
                App.nextId=d.construct.nextId || 100; 
            } else {
                // Importation depuis format "data_film" seul (mode compatibilité)
                let idx = 1;
                const pos = generateCircularPositions(Object.keys(d).length, innerWidth/2, innerHeight/2, 300);
                const map = {};
                Object.keys(d).forEach((k, i) => {
                    const id = `node-${idx++}`; map[k] = id;
                    App.nodes[id] = { id, name:k, x:pos[i].x, y:pos[i].y, url:d[k].url||'', timer:d[k].duree_choix||0, choices:[] };
                    if(d[k].choix) {
                        Object.values(d[k].choix).forEach(c => App.nodes[id].choices.push(c[0]));
                    }
                });
                // Liens
                Object.keys(d).forEach(k => {
                   if(d[k].choix) {
                       Object.values(d[k].choix).forEach(c => {
                           if(c[1] && map[c[1]]) mkLink(map[k], `${c[0]}_out`, map[c[1]], 'in');
                       });
                   } 
                });
                App.nextId = idx + 1;
            }
            Object.values(App.nodes).forEach(n => renderNode(n));
            Object.values(App.links).forEach(l => drawLink(l));
            saveState("Load");
        }

        // --- RENDER ENGINE ---
        function loop() {
            DOM.world.style.transform = `translate3d(${App.view.x}px, ${App.view.y}px, 0) scale(${App.view.z})`;
            DOM.vp.style.backgroundPosition = `${App.view.x}px ${App.view.y}px`;
            DOM.vp.style.backgroundSize = `${App.config.gridSize*App.view.z}px ${App.config.gridSize*App.view.z}px`;
            document.getElementById('zoom-level').innerText = Math.round(App.view.z*100)+'%';
            
            if(App.config.showMinimap && Date.now()%5 === 0) drawMini(); 
            requestAnimationFrame(loop);
        }
        function centerCam() { App.view.x=innerWidth/2; App.view.y=innerHeight/2; App.view.z=1; }
        function s2w(sx,sy) { return { x:(sx-App.view.x)/App.view.z, y:(sy-App.view.y)/App.view.z }; }

        // --- NODE LOGIC ---
        function createNode(x,y, data=null) {
            const id = data?.id || `node-${App.nextId++}`;
            // Fix ID collision
            const num = parseInt(id.split('-')[1]); if(!isNaN(num) && num>=App.nextId) App.nextId=num+1;
            
            const n = { id, name:data?.name||`Séquence ${num||App.nextId}`, x, y, url:data?.url||"", timer:data?.timer||4000, choices:data?.choices||[] };
            App.nodes[id]=n; renderNode(n); return n;
        }

        function renderNode(n) {
            let el = document.getElementById(n.id);
            if(!el) { el=document.createElement('div'); el.id=n.id; el.className='node'; DOM.nLayer.appendChild(el); }
            el.style.left=`${n.x}px`; el.style.top=`${n.y}px`;
            el.className = `node ${App.selection.has(n.id)?'selected':''}`;
            if(document.getElementById('search-input').value && !n.name.toLowerCase().includes(document.getElementById('search-input').value.toLowerCase())) el.style.opacity=0.2; else el.style.opacity=1;

            const vOpt = [`<option value="">-- Média --</option>`, ...App.videos.map(v=>`<option value="${v}" ${v===n.url?'selected':''}>${v.split('/').pop()}</option>`)].join('');
            const cHTML = n.choices.map((c,i)=>`
                <div class="flex items-center gap-2 mt-1 bg-white/5 p-1 rounded border border-transparent hover:border-white/10 group relative">
                    <input class="bg-transparent border-none text-xs text-gray-300 w-full outline-none ch-in" value="${c}" data-idx="${i}">
                    <i class="fa-solid fa-times text-gray-500 hover:text-red-500 cursor-pointer opacity-0 group-hover:opacity-100 del-ch" data-idx="${i}"></i>
                    <div class="port output" data-nid="${n.id}" data-pid="${c}_out" style="position:absolute; right:-8px;"></div>
                </div>
            `).join('');

            el.innerHTML = `
                <div class="node-header"><span class="font-bold text-gray-200 text-xs truncate pointer-events-none max-w-[200px]">${n.name}</span><i class="fa-solid fa-grip-lines text-gray-600"></i></div>
                <div class="p-3 flex flex-col gap-2">
                    <div class="flex justify-between items-center bg-black/20 p-1 rounded border border-white/5 relative">
                        <div class="port input" data-nid="${n.id}" data-pid="in" style="position:absolute; left:-8px;"></div>
                        <span class="text-[10px] font-bold uppercase text-green-500 ml-2">Entrée</span>
                    </div>
                    <select class="bg-[#111] border border-[#333] text-gray-300 text-xs rounded p-1 outline-none vid-sel">${vOpt}</select>
                    <div class="flex items-center gap-2 bg-[#111] border border-[#333] rounded p-1">
                        <i class="fa-regular fa-clock text-gray-500 text-xs"></i>
                        <input type="number" class="bg-transparent border-none text-gray-300 text-xs w-full outline-none tm-in" value="${n.timer}" placeholder="ms">
                    </div>
                    <div class="border-t border-[#333] pt-2">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[10px] font-bold text-gray-500">CHOIX</span>
                            <i class="fa-solid fa-plus text-blue-500 hover:text-blue-400 cursor-pointer add-ch ${n.choices.length>=4?'opacity-30':''}"></i>
                        </div>
                        <div>${cHTML}</div>
                    </div>
                </div>
            `;
            bindNode(el, n); updateLinks(n.id);
        }

        function bindNode(el, n) {
            const stop = e => e.stopPropagation();
            el.querySelectorAll('input, select, i.cursor-pointer').forEach(i=>i.addEventListener('mousedown', stop));
            el.querySelector('.node-header').ondblclick = () => openRen(n.id);
            el.querySelector('.vid-sel').onchange = e => { n.url=e.target.value; saveState("Vid"); };
            el.querySelector('.tm-in').onchange = e => { n.timer=parseInt(e.target.value)||0; saveState("Time"); };
            
            const add = el.querySelector('.add-ch');
            if(add) add.onclick = () => { if(n.choices.length<4) { n.choices.push(`Choix ${n.choices.length+1}`); renderNode(n); saveState("AddCh"); }};
            
            el.querySelectorAll('.del-ch').forEach(b=>b.onclick=e=>{
                const idx=parseInt(e.target.dataset.idx);
                const val=n.choices[idx];
                Object.keys(App.links).forEach(k=>{ if(App.links[k].srcNode===n.id && App.links[k].srcPort===`${val}_out`) delLink(k); });
                n.choices.splice(idx, 1); renderNode(n); saveState("DelCh");
            });

            el.querySelectorAll('.ch-in').forEach(i=>i.onchange=e=>{
                const idx=parseInt(e.target.dataset.idx);
                const old=n.choices[idx]; const val=e.target.value;
                Object.values(App.links).forEach(l=>{ if(l.srcNode===n.id && l.srcPort===`${old}_out`) l.srcPort=`${val}_out`; });
                n.choices[idx]=val; renderNode(n); updateLinks(n.id); saveState("RenCh");
            });
        }

        function delNode(id) {
            Object.keys(App.links).forEach(k=>{ if(App.links[k].srcNode===id || App.links[k].tgtNode===id) delLink(k); });
            document.getElementById(id).remove(); delete App.nodes[id];
        }

        // --- LINKS LOGIC ---
        function mkLink(sN, sP, tN, tP) {
            Object.values(App.links).forEach(l=>{ if(l.srcNode===sN && l.srcPort===sP) delLink(l.id); });
            const id = `L${Date.now()}_${Math.random().toString(36).substr(2,9)}`;
            App.links[id] = { id, srcNode:sN, srcPort:sP, tgtNode:tN, tgtPort:tP };
            drawLink(App.links[id]);
        }

        function drawLink(l) {
            let el = document.getElementById(l.id);
            if(!el) {
                el = document.createElementNS('http://www.w3.org/2000/svg','path');
                el.id=l.id; el.classList.add('cable'); el.setAttribute('marker-end','url(#arrow-marker)');
                el.addEventListener('click', e => { e.stopPropagation(); if(confirm("Supprimer ce lien ?")) { delLink(l.id); saveState("DelLink"); }});
                DOM.sLayer.appendChild(el);
            }
            updLink(l, el);
        }

        function updLink(l, el) {
            const s = document.querySelector(`.port[data-nid="${l.srcNode}"][data-pid="${l.srcPort}"]`);
            // Target peut être "in" générique
            const t = document.querySelector(`.port[data-nid="${l.tgtNode}"][data-pid="in"]`) || document.querySelector(`.port[data-nid="${l.tgtNode}"][data-pid="${l.tgtPort}"]`);
            if(!s || !t) { delLink(l.id); return; }
            
            const p1=getPos(s), p2=getPos(t);
            // Offset pour SVG large
            const x1=p1.x+100000, y1=p1.y+100000, x2=p2.x+100000, y2=p2.y+100000;
            const dist=Math.hypot(x2-x1, y2-y1), cp=Math.min(dist*0.5, 250);
            let d = App.config.lineStyle==='straight' ? `M ${x1} ${y1} L ${x2} ${y2}` : `M ${x1} ${y1} C ${x1+cp} ${y1}, ${x2-cp} ${y2}, ${x2} ${y2}`;
            el.setAttribute('d', d);
        }

        function getPos(el) { const r=el.getBoundingClientRect(); return { x:(r.left+r.width/2 - App.view.x)/App.view.z, y:(r.top+r.height/2 - App.view.y)/App.view.z }; }
        function updateLinks(nid) { Object.values(App.links).forEach(l=>{ if(l.srcNode===nid || l.tgtNode===nid) { const el=document.getElementById(l.id); if(el) updLink(l, el); }}); }
        function delLink(id) { document.getElementById(id)?.remove(); delete App.links[id]; }

        // --- INTERACTION ---
        function bindEvents() {
            window.addEventListener('wheel', e => {
                if(e.target.closest('.node-body') || e.target.closest('textarea')) return;
                e.preventDefault();
                const d = -Math.sign(e.deltaY)*0.1;
                const nz = Math.max(0.1, Math.min(4, App.view.z+d));
                const mx=e.clientX, my=e.clientY;
                App.view.x = mx - (mx - App.view.x) * (nz/App.view.z);
                App.view.y = my - (my - App.view.y) * (nz/App.view.z);
                App.view.z = nz;
            }, {passive:false});

            window.addEventListener('mousedown', e => {
                document.getElementById('context-menu').style.display='none';
                const t = e.target;
                if(t.closest('input') || t.closest('select')) return;

                if(e.button===1 || (e.button===0 && e.getModifierState('Space'))) {
                    App.drag = { type:'pan', sx:e.clientX, sy:e.clientY }; document.body.style.cursor='grabbing'; return;
                }

                const head = t.closest('.node-header');
                if(head && e.button===0) {
                    const id = head.closest('.node').id;
                    if(e.shiftKey) { if(App.selection.has(id)) App.selection.delete(id); else App.selection.add(id); }
                    else { if(!App.selection.has(id)) { App.selection.clear(); App.selection.add(id); } }
                    refreshSel();
                    const wm = s2w(e.clientX, e.clientY);
                    App.drag = { type:'node', sx:wm.x, sy:wm.y, moved:false }; e.stopPropagation(); return;
                }

                if(t.classList.contains('output')) {
                    App.linking = { nid:t.dataset.nid, pid:t.dataset.pid, el:t };
                    const p = document.createElementNS('http://www.w3.org/2000/svg','path');
                    p.id='temp-link'; p.classList.add('cable','dragging'); DOM.sLayer.appendChild(p);
                    e.stopPropagation(); return;
                }

                if(e.button===0 && t===DOM.vp) {
                    if(!e.shiftKey) { App.selection.clear(); refreshSel(); }
                    DOM.selBox.style.width='0'; DOM.selBox.style.height='0';
                    DOM.selBox.style.left=e.clientX+'px'; DOM.selBox.style.top=e.clientY+'px'; DOM.selBox.style.display='block';
                    App.drag = { type:'sel', sx:e.clientX, sy:e.clientY };
                }
            });

            window.addEventListener('mousemove', e => {
                const wm = s2w(e.clientX, e.clientY);
                if(App.drag) {
                    if(App.drag.type==='pan') {
                        App.view.x += e.clientX - App.drag.sx; App.view.y += e.clientY - App.drag.sy;
                        App.drag.sx=e.clientX; App.drag.sy=e.clientY;
                    }
                    else if(App.drag.type==='node') {
                        App.drag.moved=true;
                        const dx=wm.x - App.drag.sx, dy=wm.y - App.drag.sy;
                        App.selection.forEach(id=>{ 
                            const n=App.nodes[id]; n.x+=dx; n.y+=dy;
                            const el=document.getElementById(id); el.style.left=`${n.x}px`; el.style.top=`${n.y}px`;
                            updateLinks(id);
                        });
                        App.drag.sx=wm.x; App.drag.sy=wm.y;
                    }
                    else if(App.drag.type==='sel') {
                        const x=Math.min(e.clientX, App.drag.sx), y=Math.min(e.clientY, App.drag.sy);
                        const w=Math.abs(e.clientX - App.drag.sx), h=Math.abs(e.clientY - App.drag.sy);
                        DOM.selBox.style.left=x+'px'; DOM.selBox.style.top=y+'px'; DOM.selBox.style.width=w+'px'; DOM.selBox.style.height=h+'px';
                    }
                }
                if(App.linking) {
                    const p1 = getPos(App.linking.el);
                    const x1=p1.x+100000, y1=p1.y+100000, x2=wm.x+100000, y2=wm.y+100000;
                    const dist=Math.hypot(x2-x1, y2-y1), cp=Math.min(dist*0.5, 250);
                    let d = App.config.lineStyle==='straight' ? `M ${x1} ${y1} L ${x2} ${y2}` : `M ${x1} ${y1} C ${x1+cp} ${y1}, ${x2-cp} ${y2}, ${x2} ${y2}`;
                    document.getElementById('temp-link').setAttribute('d', d);
                }
            });

            window.addEventListener('mouseup', e => {
                if(App.drag?.type==='pan') document.body.style.cursor='default';
                
                if(App.drag?.type==='node' && App.drag.moved && App.config.snap) {
                    App.selection.forEach(id=>{
                        const n=App.nodes[id]; n.x=Math.round(n.x/App.config.gridSize)*App.config.gridSize; n.y=Math.round(n.y/App.config.gridSize)*App.config.gridSize;
                        const el=document.getElementById(id); el.style.left=`${n.x}px`; el.style.top=`${n.y}px`; updateLinks(id);
                    }); saveState("Move");
                }

                if(App.drag?.type==='sel') {
                    const b=DOM.selBox.getBoundingClientRect();
                    if(b.width>5) {
                        Object.values(App.nodes).forEach(n=>{
                            const r=document.getElementById(n.id).getBoundingClientRect();
                            if(b.left<r.right && b.right>r.left && b.top<r.bottom && b.bottom>r.top) App.selection.add(n.id);
                        });
                    }
                    DOM.selBox.style.display='none'; refreshSel();
                }

                if(App.linking) {
                    const t = e.target;
                    // Détection si on lâche sur une entrée valide
                    if(t.classList.contains('input')) {
                        const nid=t.dataset.nid, pid=t.dataset.pid;
                        if(nid!==App.linking.nid) { mkLink(App.linking.nid, App.linking.pid, nid, pid); saveState("Link"); }
                    } 
                    // NOUVEAU : Détection si on lâche dans le vide pour créer un nœud
                    else {
                        const wm = s2w(e.clientX, e.clientY);
                        const m = document.getElementById('context-menu');
                        m.style.left = e.clientX + 'px';
                        m.style.top = e.clientY + 'px';
                        m.style.display = 'block';
                        // On passe les valeurs primitives
                        m.innerHTML = `<div class="ctx-item text-blue-400 font-bold" onclick="ctxAction('link-create', ${wm.x}, ${wm.y}, '${App.linking.nid}', '${App.linking.pid}')"><i class="fa-solid fa-bolt mr-2"></i> Créer Scène liée</div>`;
                    }
                    document.getElementById('temp-link')?.remove(); App.linking=null;
                }
                App.drag=null;
            });

            document.getElementById('search-input').addEventListener('input', e => {
                const t = e.target.value.toLowerCase();
                Object.values(App.nodes).forEach(n => {
                    const el = document.getElementById(n.id);
                    if(!t || n.name.toLowerCase().includes(t)) el.style.opacity=1; else el.style.opacity=0.2;
                });
            });

            window.addEventListener('keydown', e => {
                if(e.target.tagName.match(/INPUT|SELECT|TEXTAREA/)) return;
                if(e.code==='Delete' && App.selection.size>0) { App.selection.forEach(id=>delNode(id)); App.selection.clear(); saveState("Del"); }
                if(e.ctrlKey) {
                    if(e.code==='KeyZ') { e.preventDefault(); loadHist(-1); }
                    if(e.code==='KeyY') { e.preventDefault(); loadHist(1); }
                    if(e.code==='KeyC') { const d=[]; App.selection.forEach(id=>d.push(App.nodes[id])); if(d.length){ App.clipboard=JSON.stringify(d); toast("Copié"); }}
                    if(e.code==='KeyV' && App.clipboard) {
                        const d=JSON.parse(App.clipboard); App.selection.clear();
                        d.forEach(n=>{ const nn=createNode(n.x+30, n.y+30, {...n,id:null,name:n.name+' (Copy)'}); App.selection.add(nn.id); });
                        refreshSel(); saveState("Paste");
                    }
                }
            });

            window.addEventListener('contextmenu', e => {
                e.preventDefault(); const m=document.getElementById('context-menu');
                const n = e.target.closest('.node');
                m.style.left=e.clientX+'px'; m.style.top=e.clientY+'px'; m.style.display='block';
                
                if(n) {
                    // Passage d'arguments primitives uniquement
                    m.innerHTML=`<div class="ctx-item" onclick="ctxAction('ren','${n.id}')">Renommer</div>
                                 <div class="ctx-item" onclick="ctxAction('dupe','${n.id}')">Dupliquer</div>
                                 <div class="ctx-item" style="color:#ef4444;" onclick="ctxAction('del','${n.id}')">Supprimer</div>`;
                } else {
                    const wm=s2w(e.clientX, e.clientY);
                    m.innerHTML=`<div class="ctx-item" onclick="ctxAction('add', ${wm.x}, ${wm.y})">Ajouter Scène</div>
                                 <div class="ctx-item" onclick="ctxAction('center')">Recentrer Vue</div>`;
                }
            });
            window.addEventListener('click', () => document.getElementById('context-menu').style.display='none');
        }

        // --- UI SETUP ---
        function setupUI() {
            // Menu Data
            const btnD = document.getElementById('btn-data-menu'); const mD = document.getElementById('menu-data');
            btnD.onclick = e => { e.stopPropagation(); mD.classList.toggle('active'); };
            document.onclick = e => { if(!e.target.closest('.dropdown')) mD.classList.remove('active'); };

            // Videos Upload
            const fIn = document.getElementById('input-videos');
            document.getElementById('btn-videos').onclick = () => fIn.click();
            fIn.onchange = e => { if(e.target.files.length) uploadFiles(e.target.files); };

            // Settings
            document.getElementById('btn-settings').onclick = () => {
                document.getElementById('set-minimap').checked = App.config.showMinimap;
                document.getElementById('set-snap').checked = App.config.snap;
                document.getElementById('set-style').value = App.config.lineStyle;
                document.getElementById('modal-settings').classList.add('active');
            };
            document.querySelectorAll('.close-modal').forEach(b => b.onclick = () => document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('active')));
            
            document.getElementById('set-minimap').onchange = e => { App.config.showMinimap=e.target.checked; saveConfig(); };
            document.getElementById('set-snap').onchange = e => { App.config.snap=e.target.checked; saveConfig(); };
            document.getElementById('set-style').onchange = e => { App.config.lineStyle=e.target.value; saveConfig(); Object.values(App.links).forEach(l=>updLink(l, document.getElementById(l.id))); };

            // Buttons
            document.getElementById('btn-add').onclick = () => { const c=s2w(innerWidth/2, innerHeight/2); createNode(c.x-140, c.y-100); saveState("Add"); };
            document.getElementById('btn-undo').onclick = () => loadHist(-1);
            document.getElementById('btn-redo').onclick = () => loadHist(1);
            
            // SAVE
            document.getElementById('btn-save').onclick = () => {
                document.getElementById('save-filename').value = App.currentFile;
                document.getElementById('modal-save').classList.add('active');
            };
            document.getElementById('btn-save-confirm').onclick = () => saveToServer();

            // DATA
            const mJson = document.getElementById('modal-json'), txt = document.getElementById('json-area');
            document.getElementById('act-export').onclick = () => {
                const df = buildDataFilm();
                txt.value = JSON.stringify(df, null, 2);
                mJson.classList.add('active');
                fetchJsonList();
            };
            document.getElementById('act-import').onclick = () => { 
                txt.value=''; mJson.classList.add('active'); fetchJsonList();
            };
            document.getElementById('btn-load-server').onclick = loadFromServer;
            
            document.getElementById('btn-copy').onclick = () => { txt.select(); document.execCommand('copy'); toast("Copié !"); };
            document.getElementById('btn-import-conf').onclick = () => {
                try {
                    const d = JSON.parse(txt.value);
                    loadProject(d); mJson.classList.remove('active'); toast("Importé");
                } catch(e) { alert("JSON Erreur"); }
            };
            
            document.getElementById('act-clear').onclick = () => { 
                if(confirm("Créer un nouveau projet ? Tout sera effacé.")) { 
                    App.nodes={}; App.links={}; DOM.nLayer.innerHTML=''; DOM.sLayer.innerHTML=''; 
                    createNode(innerWidth/2-140, innerHeight/2-100, {name:'intro'});
                    App.currentFile = "nouveau_projet.json";
                    updatePlayerLink(null);
                    saveState("New");
                } 
            };

            // Rename
            const mRen = document.getElementById('modal-rename'), rI = document.getElementById('rename-input');
            window.openRen = (id) => { App.renId=id; rI.value=App.nodes[id].name; mRen.classList.add('active'); rI.select(); };
            document.getElementById('btn-rename-conf').onclick = () => { if(App.renId && rI.value) { App.nodes[App.renId].name=rI.value; renderNode(App.nodes[App.renId]); saveState("Ren"); } mRen.classList.remove('active'); };
        }

        // --- HELPERS ---
        function updateBadge() { const b=document.getElementById('badge-videos'); b.innerText=App.videos.length; b.classList.remove('hidden'); }
        function saveConfig() { localStorage.setItem('storyswitch_config', JSON.stringify(App.config)); DOM.mini.style.display = App.config.showMinimap ? 'block':'none'; }
        function refreshSel() { document.querySelectorAll('.node').forEach(e=>e.classList.remove('selected')); App.selection.forEach(id=>document.getElementById(id)?.classList.add('selected')); }
        function updatePlayerLink(f) { 
            if(f) { DOM.playerLink.href=`index.php?projet=${encodeURIComponent(f)}`; DOM.playerLink.style.opacity=1; DOM.playerLink.style.pointerEvents='auto'; }
            else { DOM.playerLink.removeAttribute('href'); DOM.playerLink.style.opacity=0.5; DOM.playerLink.style.pointerEvents='none'; }
        }
        function generateCircularPositions(count, centerX, centerY, radius) {
            const positions = []; const angleStep = (2 * Math.PI) / count;
            for (let i = 0; i < count; i++) {
                const x = centerX + radius * Math.cos(i * angleStep - Math.PI / 2); 
                const y = centerY + radius * Math.sin(i * angleStep - Math.PI / 2);
                positions.push({ x: Math.round(x), y: Math.round(y) });
            } return positions;
        }

        // Action globale pour les clics contextuels
        window.ctxAction = (act, p1, p2, p3, p4) => {
            if(act==='ren') openRen(p1); 
            if(act==='del') { delNode(p1); saveState("Del"); }
            if(act==='add') { createNode(p1, p2); saveState("Add"); }
            if(act==='dupe') { const s=App.nodes[p1]; createNode(s.x+50, s.y+50, {...s,id:null,name:s.name+' (Copy)'}); saveState("Dupe"); }
            if(act==='center') centerCam();
            if(act==='link-create') {
                const newNode = createNode(p1, p2);
                mkLink(p3, p4, newNode.id, 'in');
                saveState("LinkCreate");
                toast("Nouvelle scène reliée !");
            }
            document.getElementById('context-menu').style.display='none';
        };

        function buildDataFilm() {
            const df={};
            Object.values(App.nodes).forEach(n=>{
                const ch={};
                n.choices.forEach((c,i)=>{
                    const l = Object.values(App.links).find(x=>x.srcNode===n.id && x.srcPort===`${c}_out`);
                    ch[`choix${i+1}`] = [c, l?App.nodes[l.tgtNode].name:null];
                });
                df[n.name] = { url:n.url, duree_choix:n.timer, ...(Object.keys(ch).length?{choix:ch}:{}) };
            });
            return df;
        }

        function drawMini() {
            const ctx=DOM.ctx, w=DOM.miniC.width=DOM.miniC.offsetWidth, h=DOM.miniC.height=DOM.miniC.offsetHeight;
            ctx.clearRect(0,0,w,h); const ns=Object.values(App.nodes); if(!ns.length) return;
            let minX=Infinity, minY=Infinity, maxX=-Infinity, maxY=-Infinity;
            ns.forEach(n=>{ minX=Math.min(minX,n.x); minY=Math.min(minY,n.y); maxX=Math.max(maxX,n.x); maxY=Math.max(maxY,n.y); });
            const p=2000, mw=maxX-minX+p*2, mh=maxY-minY+p*2, s=Math.min(w/mw, h/mh);
            ns.forEach(n=>{ 
                const x=(n.x-minX+p)*s, y=(n.y-minY+p)*s; 
                ctx.fillStyle = App.selection.has(n.id)?'#3b82f6':'#52525b'; ctx.fillRect(x,y, 280*s, 150*s); 
            });
            const vx=(-App.view.x/App.view.z-minX+p)*s, vy=(-App.view.y/App.view.z-minY+p)*s;
            DOM.miniV.style.left=vx+'px'; DOM.miniV.style.top=vy+'px'; 
            DOM.miniV.style.width=(innerWidth/App.view.z*s)+'px'; DOM.miniV.style.height=(innerHeight/App.view.z*s)+'px';
        }

        function toast(m, t='info') {
            const el = document.createElement('div'); el.className='toast'; el.innerHTML=`<i class="fa-solid fa-info-circle ${t==='success'?'text-green-400':(t==='error'?'text-red-400':'text-blue-400')}"></i> ${m}`;
            if(t==='success') el.style.borderLeftColor='#10b981'; if(t==='error') el.style.borderLeftColor='#ef4444';
            DOM.toasts.appendChild(el); setTimeout(()=>{el.style.opacity='0'; setTimeout(()=>el.remove(),300);},3000);
        }

        function saveState(lbl) {
            if(App.histIdx<App.history.length-1) App.history=App.history.slice(0, App.histIdx+1);
            App.history.push(JSON.stringify({n:App.nodes, l:App.links, i:App.nextId}));
            if(App.history.length>50) App.history.shift(); else App.histIdx++;
            document.getElementById('btn-undo').disabled=false;
        }
        function loadHist(d) {
            const ni=App.histIdx+d;
            if(ni>=0 && ni<App.history.length) {
                App.histIdx=ni; const o=JSON.parse(App.history[ni]);
                App.nodes=o.n; App.links=o.l; App.nextId=o.i;
                DOM.nLayer.innerHTML=''; DOM.sLayer.innerHTML='';
                Object.values(App.nodes).forEach(n=>renderNode(n)); Object.values(App.links).forEach(l=>drawLink(l));
                document.getElementById('btn-undo').disabled=ni<=0; document.getElementById('btn-redo').disabled=ni>=App.history.length-1;
            }
        }

        init();
    </script>
</body>
</html>