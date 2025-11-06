<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Конвертор+</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .loading-panel {
            display: none;
            align-items: center;
            gap: 10px;
            font-style: italic;
            color: #666;
        }

        .loading-panel.active {
            display: flex;
        }

        .progress-bar {
            width: 120px;
            height: 12px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: #4CAF50;
            transition: width 0.3s ease;
            width: 0%;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 24px;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            margin-right: 4px;
        }

        .tab:hover {
            background: #e8e8e8;
        }

        .tab.active {
            background: #fff;
            border-bottom: 2px solid #fff;
            margin-bottom: -2px;
            font-weight: 600;
        }

        .tab-content {
            display: none;
            padding: 20px 0;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
        }

        textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }

        select {
            width: 250px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }

        button {
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #f0f0f0;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        button:hover {
            background: #e0e0e0;
        }

        button:active {
            transform: translateY(1px);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .btn-primary:hover:not(:disabled) {
            background: #45a049;
        }

        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-area:hover {
            border-color: #4CAF50;
            background: #f9f9f9;
        }

        .file-upload-area.dragover {
            border-color: #4CAF50;
            background: #e8f5e9;
        }

        .file-list {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
        }

        .file-item {
            padding: 8px;
            background: white;
            margin-bottom: 5px;
            border-radius: 3px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-item button {
            padding: 4px 8px;
            font-size: 12px;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        input[type="file"] {
            display: none;
        }

        .column-selector {
            display: none;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .column-selector.active {
            display: block;
        }

        .column-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .column-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .column-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Конвертор+</h1>
            <div class="loading-panel" id="loadingPanel">
                <span>Превод у току...</span>
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="progressBar"></div>
                </div>
                <span id="progressText">0%</span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab(0)">Превођење текста</button>
            <button class="tab" onclick="switchTab(1)">DOCX превод</button>
            <button class="tab" onclick="switchTab(2)">XLSX превод</button>
        </div>

        <!-- Tab 1: Text Conversion -->
        <div class="tab-content active" id="tab-0">
            <div class="form-group">
                <label>Унесите текст:</label>
                <textarea id="inputText" placeholder="Унесите текст за конвертовање..."></textarea>
            </div>

            <div class="button-group">
                <button onclick="convertText('latinica')">Конвертуј у латиницу</button>
                <button onclick="convertText('cirilica')">Конвертуј у ћирилицу</button>
            </div>

            <div class="form-group">
                <div class="result-header">
                    <label>Резултат:</label>
                    <button id="btnCopyResult" onclick="copyResult()" disabled>📋 Копирај</button>
                </div>
                <textarea id="outputText" readonly placeholder="Резултат ће се приказати овде..."></textarea>
            </div>
        </div>

        <!-- Tab 2: DOCX Conversion -->
        <div class="tab-content" id="tab-1">
            <div class="form-group">
                <label>Изаберите правац превођења:</label>
                <select id="docxDirection">
                    <option value="">-- Одаберите --</option>
                    <option value="cirilica">Ћирилица</option>
                    <option value="latinica">Латиница</option>
                </select>
            </div>

            <div class="file-upload-area" id="docxUploadArea" onclick="document.getElementById('docxFiles').click()">
                <p>📄 Кликните или превуците DOCX фајлове овде</p>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">Максимална величина фајла: 10MB</p>
                <input type="file" id="docxFiles" accept=".docx" multiple onchange="handleDocxFiles(this.files)">
            </div>

            <div id="docxFileList" class="file-list" style="display: none;"></div>

            <div class="button-group">
                <button id="btnConvertDocx" class="btn-primary" onclick="convertDocx()" disabled>Конвертуј</button>
            </div>

            <div id="docxAlert"></div>
        </div>

        <!-- Tab 3: XLSX Conversion -->
        <div class="tab-content" id="tab-2">
            <div class="form-group">
                <label>Изаберите правац превођења:</label>
                <select id="xlsxDirection">
                    <option value="">-- Одаберите --</option>
                    <option value="cirilica">Ћирилица</option>
                    <option value="latinica">Латиница</option>
                </select>
            </div>

            <div class="file-upload-area" id="xlsxUploadArea" onclick="document.getElementById('xlsxFiles').click()">
                <p>📊 Кликните или превуците XLSX фајлове овде</p>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">Максимална величина фајла: 10MB</p>
                <input type="file" id="xlsxFiles" accept=".xlsx" multiple onchange="handleXlsxFiles(this.files)">
            </div>

            <div id="xlsxFileList" class="file-list" style="display: none;"></div>

            <div id="columnSelector" class="column-selector">
                <label style="font-weight: 600; margin-bottom: 10px; display: block;">
                    Одаберите колоне које желите да прескочите:
                </label>
                <div id="columnList" class="column-list"></div>
            </div>

            <div class="button-group">
                <button id="btnConvertXlsx" class="btn-primary" onclick="convertXlsx()" disabled>Конвертуј</button>
            </div>

            <div id="xlsxAlert"></div>
        </div>
    </div>

    <script>
        let currentTab = 0;
        let docxFiles = [];
        let xlsxFiles = [];
        let xlsxHeaders = {};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Tab switching
        function switchTab(index) {
            document.querySelectorAll('.tab').forEach((tab, i) => {
                tab.classList.toggle('active', i === index);
            });
            document.querySelectorAll('.tab-content').forEach((content, i) => {
                content.classList.toggle('active', i === index);
            });
            currentTab = index;
        }

        // Text conversion
        async function convertText(direction) {
            const inputText = document.getElementById('inputText').value;
            if (!inputText.trim()) {
                alert('Молимо унесите текст за конвертовање');
                return;
            }

            try {
                const response = await fetch('/convert/text', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ text: inputText, direction })
                });

                const data = await response.json();
                if (data.success) {
                    document.getElementById('outputText').value = data.result;
                    document.getElementById('btnCopyResult').disabled = false;
                }
            } catch (error) {
                alert('Грешка при конвертовању текста');
                console.error(error);
            }
        }

        // Copy result
        function copyResult() {
            const outputText = document.getElementById('outputText');
            outputText.select();
            document.execCommand('copy');

            const btn = document.getElementById('btnCopyResult');
            btn.textContent = '✓ Копирано!';
            setTimeout(() => {
                btn.textContent = '📋 Копирај';
            }, 1500);
        }

        // DOCX file handling
        function handleDocxFiles(files) {
            const direction = document.getElementById('docxDirection').value;
            if (!direction) {
                alert('Молимо одаберите правац превођења');
                document.getElementById('docxFiles').value = '';
                return;
            }

            docxFiles = Array.from(files);
            displayFileList('docxFileList', docxFiles);
            document.getElementById('btnConvertDocx').disabled = docxFiles.length === 0;
        }

        // XLSX file handling
        async function handleXlsxFiles(files) {
            const direction = document.getElementById('xlsxDirection').value;
            if (!direction) {
                alert('Молимо одаберите правац превођења');
                document.getElementById('xlsxFiles').value = '';
                return;
            }

            xlsxFiles = Array.from(files);
            displayFileList('xlsxFileList', xlsxFiles);

            // Get headers from first file
            if (xlsxFiles.length > 0) {
                await loadXlsxHeaders(xlsxFiles[0]);
            }

            document.getElementById('btnConvertXlsx').disabled = xlsxFiles.length === 0;
        }

        // Load XLSX headers
        async function loadXlsxHeaders(file) {
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('/xlsx/headers', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const data = await response.json();
                if (data.success && data.headers) {
                    displayColumnSelector(data.headers);
                }
            } catch (error) {
                console.error('Error loading headers:', error);
            }
        }

        // Display column selector
        function displayColumnSelector(headers) {
            const columnList = document.getElementById('columnList');
            columnList.innerHTML = '';

            headers.forEach(header => {
                const div = document.createElement('div');
                div.className = 'column-checkbox';
                div.innerHTML = `
                    <input type="checkbox" id="col_${header.column}" value="${header.name}">
                    <label for="col_${header.column}">${header.name}</label>
                `;
                columnList.appendChild(div);
            });

            document.getElementById('columnSelector').classList.add('active');
        }

        // Display file list
        function displayFileList(containerId, files) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            files.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.innerHTML = `
                    <span>${file.name}</span>
                    <button onclick="removeFile('${containerId === 'docxFileList' ? 'docx' : 'xlsx'}', ${index})">✕</button>
                `;
                container.appendChild(div);
            });

            container.style.display = files.length > 0 ? 'block' : 'none';
        }

        // Remove file
        function removeFile(type, index) {
            if (type === 'docx') {
                docxFiles.splice(index, 1);
                displayFileList('docxFileList', docxFiles);
                document.getElementById('btnConvertDocx').disabled = docxFiles.length === 0;
                if (docxFiles.length === 0) {
                    document.getElementById('docxFiles').value = '';
                }
            } else {
                xlsxFiles.splice(index, 1);
                displayFileList('xlsxFileList', xlsxFiles);
                document.getElementById('btnConvertXlsx').disabled = xlsxFiles.length === 0;
                if (xlsxFiles.length === 0) {
                    document.getElementById('xlsxFiles').value = '';
                    document.getElementById('columnSelector').classList.remove('active');
                }
            }
        }

        // Convert DOCX
        async function convertDocx() {
            const direction = document.getElementById('docxDirection').value;
            if (!direction || docxFiles.length === 0) return;

            showLoading(true);
            const formData = new FormData();
            formData.append('direction', direction);
            docxFiles.forEach(file => {
                formData.append('files[]', file);
            });

            try {
                const response = await fetch('/convert/docx', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = docxFiles.length === 1
                        ? docxFiles[0].name.replace('.docx', `_${direction}.docx`)
                        : `converted_${Date.now()}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);

                    showAlert('docxAlert', 'DOCX превод успешно завршен!', 'success');
                    resetDocxForm();
                } else {
                    showAlert('docxAlert', 'Грешка при конвертовању DOCX фајлова', 'error');
                }
            } catch (error) {
                showAlert('docxAlert', 'Грешка при конвертовању', 'error');
                console.error(error);
            } finally {
                showLoading(false);
            }
        }

        // Convert XLSX
        async function convertXlsx() {
            const direction = document.getElementById('xlsxDirection').value;
            if (!direction || xlsxFiles.length === 0) return;

            showLoading(true);

            // Get selected columns to skip
            const skipColumns = [];
            document.querySelectorAll('#columnList input[type="checkbox"]:checked').forEach(checkbox => {
                skipColumns.push(checkbox.value);
            });

            const formData = new FormData();
            formData.append('direction', direction);
            xlsxFiles.forEach((file, index) => {
                formData.append('files[]', file);
                // For now, apply same skip columns to all files
                formData.append(`skip_columns[${index}]`, JSON.stringify(skipColumns));
            });

            try {
                const response = await fetch('/convert/xlsx', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = xlsxFiles.length === 1
                        ? xlsxFiles[0].name.replace('.xlsx', `_${direction}.xlsx`)
                        : `converted_${Date.now()}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);

                    showAlert('xlsxAlert', 'XLSX превод успешно завршен!', 'success');
                    resetXlsxForm();
                } else {
                    showAlert('xlsxAlert', 'Грешка при конвертовању XLSX фајлова', 'error');
                }
            } catch (error) {
                showAlert('xlsxAlert', 'Грешка при конвертовању', 'error');
                console.error(error);
            } finally {
                showLoading(false);
            }
        }

        // Show loading
        function showLoading(show) {
            const panel = document.getElementById('loadingPanel');
            if (show) {
                panel.classList.add('active');
                updateProgress(0);
            } else {
                panel.classList.remove('active');
            }
        }

        // Update progress
        function updateProgress(percent) {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = percent + '%';
        }

        // Show alert
        function showAlert(containerId, message, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        // Reset forms
        function resetDocxForm() {
            docxFiles = [];
            document.getElementById('docxFiles').value = '';
            document.getElementById('docxFileList').style.display = 'none';
            document.getElementById('btnConvertDocx').disabled = true;
        }

        function resetXlsxForm() {
            xlsxFiles = [];
            document.getElementById('xlsxFiles').value = '';
            document.getElementById('xlsxFileList').style.display = 'none';
            document.getElementById('columnSelector').classList.remove('active');
            document.getElementById('btnConvertXlsx').disabled = true;
        }

        // Drag and drop support
        ['docxUploadArea', 'xlsxUploadArea'].forEach(areaId => {
            const area = document.getElementById(areaId);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                area.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                area.addEventListener(eventName, () => area.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                area.addEventListener(eventName, () => area.classList.remove('dragover'), false);
            });

            area.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (areaId === 'docxUploadArea') {
                    handleDocxFiles(files);
                } else {
                    handleXlsxFiles(files);
                }
            }, false);
        });

        // Simulate progress for better UX
        let progressInterval;
        function showLoading(show) {
            const panel = document.getElementById('loadingPanel');
            if (show) {
                panel.classList.add('active');
                let progress = 0;
                progressInterval = setInterval(() => {
                    progress += 5;
                    if (progress >= 90) {
                        clearInterval(progressInterval);
                    }
                    updateProgress(progress);
                }, 200);
            } else {
                clearInterval(progressInterval);
                updateProgress(100);
                setTimeout(() => {
                    panel.classList.remove('active');
                }, 300);
            }
        }
    </script>
</body>
</html>
