<?php
header('Content-Type: application/json');

$pier = $_GET['pier'] ?? 'Central';
$isHolidayToggle = filter_var($_GET['holiday'] ?? false, FILTER_VALIDATE_BOOLEAN);

$dayOfWeek = (int)date('w'); // 0 = Sunday, 6 = Saturday, 1-5 = Weekdays
$isSunday = ($dayOfWeek === 0);
$isSaturday = ($dayOfWeek === 6);
$scheduleType = ($isSunday || $isHolidayToggle) ? 'sunday' : 'weekday';

$specialPath = __DIR__ . '/schedules/special.json';
$useSpecial = false;
$newsContent = "";
$rawSchedules = [];

// Check if special.json exists and read content
if (file_exists($specialPath)) {
    $specialData = json_decode(file_get_contents($specialPath), true);
    if (is_array($specialData) && count($specialData) > 0) {
        // Check the first element for the boolean flag
        if ($specialData[0] === true) {
            $useSpecial = true;
            // The second element is the news text, everything after is the schedule
            $newsContent = $specialData[1] ?? "";
            $rawSchedules = array_slice($specialData, 2);
        }
    }
}

// Fallback to standard unified JSON files if special mode is off or file doesn't exist
if (!$useSpecial) {
    $fileName = ($pier === 'Central') ? 'central.json' : 'muiwo.json';
    $filePath = __DIR__ . '/schedules/' . $fileName;
    
    if (file_exists($filePath)) {
        $rawSchedules = json_decode(file_get_contents($filePath), true);
    } else {
        // Fallback to legacy naming if needed
        $legacyName = '';
        if ($pier === 'Central') {
            $legacyName = ($scheduleType === 'sunday') ? 'central_holiday.json' : 'central_weekday.json';
        } else {
            $legacyName = ($scheduleType === 'sunday') ? 'muiwo_holiday.json' : 'muiwo_weekday.json';
        }
        $legacyPath = __DIR__ . '/schedules/' . $legacyName;
        if (file_exists($legacyPath)) {
            $rawSchedules = json_decode(file_get_contents($legacyPath), true);
        }
    }
}

if (!is_array($rawSchedules) || empty($rawSchedules)) {
    echo json_encode(['error' => 'Schedule data not found']);
    exit;
}

// Get today's lowercase English day name (e.g., 'monday', 'tuesday', 'wednesday', etc.)
$currentDayName = strtolower(date('l')); 
$isHolidayToggle = filter_var($_GET['holiday'] ?? false, FILTER_VALIDATE_BOOLEAN);

$cleanedSchedules = [];
foreach ($rawSchedules as $timeStr) {
    $timeStrLower = strtolower($timeStr);

    // List of all possible day tags to look out for
    $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    $matchedDayTag = null;
    foreach ($daysOfWeek as $day) {
        if (strpos($timeStrLower, $day) !== false) {
            $matchedDayTag = $day;
            break;
        }
    }

    // Filtering logic:
    if ($isHolidayToggle) {
        // If public holiday mode is on, treat it like Sunday (drop any specific day tags unless it's explicitly sunday)
        if ($matchedDayTag && $matchedDayTag !== 'sunday') {
            continue;
        }
    } else {
        // If a specific day tag is found (e.g., 'tuesday'), ONLY include it if today matches that exact day
        if ($matchedDayTag !== null && $matchedDayTag !== $currentDayName) {
            continue;
        }
    }

    // Clean the time string to HH:MM format
    $cleanTime = preg_replace('/[^0-9:]/', '', $timeStr);
    if (strlen($cleanTime) >= 4) {
        $cleanedSchedules[] = substr($cleanTime, 0, 5);
    }
}

sort($cleanedSchedules);
$cleanedSchedules = array_values(array_unique($cleanedSchedules));

$currentTime = new DateTime();
$nextFerryIndex = -1;
$targetDateTime = null;

// Find the next upcoming ferry today
foreach ($cleanedSchedules as $index => $timeStr) {
    $departureDateTime = DateTime::createFromFormat('H:i', $timeStr);
    $departureDateTime->setDate((int)$currentTime->format('Y'), (int)$currentTime->format('m'), (int)$currentTime->format('d'));
    
    if ($departureDateTime > $currentTime) {
        $nextFerryIndex = $index;
        $targetDateTime = $departureDateTime;
        break;
    }
}

// Roll over to tomorrow's first ferry if no more ferries are left today
if ($nextFerryIndex === -1 && !empty($cleanedSchedules)) {
    $nextFerryIndex = 0;
    $timeStr = $cleanedSchedules[0];
    $targetDateTime = DateTime::createFromFormat('H:i', $timeStr);
    $targetDateTime->setDate((int)$currentTime->format('Y'), (int)$currentTime->format('m'), (int)$currentTime->format('d'));
    $targetDateTime->modify('+1 day');
}

echo json_encode([
    'pier' => $pier,
    'schedule_type' => $scheduleType,
    'news' => trim($newsContent),
    'next_index' => $nextFerryIndex,
    'target_timestamp' => $targetDateTime ? $targetDateTime->getTimestamp() : null,
    'schedules' => $cleanedSchedules
]);
?>