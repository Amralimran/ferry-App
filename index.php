<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta charset="UTF-8">
    <title>Ferry Schedule App</title>
    <style>
        body { font-family: sans-serif; font-size: 4svmin; padding: 20px; display: flex; flex-direction: column; align-items: center; background: #000000; color: #f7f4f4; }
        .header { display: flex; justify-content: space-between; align-items: center; font-size: 4svmin; background: #474747; padding: 20px; width: 90vw; border-radius: 2svmin; box-shadow: 0 2px 4px rgba(254, 252, 252, 0.1); }
        .card { background: #464646; margin-top: 20px; padding: 20px; width: 90vw; border-radius: 2svmin; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        /* Side-by-side Header Row */
        .header-row { display: flex; gap: 20px; margin-top: 15px; margin-bottom: 5px; }
        .header-row h4 { flex: 1; margin: 0; font-size: 3svmin; color: #bbb; padding-bottom: 5px; border-bottom: 1px solid #444; }

        /* Split Screen Container */
        .split-container { display: flex; gap: 20px; }
        .split-pane { flex: 1; background: #333; padding: 15px; border-radius: 1.5svmin; max-height: 40vh; overflow-y: auto; }
        
        @media (max-width: 768px) {
            .split-container, .header-row { flex-direction: column; }
            .header-row h4 { margin-top: 10px; border-bottom: none; }
            .card, .header, #newsBanner { width: 92vw !important; }
        }

        /* News Ticker Banner */
        #newsBanner { background: #ff9800; color: #000; padding: 15px; width: 80vw; border-radius: 2svmin; margin-bottom: 20px; display: none; font-weight: bold; cursor: pointer; }
        .news-snippet { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; }
        .see-more { font-size: 0.8em; text-decoration: underline; color: #002d62; margin-top: 5px; display: inline-block; }

        /* Modal Styling */
        #newsModal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .modal-content { background: #333; padding: 30px; width: 70vw; border-radius: 2svmin; max-height: 80vh; overflow-y: auto; position: relative; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; cursor: pointer; color: #fff; }

        .countdown { font-size: 8svmin; font-weight: bold; border-radius: 2svmin; padding: 10px 0; }
        
        /* Live Countdown Color States */
        .color-green { color: #4caf50; }
        .color-yellow { color: #ffeb3b; }
        .color-red { color: #f44336; }

        .pier-selector { display: flex; gap: 10px; margin-top: 15px; }
        .pier-btn { flex: 1; padding: 2svmin; border: none; border-radius: 1svmin; background: #2c2c2c; color: #fff; font-weight: bold; cursor: pointer; font-size: 4svmin; }
        .pier-btn.active { background: #007bff; }

        .holiday-btn { padding: 2svmin; border: none; border-radius: 1svmin; background: #2c2c2c; color: #fff; font-weight: bold; cursor: pointer; font-size: 4svmin; }
        .holiday-btn.active { background: #67d66a; color: #000; }

        #scheduleList { list-style: none; padding: 0; margin: 0; }
        #scheduleList li { padding: 8px 10px; margin: 4px 0; border-radius: 4px; background: #2c2c2c; }
        .highlight-ferry { background: #007bff !important; color: #fff; font-weight: bold; }

        /* Weather List Styling */
        .weather-list { list-style: none; padding: 0; margin: 0; }
        .weather-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; margin: 4px 0; border-radius: 4px; background: #2c2c2c; font-size: 0.7em; }
        .highlight-weather { background: #0056b3 !important; font-weight: bold; }
    </style>
</head>
<body>

<!-- News Ticker Banner -->
<div id="newsBanner" onclick="openModal()">
    <div id="newsText" class="news-snippet"></div>
    <span class="see-more">...see more</span>
</div>

<!-- Modal for Full News -->
<div id="newsModal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Special Arrangements & News</h3>
        <p id="modalFullText" style="white-space: pre-line; line-height: 1.5;"></p>
    </div>
</div>

<div class="header">
    <h2>Ferry<br>Schedule</h2>
    <button id="holidayBtn" class="holiday-btn" onclick="toggleHoliday()">Public Holiday</button>
</div>

<div class="card">
    <div id="locationStatus">Defaulting to Central. Checking location...</div>
    <div class="pier-selector">
        <button id="btnCentral" class="pier-btn" onclick="setManualPier('Central')">Central</button>
        <button id="btnMuiWo" class="pier-btn" onclick="setManualPier('Mui Wo')">Mui Wo</button>
    </div>
</div>

<div class="card" id="scheduleCard" style="display:none;">
    <h3 id="pierTitle" style="margin-top:0;"></h3>
    <div class="countdown color-green" id="countdownText">00:00:00</div>
    <div id="nextFerryTimeLabel" style="font-size: 3svmin; opacity: 0.8; margin-bottom: 15px;"></div>
    <hr style="border-color: #555;">

    <!-- Side-by-side Titles -->
    <div class="header-row">
        <h4>Today Schedule</h4>
        <h4 id="weatherHeader">Hourly Weather</h4>
    </div>

    <!-- Split Screen: Left = Schedule, Right = Weather -->
    <div class="split-container">
        <div class="split-pane">
            <ul id="scheduleList" style="list-style:none; padding:0; margin:0;"></ul>
        </div>
        <div class="split-pane">
            <ul id="weatherList" class="weather-list" style="list-style:none; padding:0; margin:0;">
                <li style="text-align:center; background:none;">Loading weather...</li>
            </ul>
        </div>
    </div>
</div>

<script>
let currentSelectedPier = 'Central';
let isHolidayActive = false;
let targetTimestamp = null;
let countdownInterval = null;
let cachedSchedules = [];
let nextFerryIdx = -1;

const piers = {
    'Central': { lat: 22.2868, lon: 114.1577 },
    'Mui Wo': { lat: 22.2678, lon: 114.0016 }
};

function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a = Math.sin(dLat/2)**2 + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon/2)**2;
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
}

function deg2rad(deg) { return deg * (Math.PI/180); }

function initApp() {
    setManualPier('Central');

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const distCentral = getDistanceFromLatLonInKm(position.coords.latitude, position.coords.longitude, piers['Central'].lat, piers['Central'].lon);
            const distMuiWo = getDistanceFromLatLonInKm(position.coords.latitude, position.coords.longitude, piers['Mui Wo'].lat, piers['Mui Wo'].lon);

            if (distMuiWo < distCentral) {
                currentSelectedPier = 'Mui Wo';
                document.getElementById('locationStatus').innerText = `Closest Pier Detected: Mui Wo`;
                fetchSchedule('Mui Wo');
            } else {
                document.getElementById('locationStatus').innerText = `Closest Pier Detected: Central`;
            }
        }, () => {
            document.getElementById('locationStatus').innerText = 'Geolocation unavailable. Staying on Central.';
        });
    } else {
        document.getElementById('locationStatus').innerText = 'Geolocation not supported. Staying on Central.';
    }
}

function setManualPier(pier) {
    currentSelectedPier = pier;
    document.getElementById('locationStatus').innerText = `Manually selected: ${pier}`;
    fetchSchedule(pier);
}

function toggleHoliday() {
    isHolidayActive = !isHolidayActive;
    document.getElementById('holidayBtn').classList.toggle('active', isHolidayActive);
    fetchSchedule(currentSelectedPier);
}

function fetchSchedule(pier) {
    document.getElementById('btnCentral').classList.toggle('active', pier === 'Central');
    document.getElementById('btnMuiWo').classList.toggle('active', pier === 'Mui Wo');

    fetch(`get_schedule.php?pier=${encodeURIComponent(pier)}&holiday=${isHolidayActive ? 1 : 0}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('scheduleCard').style.display = 'block';
            document.getElementById('pierTitle').innerText = `Departing from: ${data.pier}`;
            
            const newsBanner = document.getElementById('newsBanner');
            if (data.news && data.news.trim() !== "") {
                newsBanner.style.display = 'block';
                document.getElementById('newsText').innerText = data.news;
                document.getElementById('modalFullText').innerText = data.news;
            } else {
                newsBanner.style.display = 'none';
            }

            targetTimestamp = data.target_timestamp;
            cachedSchedules = data.schedules;
            nextFerryIdx = data.next_index;

            if (nextFerryIdx !== -1 && cachedSchedules[nextFerryIdx]) {
                document.getElementById('nextFerryTimeLabel').innerText = `Next Ferry at: ${cachedSchedules[nextFerryIdx]}`;
            }

            renderScheduleList();
            fetchWeather(pier);
            startCountdownEngine();
        });
}

function startCountdownEngine() {
    if (countdownInterval) clearInterval(countdownInterval);

    function updateTimer() {
        if (!targetTimestamp) return;
        const now = Math.floor(Date.now() / 1000);
        const diff = targetTimestamp - now;

        const countdownEl = document.getElementById('countdownText');

        if (diff <= 0) {
            countdownEl.innerText = "00:00:00";
            fetchSchedule(currentSelectedPier);
            return;
        }

        const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
        const seconds = String(diff % 60).padStart(2, '0');

        countdownEl.innerText = `in ${hours}:${minutes}:${seconds}`;

        countdownEl.className = "countdown";
        if (diff <= 60) {
            countdownEl.classList.add('color-red');
        } else if (diff <= 180) {
            countdownEl.classList.add('color-yellow');
        } else {
            countdownEl.classList.add('color-green');
        }
    }

    updateTimer();
    countdownInterval = setInterval(updateTimer, 1000);
}

function renderScheduleList() {
    let listHtml = '';
    cachedSchedules.forEach((time, index) => {
        let highlightClass = (index === nextFerryIdx) ? 'highlight-ferry' : '';
        // Give each item an ID so we can automatically jump/scroll to it
        listHtml += `<li id="schedule-item-${index}" class="${highlightClass}">${time}</li>`;
    });
    
    const container = document.getElementById('scheduleList');
    container.innerHTML = listHtml;

    // Scroll to the next upcoming ferry automatically on load
    if (nextFerryIdx !== -1) {
        const targetElement = document.getElementById(`schedule-item-${nextFerryIdx}`);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

async function fetchWeather(pierName) {
    const coords = piers[pierName] || piers['Central'];
    const weatherHeader = document.getElementById('weatherHeader');
    const weatherList = document.getElementById('weatherList');
    
    weatherHeader.innerText = `${pierName} Weather`;
    weatherList.innerHTML = `<li style="text-align:center; background:none;">Loading weather...</li>`;

    try {
        const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${coords.lat}&longitude=${coords.lon}&hourly=temperature_2m,weathercode&timezone=auto`);
        const data = await response.json();

        let html = '';
        const now = new Date();
        const currentHourStr = String(now.getHours()).padStart(2, '0') + ":00";
        let closestIndex = 0;
        let minDiff = Infinity;

        // Find the index closest to the current hour
        data.hourly.time.forEach((timeStr, idx) => {
            const hourPart = timeStr.split('T')[1];
            // Compare only date/hour strings or match current time slot
            const diff = Math.abs(new Date(timeStr) - now);
            if (diff < minDiff) {
                minDiff = diff;
                closestIndex = idx;
            }
        });

        // Loop through next 24 hours starting from the closest match
        let closestWeatherListItemId = '';
        for (let i = closestIndex; i < closestIndex + 24 && i < data.hourly.time.length; i++) {
            const timeFull = data.hourly.time[i];
            const timeDisplay = timeFull.split('T')[1];
            const temp = data.hourly.temperature_2m[i];
            const code = data.hourly.weathercode[i];
            const condition = getWeatherDescription(code);
            const isClosest = (i === closestIndex);
            
            if (isClosest) {
                closestWeatherListItemId = `weather-item-${i}`;
            }

            html += `
                <li id="weather-item-${i}" class="weather-item ${isClosest ? 'highlight-weather' : ''}">
                    <span><strong>${timeDisplay}</strong> - ${condition}</span>
                    <span>${temp}°C</span>
                </li>
            `;
        }

        weatherList.innerHTML = html;

        // Auto-scroll the weather pane to the current/closest hour
        if (closestWeatherListItemId) {
            const weatherEl = document.getElementById(closestWeatherListItemId);
            if (weatherEl) {
                weatherEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

    } catch (err) {
        weatherList.innerHTML = `<li style="text-align:center; background:none; color:#f44336;">Failed to load weather.</li>`;
    }
}

function getWeatherDescription(code) {
    if (code === 0) return "☀️ Clear";
    if (code >= 1 && code <= 3) return "⛅ Partly Cloudy";
    if (code >= 45 && code <= 48) return "🌫️ Foggy";
    if (code >= 51 && code <= 67) return "🌧️ Rain";
    if (code >= 71 && code <= 77) return "❄️ Snow";
    if (code >= 95) return "⚡ Thunderstorm";
    return "☁️ Overcast";
}

function openModal() { document.getElementById('newsModal').style.display = 'flex'; }
function closeModal() { document.getElementById('newsModal').style.display = 'none'; }

window.onload = initApp;
</script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered!', reg))
            .catch(err => console.log('Service Worker registration failed:', err));
    });
}
</script>
</body>
</html>
