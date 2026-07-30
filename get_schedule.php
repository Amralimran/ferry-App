<?php
header('Content-Type: application/json');

// Safely normalize the pier parameter (trim whitespace and match case correctly)
$rawPier = $_GET['pier'] ?? 'Central';
$pier = (strcasecmp(trim($rawPier), 'Mui Wo') === 0) ? 'Mui Wo' : 'Central';
$filePrefix = ($pier === 'Mui Wo') ? 'muiwo' : 'central';

$isHolidayToggle = filter_var($_GET['holiday'] ?? false, FILTER_VALIDATE_BOOLEAN);

$dayOfWeek = (int)date('w'); // 0 = Sunday, 6 = Saturday, 1-5 = Weekdays
$isSunday = ($dayOfWeek === 0);
$isSaturday = ($dayOfWeek === 6);
$scheduleType = ($isSunday || $isHolidayToggle) ? 'sunday' : 'weekday';

// Check if global special override mode is active via special.tgl file
$isSpecialMode = file_exists(__DIR__ . '/schedules/special.tgl');
$newsContent = "";
$rawSchedules = [];

if ($isSpecialMode) {
    // 1. Load pier-specific special JSON file (e.g., central_special.json or muiwo_special.json)
    $specialPath = __DIR__ . '/schedules/' . $filePrefix . '_special.json';
    if (file_exists($specialPath)) {
        $specialData = json_decode(file_get_contents($specialPath), true);
        if (is_array($specialData)) {
            $rawSchedules = $specialData;
        }
    }

    // 2. Load independent news message from news.json
    $newsPath = __DIR__ . '/schedules/news.json';
    if (file_exists($newsPath)) {
        $newsData = json_decode(file_get_contents($newsPath), true);
        if (is_array($newsData) && isset($newsData['message'])) {
            $newsContent = $newsData['message'];
        }
    }
} else {
    // Fallback to standard unified JSON files
    $fileName = ($pier === 'Central') ? 'central.json' : 'muiwo.json';
    $filePath = __DIR__ . '/schedules/' . $fileName;
    
    if (file_exists($filePath)) {
        $fileData = json_decode(file_get_contents($filePath), true);
        if (is_array($fileData)) {
            // Optional support for news header inside standard files if needed
            if (isset($fileData[0]) && ($fileData[0] === true || $fileData[0] === 'true' || $fileData[0] === 1)) {
                $newsContent = $fileData[1] ?? "";
                $rawSchedules = array_slice($fileData, 2);
            } else {
                $rawSchedules = $fileData;
            }
        }
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

$currentDayName = strtolower(date('l')); 

$cleanedSchedules = [];
foreach ($rawSchedules as $timeStr) {
    if (!is_string($timeStr)) continue;
    $timeStrLower = strtolower($timeStr);

    $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    $matchedDayTag = null;
    foreach ($daysOfWeek as $day) {
        if (strpos($timeStrLower, $day) !== false) {
            $matchedDayTag = $day;
            break;
        }
    }

    // Strict Filtering Logic:
    if ($isSpecialMode || $isHolidayToggle) {
        // If Special Mode or Public Holiday is active, ONLY include rows explicitly tagged with 'sunday' 
        // (or if your list relies on separate files, skip any rows tagged for other weekdays).
        if ($matchedDayTag !== 'sunday') {
            continue;
        }
    } else {
        // Regular weekday mode: if a row has a specific day tag, it must match today. 
        // If it has no day tag, it's a general daily ferry time and gets included.
        if ($matchedDayTag !== null && $matchedDayTag !== $currentDayName) {
            continue;
        }
    }

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

foreach ($cleanedSchedules as $index => $timeStr) {
    $departureDateTime = DateTime::createFromFormat('H:i', $timeStr);
    if (!$departureDateTime) continue;
    $departureDateTime->setDate((int)$currentTime->format('Y'), (int)$currentTime->format('m'), (int)$currentTime->format('d'));
    
    if ($departureDateTime > $currentTime) {
        $nextFerryIndex = $index;
        $targetDateTime = $departureDateTime;
        break;
    }
}

if ($nextFerryIndex === -1 && !empty($cleanedSchedules)) {
    $nextFerryIndex = 0;
    $timeStr = $cleanedSchedules[0];
    $targetDateTime = DateTime::createFromFormat('H:i', $timeStr);
    $targetDateTime->setDate((int)$currentTime->format('Y'), (int)$currentTime->format('m'), (int)$currentTime->format('d'));
    $targetDateTime->modify('+1 day');
}

echo json_encode([
    'pier' => $pier,
    'schedule_type' => $isSpecialMode ? 'special' : $scheduleType,
    'news' => trim($newsContent),
    'next_index' => $nextFerryIndex,
    'target_timestamp' => $targetDateTime ? $targetDateTime->getTimestamp() : null,
    'schedules' => $cleanedSchedules
]);
?>
