<?php
// includes/functions.php

/**
 * Recalculate cumulative KM for a specific vehicle.
 * formulas: Cumulative = Initial Handover KM + Sum(All Monthly KM up to that month)
 */
function updateCumulativeKM($conn, $vehicle_id) {
    // 1. Get Initial Handover KM (most recent assignment)
    $stmt = $conn->prepare("SELECT so_km_ban_giao FROM vehicle_assignments 
                           WHERE vehicle_id = ? AND den_ngay IS NULL 
                           ORDER BY tu_ngay DESC LIMIT 1");
    $stmt->execute([$vehicle_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    $initial_km = $assignment ? (float)$assignment['so_km_ban_giao'] : 0;

    // 2. Get all monthly records ordered by time
    $stmt = $conn->prepare("SELECT id, km_trong_thang 
                           FROM operation_stats 
                           WHERE vehicle_id = ? 
                           ORDER BY nam ASC, thang ASC");
    $stmt->execute([$vehicle_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Iterate and update
    $running_total = $initial_km;
    
    // Prepare update statement once
    $updateStmt = $conn->prepare("UPDATE operation_stats SET km_tich_luy = ? WHERE id = ?");

    foreach ($records as $row) {
        $km_month = (float)$row['km_trong_thang'];
        $running_total += $km_month;

        // Perform update
        $updateStmt->execute([$running_total, $row['id']]);
    }

    return $running_total; // Return the final total if needed
}
?>
