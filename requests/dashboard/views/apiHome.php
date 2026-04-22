<?php
$response["storeDetails"] = array(
	"title" => $storeDetails["title"],
	"logo" => "{$storeDetails["logo"]}",
);

// Get all successful subscriptions ordered by date
$sqlSubs = "SELECT s.date, p.duration 
            FROM subscriptions as s 
            JOIN packages as p ON s.packageId = p.id 
            WHERE s.storeId = '{$storeId}' AND s.status = '1' 
            ORDER BY s.date ASC";

$remainingDays = 0;
if ($subs = queryDB($sqlSubs)) {
    $currentExpiry = null;
    $today = strtotime(date('Y-m-d'));
    
    foreach ($subs as $sub) {
        $purchaseDate = strtotime(date('Y-m-d', strtotime($sub["date"])));
        $durationDays = (int)$sub["duration"];
        
        // If this is the first package or current one expired before this purchase
        if ($currentExpiry === null || $purchaseDate > $currentExpiry) {
            $currentExpiry = strtotime("+{$durationDays} days", $purchaseDate);
        } else {
            // Purchase was made during an active package, append duration
            $currentExpiry = strtotime("+{$durationDays} days", $currentExpiry);
        }
    }
    
    if ($currentExpiry) {
        $diff = $currentExpiry - $today;
        $remainingDays = round($diff / (60 * 60 * 24));
    }
}

$response["storeDetails"]["remainingDays"] = ($remainingDays < 0) ? "0" : (string)$remainingDays;

$today = date("Y-m-d");
$lastMonth = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y")));
$tomorrow = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));

// Earnings Query - Optimized to one query
$sqlEarnings = "SELECT 
				SUM(CASE WHEN `date` LIKE '%{$today}%' THEN (price + JSON_UNQUOTE(JSON_EXTRACT(address, '$.shipping'))) ELSE 0 END) as dailyTotal,
				SUM(CASE WHEN `date` BETWEEN '{$lastMonth}' AND '{$tomorrow}' THEN (price + JSON_UNQUOTE(JSON_EXTRACT(address, '$.shipping'))) ELSE 0 END) as monthlyTotal,
				SUM(price + JSON_UNQUOTE(JSON_EXTRACT(address, '$.shipping'))) as allTimeTotal
			FROM (
				SELECT * FROM `orders2` 
				WHERE `storeId` = '{$storeId}' AND `status` NOT IN ('0', '5') 
				GROUP BY `orderId`
			) as f";

if ($resultEarnings = $dbconnect->query($sqlEarnings)) {
    $rowEarnings = $resultEarnings->fetch_assoc();
    $earnings = [
        ["en" => "Daily Stats", "ar" => "يومية", "val" => $rowEarnings["dailyTotal"]],
        ["en" => "Monthly Stats", "ar" => "شهرية", "val" => $rowEarnings["monthlyTotal"]],
        ["en" => "All time Stats", "ar" => "أحصائيات الكل", "val" => $rowEarnings["allTimeTotal"]]
    ];
    foreach ($earnings as $earning) {
        $response["earnings"][] = [
            "enTitle" => $earning["en"],
            "arTitle" => $earning["ar"],
            "total" => empty($earning["val"]) ? numTo3Float(0) : numTo3Float($earning["val"]),
        ];
    }
}

// Stats Query - Optimized to one query
$sqlStats = "SELECT 
			`status`,
			COUNT(CASE WHEN `date` LIKE '%{$today}%' THEN 1 END) as dailyCount,
			COUNT(CASE WHEN `date` BETWEEN '{$lastMonth}' AND '{$today}' THEN 1 END) as monthlyCount,
			COUNT(*) as allTimeCount
		FROM `orders2` 
		WHERE `storeId` = '{$storeId}' AND `status` IN ('1', '2', '3', '4')
		GROUP BY `status`";

if ($resultStats = $dbconnect->query($sqlStats)) {
    $statsMap = [];
    while ($row = $resultStats->fetch_assoc()) {
        $statsMap[$row['status']] = $row;
    }

    $periods = [
        ["en" => "Daily Stats", "ar" => "يومية", "key" => "dailyCount"],
        ["en" => "Monthly Stats", "ar" => "شهرية", "key" => "monthlyCount"],
        ["en" => "All time Stats", "ar" => "أحصائيات الكل", "key" => "allTimeCount"]
    ];

    $statusDetails = [
        "1" => ["en" => "Success", "ar" => "ناجحه"],
        "2" => ["en" => "Preparing", "ar" => "قيد التجهيز"],
        "3" => ["en" => "Delivering", "ar" => "جاري التوصيل"],
        "4" => ["en" => "Delivered", "ar" => "تم تسليمها"]
    ];

    foreach ($periods as $period) {
        foreach ($statusDetails as $statusId => $titles) {
            $response["stats"][] = [
                "enMainTitle" => $period["en"],
                "arMainTitle" => $period["ar"],
                "enTitle" => $titles["en"],
                "arTitle" => $titles["ar"],
                "total" => $statsMap[$statusId][$period["key"]] ?? 0,
            ];
        }
    }
}

echo outputData($response); die();
?>