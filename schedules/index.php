<?php
// Handle saving file & toggle state via AJAX POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle toggle creation / deletion
    if (isset($input['toggle_special'])) {
        $tglPath = __DIR__ . '/special.tgl';
        if ($input['toggle_special'] === true) {
            file_put_contents($tglPath, '1'); // Creates special.tgl
        } else {
            if (file_exists($tglPath)) {
                unlink($tglPath); // Deletes special.tgl
            }
        }
        echo json_encode(['status' => 'success']);
        exit;
    }
    // ... rest of json file saving logic ...
}

$isSpecialActive = file_exists(__DIR__ . '/special.tgl');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule File Editor</title>
    <!-- Tabulator Stylesheet -->
    <link href="https://unpkg.com/tabulator-tables@6.2.5/dist/css/tabulator.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        h1 { font-size: 1.5rem; margin-bottom: 5px; }
        p.subtitle { color: #666; margin-top: 0; margin-bottom: 20px; }
        .toolbar {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .selector-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        select, button {
            padding: 8px 12px;
            font-size: 0.95rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        select { background: #fff; cursor: pointer; }
        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        button:hover { background: #0056b3; }
        #schedule-table {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        #statusMessage {
            margin-left: 10px;
            font-size: 0.9rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Schedule & Notice Editor</h1>
    <p class="subtitle">Direct server-side schedule management.</p>

    <div class="editor-toolbar" style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px; flex-wrap: wrap;">
    
    <!-- File Selector Dropdown -->
    <div class="selector-group">
        <label for="fileSelector"><strong>Select File:</strong></label>
        <select id="fileSelector" onchange="loadJSONFile(this.value)" style="padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
            <option value="central">central.json (Standard)</option>
            <option value="central_special">central_special.json (Special)</option>
            <option value="muiwo">muiwo.json (Standard)</option>
            <option value="muiwo_special">muiwo_special.json (Special)</option>
        </select>
    </div>

    <!-- Special Mode Toggle & Apply Button -->
    <div class="selector-group" style="background: #f8f9fa; padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd;">
        <label>
            <input type="checkbox" id="globalToggle" <?php echo $isSpecialActive ? 'checked' : ''; ?>> 
            <strong>Special Mode Active</strong>
        </label>
        <button onclick="applySpecialToggle()" style="margin-left: 8px; padding: 4px 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Apply Mode</button>
    </div>

    <!-- Action Buttons -->
    <div style="margin-left: auto; display: flex; gap: 10px;">
        <button onclick="addRow()" style="padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">+ Add Entry</button>
        <button onclick="saveJSONFile()" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Save to Server</button>
    </div>
</div>

    <!-- Tabulator Table Container -->
    <div id="schedule-table"></div>

    <!-- Tabulator JS -->
    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@6.2.5/dist/js/tabulator.min.js"></script>

    <script>
        let table;
        let currentFilename = 'central';

        function initTable(dataArray) {
            const tableData = dataArray.map((item, index) => ({ id: index + 1, value: item }));

            table = new Tabulator("#schedule-table", {
                data: tableData,
                layout: "fitColumns",
                editable: true,
                height: "450px",
                selectableRows: 1,
                columns: [
                    { title: "Schedule Entry / Notice Text", field: "value", editor: "input" },
                    { 
                        title: "", 
                        formatter: "buttonCross", 
                        width: 70, 
                        hozAlign: "center", 
                        cellClick: function(e, cell){ 
                            cell.getRow().delete(); 
                        } 
                    },
                ],
            });
        }
        async function applySpecialToggle() {
            const isActive = document.getElementById('globalToggle').checked;
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ toggle_special: isActive })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    alert('Special Mode state updated successfully on server!');
                } else {
                    alert('Failed to update special mode.');
                }
            } catch (err) {
                alert('Error communicating with server.');
            }
        }
        
        async function loadJSONFile(filename) {
            currentFilename = filename;
            try {
                // Add timestamp query parameter to bypass browser caching
                const response = await fetch(`${filename}.json?t=${new Date().getTime()}`);
                const dataArray = await response.json();
                
                if (table) {
                    table.setData(dataArray.map((item, index) => ({ id: index + 1, value: item })));
                } else {
                    initTable(dataArray);
                }
            } catch (err) {
                alert(`Could not load ${filename}.json. Make sure the file exists.`);
            }
        }

        function addRow() {
            const selectedRows = table.getSelectedRows();
            if (selectedRows.length > 0) {
                table.addRow({ value: "00:00" }, false, selectedRows[0])
                     .then(function(newRow){ newRow.select(); });
            } else {
                table.addRow({ value: "00:00" }, false);
            }
        }

        async function saveToServer() {
            const rows = table.getData();
            const cleanArray = rows.map(row => row.value);
            const statusSpan = document.getElementById('statusMessage');

            statusSpan.style.color = '#666';
            statusSpan.innerText = 'Saving...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filename: currentFilename, data: cleanArray })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    statusSpan.style.color = '#28a745';
                    statusSpan.innerText = 'Saved successfully!';
                } else {
                    statusSpan.style.color = '#dc3545';
                    statusSpan.innerText = 'Error saving!';
                }
            } catch (err) {
                statusSpan.style.color = '#dc3545';
                statusSpan.innerText = 'Network error!';
            }

            setTimeout(() => { statusSpan.innerText = ''; }, 3000);
        }

        loadJSONFile('central');
    </script>
</body>
</html>