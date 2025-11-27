<?php
// 1. Définir le répertoire où sont stockés les fichiers JSON
$json_dir = 'json/';
$files = [];

// 2. Scanner le répertoire
if (is_dir($json_dir)) {
    // scandir() retourne un tableau de fichiers et répertoires
    $all_files = scandir($json_dir);

    // 3. Filtrer pour ne garder que les fichiers .json
    foreach ($all_files as $file) {
        // Ignorer '.' et '..' et s'assurer que c'est un fichier .json
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $files[] = $file;
        }
    }
} else {
    // Gérer l'erreur si le répertoire n'existe pas
    // Vous pourriez afficher un message d'erreur ou simplement laisser le tableau $files vide
    error_log("Le répertoire 'json/' est introuvable.");
}

// 4. Déterminer le fichier actuellement sélectionné (si un chargement a été tenté)
$selected_file = $_GET['scenario'] ?? ''; // Récupère le paramètre 'scenario' de l'URL
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur de Nœuds (Film Interactif)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Style de base pour l'éditeur */
        #editor {
            width: 2000vw;
            height: 2000vh;
            background-color: #2d3748;
            /* gray-800 */
            background-image: linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            position: relative;
            overflow: hidden;
            touch-action: none;
            /* Désactiver le pinch-zoom sur mobile */
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
        }

        /* Style pour les nœuds */
        .node {
            position: absolute;
            background-color: #4a5568;
            /* gray-700 */
            border: 2px solid #1a202c;
            /* gray-900 */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            min-width: 220px;
            z-index: 10;
        }

        .node-header {
            padding: 8px 12px;
            background-color: #2d3748;
            /* gray-800 */
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }

        .port {
            width: 12px;
            height: 12px;
            border: 2px solid #1a202c;
            /* gray-900 */
            flex-shrink: 0;
            /* Empêcher de rétrécir */
        }

        .input-port {
            cursor: pointer;
        }

        .output-port {
            cursor: crosshair;
        }

        /* Ligne de connexion temporaire */
        #temp-link {
            stroke: #e2e8f0;
            /* gray-300 */
            stroke-width: 3;
            stroke-dasharray: 5, 5;
            fill: none;
            pointer-events: none;
        }

        /* Ligne de connexion finale */
        .link {
            stroke: #f6e05e;
            /* yellow-400 */
            stroke-width: 3;
            fill: none;
            cursor: pointer;
            transition: stroke 0.15s ease-in-out;
        }

        .link:hover {
            stroke: #ef4444;
            /* red-500 */
        }

        /* Empêcher le défilement pendant le drag */
        body.no-scroll {
            overflow: hidden;
        }

        /* Styles pour les nouveaux champs de données */
        .data-field {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            padding: 0 12px;
        }

        .data-field label {
            font-size: 0.8rem;
            color: #cbd5e0;
            /* gray-400 */
            margin-right: 8px;
            min-width: 90px;
            /* Aligner les inputs */
        }

        .data-field input,
        .data-field select {
            font-size: 0.8rem;
            background-color: #2d3748;
            /* gray-800 */
            color: white;
            border: 1px solid #718096;
            /* gray-500 */
            border-radius: 4px;
            padding: 2px 4px;
            width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>

<body class="bg-gray-900 text-gray-100 font-sans h-screen m-0">

    <div class="flex flex-col h-screen">

        <header class="bg-gray-800 text-white p-2 flex justify-between items-center shadow-md z-20">
            <h1 class="text-xl font-semibold">Éditeur de Film Interactif</h1>
            <div class="flex gap-2 items-center"> <select id="json-file-select"
                    class="bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="" disabled selected>Choisir un scénario...</option>
                    <?php foreach ($files as $file_name): ?>
                        <option value="<?php echo htmlspecialchars($file_name); ?>" <?php echo ($file_name === $selected_file) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($file_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button id="load-selected-json-btn"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Charger Scénario
                </button>
                <button id="load-videos-btn"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Charger le dossier Vidéos
                </button>
                <input type="file" id="video-folder-input" webkitdirectory directory style="display: none;" />

                <button id="add-node-btn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Ajouter un Nœud
                </button>
                <button id="import-json-btn"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Importer JSON
                </button>
                <button id="export-json-btn"
                    class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-4 rounded transition duration-150">
                    Exporter JSON
                </button>

                <a href="index.html" target="_blank"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Lancer le Lecteur
                </a>
            </div>
        </header>

        <main class="flex-1 relative">
            <div id="editor"></div>
            <svg id="svg-container"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5;"></svg>
        </main>

    </div>

    <div id="json-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 id="json-modal-title" class="text-2xl font-bold text-white">JSON</h2>
                <button id="close-modal-btn" class="text-gray-400 hover:text-white text-3xl">&times;</button>
            </div>
            <textarea id="json-output"
                class="w-full h-64 bg-gray-900 text-gray-200 font-mono p-2 rounded border border-gray-600"></textarea>
            <p id="import-error-msg" class="text-red-500 text-sm mt-2"></p>
            <div class="mt-4 flex justify-end gap-3">
                <button id="save-to-player-btn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Sauvegarder pour le lecteur
                </button>
                <button id="json-modal-confirm-import"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                    Valider l'Importation
                </button>
            </div>
        </div>
    </div>


    <script type="module">
        // --- Variables Globales ---

        // État de l'éditeur
        const editor = document.getElementById('editor');
        const svgContainer = document.getElementById('svg-container');
        let graphData = { nodes: {}, links: {} }; // Modèle de données local
        let nextNodeId = 1; // Compteur local pour les nouveaux nœuds

        // État du Drag-and-Drop
        let draggedNode = null;
        let offset = { x: 0, y: 0 };

        // État de la Création de Lien
        let startPort = null;
        let currentLink = null;

        let videoFileList = []; // Stocke la liste des chemins vidéo (ex: "video/intro.mp4")

        // Éléments DOM
        const addNodeBtn = document.getElementById('add-node-btn');
        const exportJsonBtn = document.getElementById('export-json-btn');
        const importJsonBtn = document.getElementById('import-json-btn');
        const jsonModal = document.getElementById('json-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const jsonOutput = document.getElementById('json-output');
        const jsonModalTitle = document.getElementById('json-modal-title');
        const confirmImportBtn = document.getElementById('json-modal-confirm-import');
        const importErrorMsg = document.getElementById('import-error-msg');
        const saveToPlayerBtn = document.getElementById('save-to-player-btn');
        const loadVideosBtn = document.getElementById('load-videos-btn');
        const videoFolderInput = document.getElementById('video-folder-input');

        // --- Initialisation ---

        /**
         * Initialise l'application et les écouteurs d'événements.
         */
        function initApp() {
            try {
                // Charger la liste des vidéos au démarrage
                const savedVideoList = localStorage.getItem('storyswitch_videoList');
                if (savedVideoList) {
                    videoFileList = JSON.parse(savedVideoList);
                    console.log("Liste de vidéos chargée depuis le localStorage:", videoFileList);
                }

                setupEventListeners();
                console.log("Application initialisée (mode local).");
            } catch (error) {
                console.error("Erreur d'initialisation:", error);
                editor.innerHTML = `<div class="p-4 text-red-400">Erreur critique lors de l'initialisation. Vérifiez la console.</div>`;
            }
        }

        /**
         * Attache tous les écouteurs d'événements globaux.
         */
        function setupEventListeners() {
            // Boutons
            addNodeBtn.addEventListener('click', () => addNewNode(50, 50));
            exportJsonBtn.addEventListener('click', exportGraphToJson);
            importJsonBtn.addEventListener('click', showImportModal);

            // Modale
            closeModalBtn.addEventListener('click', () => jsonModal.classList.add('hidden'));
            confirmImportBtn.addEventListener('click', importGraphFromJson);
            saveToPlayerBtn.addEventListener('click', saveToPlayer);

            // Vidéos
            loadVideosBtn.addEventListener('click', () => videoFolderInput.click());
            videoFolderInput.addEventListener('change', handleVideoFolderSelect);

            // Éditeur (Drag-and-Drop et Liens)
            editor.addEventListener('mousedown', startDrag);
            editor.addEventListener('mousemove', drag);
            editor.addEventListener('mouseup', endDrag);
            editor.addEventListener('mouseleave', endDrag); // Arrêter si la souris quitte

            // Empêcher la sélection de texte pendant le drag
            editor.addEventListener('selectstart', (e) => e.preventDefault());
        }

        // --- Gestion des Données (Sauvegarde et Chargement) ---

        /**
         * Sauvegarde les données d'un nœud dans le modèle local (graphData)
         * et déclenche un re-rendu.
         * @param {Object} nodeData Les données complètes du nœud.
         */
        function saveNode(nodeData) {
            graphData.nodes[nodeData.id] = nodeData;

            // Re-rendu complet pour refléter les changements (url, choix, etc.)
            const existingEl = document.getElementById(nodeData.id);
            if (existingEl) {
                existingEl.remove();
            }
            renderNode(nodeData);
            updateAllLinks(); // Mettre à jour les liens vers ce nœud
        }

        /**
         * Sauvegarde les données d'un lien dans le modèle local (graphData)
         * et déclenche un re-rendu.
         * @param {Object} linkData Les données complètes du lien.
         */
        function saveLink(linkData) {
            graphData.links[linkData.id] = linkData;
            renderLink(linkData);
        }

        // --- Fonctions de Nœuds (Création et Rendu) ---

        /**
         * Crée l'élément DOM d'un nœud.
         * @param {Object} nodeData Les données du nœud.
         * @returns {HTMLElement} L'élément DOM du nœud.
         */
        function createNodeElement(nodeData) {
            const nodeEl = document.createElement('div');
            nodeEl.id = nodeData.id;
            nodeEl.className = 'node';

            // En-tête (pour le drag et le renommage)
            const header = document.createElement('div');
            header.className = 'node-header text-white font-semibold text-lg cursor-move';
            header.textContent = nodeData.name;
            nodeEl.appendChild(header);

            // --- Logique de Renommage du Nœud (double-clic) ---
            header.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                if (draggedNode) return; // Ne pas renommer pendant un drag

                header.classList.add('hidden');
                const input = document.createElement('input');
                input.type = 'text';
                input.value = nodeData.name;
                input.className = 'bg-gray-900 text-white font-semibold text-lg w-full rounded border border-blue-500 p-0.5 m-2 box-border';

                const saveRename = () => {
                    const newName = input.value.trim();
                    if (newName && newName !== nodeData.name) {
                        // Vérifier si le nom existe déjà
                        const nameExists = Object.values(graphData.nodes).some(n => n.name === newName && n.id !== nodeData.id);
                        if (nameExists) {
                            console.warn("Erreur: Ce nom de nœud est déjà utilisé.");
                            importErrorMsg.textContent = "Erreur: Ce nom de nœud est déjà utilisé.";
                            setTimeout(() => importErrorMsg.textContent = "", 3000);
                            header.textContent = nodeData.name; // Revert
                        } else {
                            nodeData.name = newName;
                            header.textContent = newName;
                            saveNode(nodeData); // Appel sync
                        }
                    } else {
                        header.textContent = nodeData.name;
                    }
                    input.remove();
                    header.classList.remove('hidden');
                };

                input.addEventListener('mousedown', (e) => e.stopPropagation());
                input.addEventListener('blur', saveRename);
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') input.blur();
                    else if (e.key === 'Escape') {
                        input.remove();
                        header.classList.remove('hidden');
                    }
                });

                header.parentNode.insertBefore(input, header.nextSibling);
                input.focus();
                input.select();
            });

            // Bouton de suppression du nœud
            const deleteBtn = document.createElement('button');
            deleteBtn.innerHTML = '&times;';
            deleteBtn.className = 'absolute top-1 right-2 text-red-500 hover:text-red-300 text-2xl font-bold leading-none';
            deleteBtn.title = 'Supprimer ce nœud';
            deleteBtn.addEventListener('mousedown', (e) => e.stopPropagation()); // Empêcher le drag
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteNode(nodeData.id);
            });
            nodeEl.appendChild(deleteBtn);

            // Conteneur principal (Entrée + Champs de données)
            const mainContent = document.createElement('div');
            mainContent.className = 'p-3';
            nodeEl.appendChild(mainContent);

            // 1. Port d'entrée principal "Entrée"
            const inputContainer = document.createElement('div');
            inputContainer.className = 'flex items-center mb-3';
            inputContainer.innerHTML = `<div class="port input-port bg-green-500 rounded-full mr-2"
                                             data-node-id="${nodeData.id}" data-port-id="Entrée"></div>
                                        <span class="text-gray-300">Entrée</span>`;
            if (nodeData.inputs && nodeData.inputs.includes('Entrée')) {
                mainContent.appendChild(inputContainer);
            }

            // 2. Champs de Données (URL, Duree, Duree Choix)
            const createDataField = (label, property, type = 'text', placeholder = '') => {
                const field = document.createElement('div');
                field.className = 'data-field';

                const labelEl = document.createElement('label');
                labelEl.textContent = label;
                field.appendChild(labelEl);

                const inputEl = document.createElement('input');
                inputEl.type = type;
                inputEl.value = nodeData[property] || (type === 'number' ? 0 : '');
                inputEl.placeholder = placeholder;
                inputEl.addEventListener('mousedown', e => e.stopPropagation()); // Ne pas dragger

                inputEl.addEventListener('change', (e) => {
                    let value = e.target.value;
                    if (type === 'number') {
                        value = parseInt(value, 10);
                        if (isNaN(value)) value = 0;
                    }
                    nodeData[property] = value;
                    saveNode(nodeData); // Sauvegarder au changement (appel sync)
                });

                field.appendChild(inputEl);
                mainContent.appendChild(field);
            };

            // Input URL (Menu déroulant)
            const urlField = document.createElement('div');
            urlField.className = 'data-field';

            const urlLabel = document.createElement('label');
            urlLabel.textContent = 'Vidéo:';
            urlField.appendChild(urlLabel);

            const selectEl = document.createElement('select');
            selectEl.className = 'w-full'; // Tailwind gérera le style via .data-field select
            selectEl.addEventListener('mousedown', e => e.stopPropagation()); // Ne pas dragger

            const defaultOpt = document.createElement('option');
            defaultOpt.value = "";
            defaultOpt.textContent = "-- Choisir une vidéo --";
            selectEl.appendChild(defaultOpt);

            // On utilise la liste globale (chargée au démarrage)
            videoFileList.forEach(videoPath => {
                const opt = document.createElement('option');
                opt.value = videoPath;
                const fileName = videoPath.substring(videoPath.lastIndexOf('/') + 1);
                opt.textContent = fileName;

                if (videoPath === nodeData.url) {
                    opt.selected = true; // Sélectionner la valeur sauvegardée
                }
                selectEl.appendChild(opt);
            });

            selectEl.addEventListener('change', () => {
                nodeData.url = selectEl.value;
                saveNode(nodeData); // Sauvegarder au changement
            });

            urlField.appendChild(selectEl);
            mainContent.appendChild(urlField);

            createDataField('Durée Choix (ms):', 'duree_choix', 'number', '4000');

            // --- Logique des Choix Personnalisés ---
            const separator = document.createElement('hr');
            separator.className = 'border-gray-600 my-2';
            mainContent.appendChild(separator);

            const choicesContainer = document.createElement('div');
            choicesContainer.className = 'flex flex-col gap-2';
            mainContent.appendChild(choicesContainer);

            if (!nodeData.choices) nodeData.choices = []; // Initialiser le tableau

            // Rendu de chaque choix
            nodeData.choices.forEach(choiceLabel => {
                const choiceRow = document.createElement('div');
                choiceRow.className = 'flex items-center justify-between text-sm text-gray-400 p-1 bg-gray-800 rounded';

                // 1. Label du Choix (Renommable) + Bouton Supprimer
                const centerDiv = document.createElement('div');
                centerDiv.className = 'flex-1 flex items-center justify-start min-w-0 px-1';

                const label = document.createElement('span');
                label.className = 'truncate cursor-text hover:text-white';
                label.textContent = choiceLabel;
                centerDiv.appendChild(label);

                // Renommage du Choix (double-clic)
                label.addEventListener('dblclick', (e) => {
                    e.stopPropagation();
                    const oldLabel = label.textContent;
                    label.classList.add('hidden');

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = oldLabel;
                    input.className = 'bg-gray-700 text-white text-sm w-full rounded border border-blue-500 p-0.5 -m-0.5';

                    const saveChoiceRename = () => {
                        const newLabel = input.value.trim();
                        if (newLabel && newLabel !== oldLabel) {
                            const index = nodeData.choices.indexOf(oldLabel);
                            if (index > -1) {
                                nodeData.choices[index] = newLabel;
                                label.textContent = newLabel;
                                saveNode(nodeData); // Appel sync
                            }
                        } else {
                            label.textContent = oldLabel;
                        }
                        input.remove();
                        label.classList.remove('hidden');
                    };

                    input.addEventListener('mousedown', (e) => e.stopPropagation());
                    input.addEventListener('blur', saveChoiceRename);
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') input.blur();
                        else if (e.key === 'Escape') {
                            input.remove();
                            label.classList.remove('hidden');
                        }
                    });

                    centerDiv.insertBefore(input, label.nextSibling);
                    input.focus();
                    input.select();
                });

                const deleteChoiceBtn = document.createElement('button');
                deleteChoiceBtn.innerHTML = '&times;';
                deleteChoiceBtn.className = 'text-red-500 hover:text-red-300 font-bold ml-2 text-base leading-none';
                deleteChoiceBtn.title = 'Supprimer ce choix';

                deleteChoiceBtn.addEventListener('mousedown', (e) => e.stopPropagation());
                deleteChoiceBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteLinksForPort(nodeData.id, `${choiceLabel}_out`);
                    nodeData.choices = nodeData.choices.filter(label => label !== choiceLabel);
                    saveNode(nodeData); // Appel sync
                });

                centerDiv.appendChild(deleteChoiceBtn);
                choiceRow.appendChild(centerDiv);

                // 2. Port de Sortie du Choix
                const outputSide = document.createElement('div');
                outputSide.className = 'flex items-center';
                outputSide.innerHTML = `<div class="port output-port bg-yellow-500 rounded-full ml-2"
                                             data-node-id="${nodeData.id}" data-port-id="${choiceLabel}_out"></div>`;
                choiceRow.appendChild(outputSide);

                choicesContainer.appendChild(choiceRow);
            });

            // Bouton "Ajouter un Choix"
            const addChoiceBtn = document.createElement('button');
            addChoiceBtn.textContent = 'Ajouter Choix';
            addChoiceBtn.className = 'mt-2 text-xs bg-blue-600 hover:bg-blue-700 text-white py-1 px-2 rounded w-full transition duration-150';

            if (nodeData.choices.length >= 4) {
                addChoiceBtn.disabled = true;
                addChoiceBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }

            addChoiceBtn.addEventListener('mousedown', (e) => e.stopPropagation());
            addChoiceBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (nodeData.choices.length < 4) {
                    const newChoiceLabel = `Choix ${nodeData.choices.length + 1}`;
                    nodeData.choices.push(newChoiceLabel);
                    saveNode(nodeData); // Appel sync
                }
            });

            mainContent.appendChild(addChoiceBtn);

            return nodeEl;
        }

        /**
         * Affiche un nœud sur l'éditeur (ou le met à jour).
         * @param {Object} nodeData Les données du nœud.
         */
        function renderNode(nodeData) {
            let nodeEl = document.getElementById(nodeData.id);

            if (!nodeEl) {
                nodeEl = createNodeElement(nodeData);
                editor.appendChild(nodeEl);
            }

            nodeEl.style.left = `${nodeData.position.x}px`;
            nodeEl.style.top = `${nodeData.position.y}px`;
        }

        /**
         * Ajoute un nouveau nœud (via bouton ou import).
         * @param {number} x Position X.
         * @param {number} y Position Y.
         * @param {Object} [importData=null] Données optionnelles pour l'import.
         */
        function addNewNode(x, y, importData = null) {
            let newNodeData;

            if (importData) {
                newNodeData = {
                    id: importData.id,
                    name: importData.name,
                    position: importData.position || { x, y },
                    inputs: importData.inputs || ['Entrée'],
                    outputs: importData.outputs || [],
                    choices: importData.choices || [],
                    url: importData.url || '',
                    duree_choix: importData.duree_choix || 0
                };
            } else {
                const newId = `node-${nextNodeId}`;
                nextNodeId++;
                newNodeData = {
                    id: newId,
                    name: `Scene ${newId.split('-')[1]}`,
                    position: { x, y },
                    inputs: ['Entrée'],
                    outputs: [],
                    choices: [],
                    url: '',
                    duree_choix: 0
                };
            }

            const idNum = parseInt(newNodeData.id.split('-')[1]);
            if (idNum >= nextNodeId) {
                nextNodeId = idNum + 1;
            }

            saveNode(newNodeData); // Appel sync (va sauvegarder ET rendre)
            return newNodeData;
        }

        // --- Fonctions de Liens (Création et Rendu) ---

        /**
         * Affiche un lien SVG sur l'éditeur.
         * @param {Object} linkData Les données du lien.
         */
        function renderLink(linkData) {
            const pathId = linkData.id;
            let path = document.getElementById(pathId);

            if (!path) {
                path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.id = pathId;
                path.classList.add('link');

                path.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteLink(pathId);
                });

                svgContainer.appendChild(path);
            }

            updateLinkPath(path, linkData.source, linkData.source_port, linkData.target, linkData.target_port);
        }

        /**
         * Met à jour la position (le 'd' attribute) d'un lien SVG.
         * @param {SVGPathElement} path L'élément SVG du lien.
         * @param {string} sourceNodeId ID du nœud source.
         * @param {string} sourcePortId ID du port source.
         * @param {string} targetNodeId ID du nœud cible.
         * @param {string} targetPortId ID du port cible.
         */
        function updateLinkPath(path, sourceNodeId, sourcePortId, targetNodeId, targetPortId) {
            const sourcePortEl = document.querySelector(`.port[data-node-id="${sourceNodeId}"][data-port-id="${sourcePortId}"]`);
            const targetPortEl = document.querySelector(`.port[data-node-id="${targetNodeId}"][data-port-id="${targetPortId}"]`);

            if (!sourcePortEl || !targetPortEl) {
                path.setAttribute('d', '');
                return;
            }

            const svgRect = svgContainer.getBoundingClientRect();

            const startRect = sourcePortEl.getBoundingClientRect();
            const endRect = targetPortEl.getBoundingClientRect();

            const startX = startRect.left + startRect.width / 2 - svgRect.left;
            const startY = startRect.top + startRect.height / 2 - svgRect.top;
            const endX = endRect.left + endRect.width / 2 - svgRect.left;
            const endY = endRect.top + endRect.height / 2 - svgRect.top;

            const dx = Math.abs(endX - startX);
            const ctrlX1 = startX + dx * 0.5;
            const ctrlY1 = startY;
            const ctrlX2 = endX - dx * 0.5;
            const ctrlY2 = endY;

            path.setAttribute('d', `M ${startX} ${startY} C ${ctrlX1} ${ctrlY1}, ${ctrlX2} ${ctrlY2}, ${endX} ${endY}`);
        }

        /**
         * Met à jour tous les liens visibles.
         */
        function updateAllLinks() {
            Object.values(graphData.links).forEach(linkData => {
                const path = document.getElementById(linkData.id);
                if (path) {
                    updateLinkPath(path, linkData.source, linkData.source_port, linkData.target, linkData.target_port);
                } else {
                    renderLink(linkData);
                }
            });
        }

        /**
         * Supprime un lien (DOM et modèle local).
         * @param {string} linkId L'ID du lien.
         */
        function deleteLink(linkId) {
            const path = document.getElementById(linkId);
            if (path) path.remove();
            delete graphData.links[linkId];
        }

        /**
         * Supprime tous les liens connectés à un port spécifique.
         * @param {string} nodeId L'ID du nœud.
         * @param {string} portId L'ID du port.
         */
        function deleteLinksForPort(nodeId, portId) {
            const linksToDelete = Object.values(graphData.links).filter(
                link => (link.source === nodeId && link.source_port === portId) ||
                    (link.target === nodeId && link.target_port === portId)
            );
            linksToDelete.forEach(link => {
                deleteLink(link.id);
            });
        }

        /**
         * Supprime un nœud et tous les liens associés.
         * @param {string} nodeId L'ID du nœud.
         */
        function deleteNode(nodeId) {
            // 1. Supprimer tous les liens connectés
            const linksToDelete = Object.values(graphData.links).filter(
                link => link.source === nodeId || link.target === nodeId
            );
            linksToDelete.forEach(link => deleteLink(link.id));

            // 2. Supprimer le nœud du DOM
            const nodeEl = document.getElementById(nodeId);
            if (nodeEl) nodeEl.remove();

            // 3. Supprimer du modèle local
            delete graphData.nodes[nodeId];
        }

        // --- Gestion du Drag-and-Drop ---

        /**
         * Démarre le drag d'un nœud ou d'un lien.
         * @param {MouseEvent} e L'événement mousedown.
         */
        function startDrag(e) {
            e.stopPropagation();

            const target = e.target;

            const nodeHeader = target.closest('.node-header');
            if (nodeHeader) {
                draggedNode = nodeHeader.closest('.node');
                draggedNode.style.zIndex = 20;

                const rect = draggedNode.getBoundingClientRect();
                const editorRect = editor.getBoundingClientRect();

                offset.x = e.clientX - rect.left + editorRect.left;
                offset.y = e.clientY - rect.top + editorRect.top;

                document.body.classList.add('no-scroll');
                return;
            }

            const portEl = target.closest('.output-port');
            if (portEl) {
                e.stopPropagation();

                startPort = {
                    element: portEl,
                    nodeId: portEl.dataset.nodeId,
                    portId: portEl.dataset.portId
                };

                if (!currentLink) {
                    currentLink = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    currentLink.id = 'temp-link';
                    svgContainer.appendChild(currentLink);
                }
                return;
            }
        }

        /**
         * Gère le mouvement pendant un drag.
         * @param {MouseEvent} e L'événement mousemove.
         */
        function drag(e) {
            e.preventDefault();

            if (draggedNode) {
                const editorRect = editor.getBoundingClientRect();
                let x = e.clientX - offset.x - editorRect.left;
                let y = e.clientY - offset.y - editorRect.top;

                x = Math.max(0, Math.min(x, editor.clientWidth - draggedNode.offsetWidth));
                y = Math.max(0, Math.min(y, editor.clientHeight - draggedNode.offsetHeight));

                draggedNode.style.left = `${x}px`;
                draggedNode.style.top = `${y}px`;

                const nodeData = graphData.nodes[draggedNode.id];
                if (nodeData) {
                    nodeData.position = { x, y };
                }

                updateAllLinks();
                return;
            }

            if (startPort) {
                const svgRect = svgContainer.getBoundingClientRect();
                const startRect = startPort.element.getBoundingClientRect();

                const startX = startRect.left + startRect.width / 2 - svgRect.left;
                const startY = startRect.top + startRect.height / 2 - svgRect.top;
                const endX = e.clientX - svgRect.left;
                const endY = e.clientY - svgRect.top;

                const dx = Math.abs(endX - startX);
                const ctrlX1 = startX + dx * 0.5;
                const ctrlY1 = startY;
                const ctrlX2 = endX - dx * 0.5;
                const ctrlY2 = endY;

                currentLink.setAttribute('d', `M ${startX} ${startY} C ${ctrlX1} ${ctrlY1}, ${ctrlX2} ${ctrlY2}, ${endX} ${endY}`);
                return;
            }
        }

        /**
         * Termine le drag (nœud ou lien).
         * @param {MouseEvent} e L'événement mouseup.
         */
        function endDrag(e) {
            e.stopPropagation();

            if (draggedNode) {
                draggedNode.style.zIndex = 10;
                const nodeData = graphData.nodes[draggedNode.id];
                if (nodeData) {
                    saveNode(nodeData); // Appel sync
                }
                draggedNode = null;
                document.body.classList.remove('no-scroll');
            }

            if (startPort) {
                const targetElement = e.target.closest('.input-port');

                if (targetElement && targetElement.dataset.nodeId !== startPort.nodeId) {
                    const newLink = {
                        id: `link-${Date.now()}`,
                        source: startPort.nodeId,
                        source_port: startPort.portId,
                        target: targetElement.dataset.nodeId,
                        target_port: targetElement.dataset.portId,
                    };

                    saveLink(newLink); // Appel sync
                }

                if (currentLink) {
                    currentLink.remove();
                    currentLink = null;
                }
                startPort = null;
            }
        }

        // --- Fonctions d'Import/Export JSON (Format data_film) ---

        /**
         * Exporte le graphe au format data_film.
         */
        function exportGraphToJson() {
            const dataFilm = {};
            const nodesById = graphData.nodes;

            try {
                for (const node of Object.values(nodesById)) {
                    const nodeName = node.name;
                    if (dataFilm[nodeName]) {
                        throw new Error(`Erreur d'export: Le nom de nœud "${nodeName}" est dupliqué.`);
                    }

                    const choixObj = {};
                    if (node.choices && node.choices.length > 0) {
                        for (const choiceLabel of node.choices) {
                            const portId = `${choiceLabel}_out`;
                            const link = Object.values(graphData.links).find(
                                l => l.source === node.id && l.source_port === portId
                            );

                            let targetNodeName = null;
                            if (link) {
                                const targetNode = nodesById[link.target];
                                if (targetNode) {
                                    targetNodeName = targetNode.name;
                                }
                            }
                            choixObj[choiceLabel] = [choiceLabel, targetNodeName];
                        }
                    }

                    dataFilm[nodeName] = {
                        url: node.url || '',
                        duree_choix: node.duree_choix || 0,
                    };

                    if (Object.keys(choixObj).length > 0) {
                        const finalChoixObj = {};
                        Object.values(choixObj).forEach((choiceArray, index) => {
                            finalChoixObj[`choix${index + 1}`] = choiceArray;
                        });
                        dataFilm[nodeName].choix = finalChoixObj;
                    }
                }

                const constructData = {
                    nodes: Object.values(graphData.nodes).map(n => ({ ...n })),
                    links: Object.values(graphData.links).map(l => ({ ...l }))
                };

                const finalExportData = {
                    data_film: dataFilm,
                    construct: constructData
                };

                jsonOutput.value = JSON.stringify(finalExportData, null, 2);
                jsonModalTitle.textContent = "Exporter au format data_film + construct";
                confirmImportBtn.classList.add('hidden');
                saveToPlayerBtn.classList.remove('hidden');
                importErrorMsg.textContent = "";
                jsonModal.classList.remove('hidden');
                jsonOutput.readOnly = true;

            } catch (error) {
                console.error(`Erreur lors de l'exportation: ${error.message}`);
                importErrorMsg.textContent = `Erreur lors de l'exportation: ${error.message}`;
                jsonModal.classList.remove('hidden');
                setTimeout(() => importErrorMsg.textContent = "", 3000);
            }
        }

        /**
         * Affiche la modale d'importation.
         */
        function showImportModal() {
            jsonOutput.value = "";
            jsonModalTitle.textContent = "Importer depuis data_film JSON";
            confirmImportBtn.classList.remove('hidden');
            saveToPlayerBtn.classList.add('hidden');
            importErrorMsg.textContent = "";
            jsonModal.classList.remove('hidden');
            jsonOutput.readOnly = false;
            jsonOutput.placeholder = "Collez votre objet data_film ici...";
        }

        /**
         * Importe un graphe depuis le format data_film.
         */
        function importGraphFromJson() {
            let importData;
            try {
                importData = JSON.parse(jsonOutput.value);
                if (typeof importData !== 'object' || Array.isArray(importData) || importData === null) {
                    throw new Error("Le JSON doit être un objet.");
                }
                importErrorMsg.textContent = "";
            } catch (error) {
                importErrorMsg.textContent = `Erreur JSON: ${error.message}`;
                return;
            }

            try {
                if (importData.construct && Array.isArray(importData.construct.nodes)) {
                    console.log("Détection du format 'construct'. Démarrage de l'importation...");
                    importFromConstruct(importData.construct); // Appel sync
                } else {
                    console.log("Détection du format 'data_film'. Démarrage de l'importation...");
                    importFromDataFilm(importData); // Appel sync
                }

                console.log("Importation terminée.");
                jsonModal.classList.add('hidden');
            } catch (error) {
                console.error("Erreur d'importation:", error);
                importErrorMsg.textContent = `Erreur d'importation: ${error.message}`;
            }
        }

        /**
         * Sauvegarde la partie data_film dans le localStorage.
         */
        function saveToPlayer() {
            try {
                const fullExportData = JSON.parse(jsonOutput.value);
                if (!fullExportData || !fullExportData.data_film) {
                    throw new Error("Format de données invalide, 'data_film' introuvable.");
                }
                const dataFilmString = JSON.stringify(fullExportData.data_film);
                localStorage.setItem('storyswitch_data', dataFilmString);

                saveToPlayerBtn.textContent = 'Sauvegardé !';
                saveToPlayerBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                saveToPlayerBtn.classList.add('bg-green-600');

                setTimeout(() => {
                    saveToPlayerBtn.textContent = 'Sauvegarder pour le lecteur';
                    saveToPlayerBtn.classList.remove('bg-green-600');
                    saveToPlayerBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    jsonModal.classList.add('hidden');
                }, 1500);

            } catch (error) {
                importErrorMsg.textContent = `Erreur de sauvegarde: ${error.message}`;
                console.error("Erreur sauvegarde localStorage:", error);
            }
        }

        /**
         * Importe un graphe depuis l'ancien format 'data_film'.
         * @param {Object} importData L'objet data_film.
         */
        function importFromDataFilm(importData) {
            clearCurrentGraph();

            // Charger la liste existante
            let existingList = [];
            const savedVideoList = localStorage.getItem('storyswitch_videoList');
            if (savedVideoList) {
                existingList = JSON.parse(savedVideoList);
            }

            const allUrls = Object.values(importData)
                .map(data => data.url)
                .filter(Boolean);
            // Fusionner et sauvegarder
            videoFileList = [...new Set([...existingList, ...allUrls])].sort();
            localStorage.setItem('storyswitch_videoList', JSON.stringify(videoFileList));

            const nodeNameMap = {};
            const nodePositions = generateCircularPositions(Object.keys(importData).length, editor.clientWidth / 2, editor.clientHeight / 2, 250);
            let index = 0;

            for (const nodeName in importData) {
                const data = importData[nodeName];
                const choiceLabels = data.choix ? Object.values(data.choix).map(arr => arr[0]) : [];

                const nodeToImport = {
                    id: `node-${index + 1}`,
                    name: nodeName,
                    position: nodePositions[index],
                    inputs: ['Entrée'],
                    outputs: [],
                    choices: choiceLabels,
                    url: data.url || '',
                    duree_choix: data.duree_choix || 0
                };

                const newNode = addNewNode(nodePositions[index].x, nodePositions[index].y, nodeToImport); // Appel sync
                graphData.nodes[newNode.id] = newNode;
                nodeNameMap[nodeName] = newNode.id;

                index++;
            }

            for (const sourceNodeName in importData) {
                const data = importData[sourceNodeName];
                if (!data.choix) continue;

                const sourceNodeId = nodeNameMap[sourceNodeName];
                if (!sourceNodeId) continue;

                for (const choiceKey in data.choix) {
                    const choiceArray = data.choix[choiceKey];
                    const choiceLabel = choiceArray[0];
                    const targetNodeName = choiceArray[1];

                    if (targetNodeName && nodeNameMap[targetNodeName]) {
                        const targetNodeId = nodeNameMap[targetNodeName];

                        const newLink = {
                            id: `link-${Date.now()}-${Math.random()}`,
                            source: sourceNodeId,
                            source_port: `${choiceLabel}_out`,
                            target: targetNodeId,
                            target_port: 'Entrée'
                        };

                        saveLink(newLink); // Appel sync
                        graphData.links[newLink.id] = newLink;
                    }
                }
            }
        }

        /**
         * Importe un graphe depuis le format 'construct' (structure 1:1).
         * @param {Object} constructData L'objet { nodes: [], links: [] }.
         */
        function importFromConstruct(constructData) {
            clearCurrentGraph();

            // Charger la liste existante
            let existingList = [];
            const savedVideoList = localStorage.getItem('storyswitch_videoList');
            if (savedVideoList) {
                existingList = JSON.parse(savedVideoList);
            }

            const allUrls = constructData.nodes
                .map(node => node.url)
                .filter(Boolean);
            // Fusionner et sauvegarder
            videoFileList = [...new Set([...existingList, ...allUrls])].sort();
            localStorage.setItem('storyswitch_videoList', JSON.stringify(videoFileList));

            for (const node of constructData.nodes) {
                const pos = node.position || { x: 50, y: 50 };
                // addNewNode va maintenant ignorer 'duree' s'il existe dans le JSON
                const newNode = addNewNode(pos.x, pos.y, node);
                graphData.nodes[newNode.id] = newNode;
            }

            for (const link of constructData.links) {
                saveLink(link);
                graphData.links[link.id] = link;
            }
        }

        /**
         * Vide le graphe actuel localement.
         */
        function clearCurrentGraph() {
            graphData = { nodes: {}, links: {} };
            editor.innerHTML = '';
            svgContainer.innerHTML = '';
            nextNodeId = 1;
            // On ne vide PAS videoFileList ici, elle persiste
        }

        /**
         * Génère des positions en cercle pour l'importation.
         */
        function generateCircularPositions(count, centerX, centerY, radius) {
            const positions = [];
            const angleStep = (2 * Math.PI) / count;
            for (let i = 0; i < count; i++) {
                const x = centerX + radius * Math.cos(i * angleStep - Math.PI / 2);
                const y = centerY + radius * Math.sin(i * angleStep - Math.PI / 2);
                positions.push({ x: Math.round(x), y: Math.round(y) });
            }
            return positions;
        }

        /**
         * Gère la sélection du dossier de vidéos.
         */
        function handleVideoFolderSelect(event) {
            const files = event.target.files;
            if (!files.length) return;

            const videoExtensions = ['.mp4', '.webm', '.ogg'];
            const newVideoPaths = [];

            for (const file of files) {
                const extension = file.name.slice(file.name.lastIndexOf('.')).toLowerCase();
                if (videoExtensions.includes(extension)) {

                    const relativePath = file.webkitRelativePath.replace(/\\/g, '/');

                    if (relativePath.split('/').length === 2) {
                        newVideoPaths.push(relativePath);
                    }
                }
            }

            // Fusionne avec la liste existante
            const combinedList = [...new Set([...videoFileList, ...newVideoPaths])];
            videoFileList = combinedList.sort();

            // Sauvegarde la liste mise à jour
            localStorage.setItem('storyswitch_videoList', JSON.stringify(videoFileList));

            // Re-render tous les nœuds existants pour mettre à jour leurs menus déroulants
            Object.values(graphData.nodes).forEach(nodeData => {
                const existingEl = document.getElementById(nodeData.id);
                if (existingEl) {
                    existingEl.remove(); // Enlève l'ancien nœud
                }
                renderNode(nodeData); // Re-crée le nœud avec la liste à jour
            });

            console.log("Vidéos chargées et sauvegardées:", videoFileList);

            videoFolderInput.value = "";
        }

        // --- Démarrage ---
        initApp();

    </script>
</body>

</html>