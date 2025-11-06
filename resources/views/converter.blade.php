<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Конвертор+ | Латиница ↔ Ћирилица</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: rgba(255, 255, 255, 0.95);
            padding: 25px 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .header h1 {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .loading-panel {
            display: none;
            align-items: center;
            gap: 12px;
            font-style: italic;
            color: #667eea;
            font-weight: 500;
        }

        .loading-panel.active {
            display: flex;
        }

        .progress-bar {
            width: 140px;
            height: 8px;
            background: rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
            width: 0%;
            border-radius: 10px;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 30px;
            border-bottom: none;
        }

        .tab {
            padding: 14px 26px;
            background: rgba(255, 255, 255, 0.7);
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #667eea;
        }

        .tab:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 15px;
            color: #4a5568;
        }

        textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            resize: vertical;
            min-height: 130px;
            transition: all 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select {
            width: 280px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 26px 0;
            flex-wrap: wrap;
        }

        button {
            padding: 13px 28px;
            border: none;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        button:active:not(:disabled) {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-copy {
            padding: 8px 18px;
            font-size: 13px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .file-upload-area {
            border: 3px dashed #cbd5e0;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            margin: 24px 0;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(102, 126, 234, 0.02);
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-2px);
        }

        .file-upload-area.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .file-list {
            margin: 20px 0;
            padding: 0;
            max-height: 220px;
            overflow-y: auto;
        }

        .file-item {
            padding: 14px 18px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            margin-bottom: 8px;
            border-radius: 10px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .file-item button {
            padding: 6px 12px;
            font-size: 12px;
            background: #f56565;
            color: white;
        }

        .file-item button:hover {
            background: #e53e3e;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: fadeIn 0.3s ease-in-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        input[type="file"] {
            display: none;
        }

        .column-selector {
            display: none;
            margin: 24px 0;
            padding: 20px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            border: 2px solid rgba(102, 126, 234, 0.1);
        }

        .column-selector.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .column-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .column-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .column-checkbox:hover {
            background: rgba(102, 126, 234, 0.08);
        }

        .column-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .column-checkbox label {
            cursor: pointer;
            margin: 0;
            font-weight: 500;
            color: #4a5568;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5568d3 0%, #653a8b 100%);
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>⚡ Конвертор+</h1>
            <div class="loading-panel" id="loadingPanel">
                <span>Превод у току...</span>
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="progressBar"></div>
                </div>
                <span id="progressText">0%</span>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab(0)">📝 Превођење текста</button>
                <button class="tab" onclick="switchTab(1)">📄 DOCX превод</button>
                <button class="tab" onclick="switchTab(2)">📊 XLSX превод</button>
            </div>

            <!-- Tab 1: Text Conversion -->
            <div class="tab-content active" id="tab-0">
                <div class="form-group">
                    <label>Унесите текст:</label>
                    <textarea id="inputText" placeholder="Унесите текст за конвертовање..."></textarea>
                </div>

                <div class="button-group">
                    <button class="btn-secondary" onclick="convertText('latinica')">→ Конвертуј у латиницу</button>
                    <button class="btn-primary" onclick="convertText('cirilica')">→ Конвертуј у ћирилицу</button>
                </div>

                <div class="form-group">
                    <div class="result-header">
                        <label>Резултат:</label>
                        <button class="btn-copy" id="btnCopyResult" onclick="copyResult()" disabled>📋 Копирај</button>
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
                    <div class="upload-icon">📄</div>
                    <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Кликните или превуците DOCX фајлове овде</p>
                    <p style="font-size: 13px; color: #718096;">Максимална величина фајла: 10MB</p>
                    <input type="file" id="docxFiles" accept=".docx" multiple onchange="handleDocxFiles(this.files)">
                </div>

                <div id="docxFileList" class="file-list" style="display: none;"></div>

                <div class="button-group">
                    <button id="btnConvertDocx" class="btn-primary" onclick="convertDocx()" disabled>✨ Конвертуј DOCX</button>
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
                    <div class="upload-icon">📊</div>
                    <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Кликните или превуците XLSX фајлове овде</p>
                    <p style="font-size: 13px; color: #718096;">Максимална величина фајла: 10MB</p>
                    <input type="file" id="xlsxFiles" accept=".xlsx" multiple onchange="handleXlsxFiles(this.files)">
                </div>

                <div id="xlsxFileList" class="file-list" style="display: none;"></div>

                <div id="columnSelector" class="column-selector">
                    <label style="font-weight: 600; margin-bottom: 14px; display: block; color: #4a5568;">
                        Одаберите колоне које желите да прескочите:
                    </label>
                    <div id="columnList" class="column-list"></div>
                </div>

                <div class="button-group">
                    <button id="btnConvertXlsx" class="btn-primary" onclick="convertXlsx()" disabled>✨ Конвертуј XLSX</button>
                </div>

                <div id="xlsxAlert"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Made with ❤️ | Laravel 10 + PHP 8.1
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
                } else {
                    alert('Грешка: ' + (data.error || 'Непозната грешка'));
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
            const originalText = btn.textContent;
            btn.textContent = '✓ Копирано!';
            setTimeout(() => {
                btn.textContent = originalText;
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

            // Convert FileList to array first
            const filesArray = Array.from(files);

            // Check file sizes (max 20MB per file)
            const maxSize = 20 * 1024 * 1024; // 20MB in bytes
            const oversizedFiles = filesArray.filter(f => f.size > maxSize);
            if (oversizedFiles.length > 0) {
                showAlert('docxAlert', `❌ Фајл је превелик: ${oversizedFiles[0].name}. Максимална величина је 20MB.`, 'error');
                document.getElementById('docxFiles').value = '';
                return;
            }

            docxFiles = filesArray;
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

            // Convert FileList to array first
            const filesArray = Array.from(files);

            // Check file sizes (max 20MB per file)
            const maxSize = 20 * 1024 * 1024; // 20MB in bytes
            const oversizedFiles = filesArray.filter(f => f.size > maxSize);
            if (oversizedFiles.length > 0) {
                showAlert('xlsxAlert', `❌ Фајл је превелик: ${oversizedFiles[0].name}. Максимална величина је 20MB.`, 'error');
                document.getElementById('xlsxFiles').value = '';
                return;
            }

            xlsxFiles = filesArray;
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

                // Check if response has content
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned invalid response. File might be too large (max 20MB).');
                }

                const data = await response.json();
                if (data.success && data.headers) {
                    displayColumnSelector(data.headers);
                } else {
                    showAlert('xlsxAlert', '❌ ' + (data.error || 'Failed to load headers'), 'error');
                    console.error('Error loading headers:', data.error);
                }
            } catch (error) {
                showAlert('xlsxAlert', '❌ Грешка: ' + error.message, 'error');
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
                    <span>📎 ${file.name}</span>
                    <button onclick="removeFile('${containerId === 'docxFileList' ? 'docx' : 'xlsx'}', ${index})">✕ Уклони</button>
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

                    showAlert('docxAlert', '✅ DOCX превод успешно завршен!', 'success');
                    resetDocxForm();
                } else {
                    const errorData = await response.json();
                    showAlert('docxAlert', '❌ Грешка: ' + (errorData.error || 'Непозната грешка'), 'error');
                }
            } catch (error) {
                showAlert('docxAlert', '❌ Грешка при конвертовању', 'error');
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

                    showAlert('xlsxAlert', '✅ XLSX превод успешно завршен!', 'success');
                    resetXlsxForm();
                } else {
                    const errorData = await response.json();
                    showAlert('xlsxAlert', '❌ Грешка: ' + (errorData.error || 'Непозната грешка'), 'error');
                }
            } catch (error) {
                showAlert('xlsxAlert', '❌ Грешка при конвертовању', 'error');
                console.error(error);
            } finally {
                showLoading(false);
            }
        }

        // Show loading
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
    </script>
</body>
</html>
