<?php
include 'config.php';

$employee_id = $_POST['employee_id'];
$date = date('Y-m-d');
$time = date('Y-m-d H:i:s');

// Check if employee already timed in today
$sql = "SELECT * FROM time_logs WHERE employee_id=? AND date=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Update time_out
    $time_in = strtotime($row['time_in']);
    $time_out = strtotime($time);
    $hours_worked = round(($time_out - $time_in) / 3600, 2);

    $update = $conn->prepare("UPDATE time_logs SET time_out=?, hours_worked=? WHERE id=?");
    $update->bind_param("sdi", $time, $hours_worked, $row['id']);
    $update->execute();

    echo "Time Out recorded for Employee $employee_id";
} else {
    // Insert time_in
    $insert = $conn->prepare("INSERT INTO time_logs (employee_id, time_in, date) VALUES (?, ?, ?)");
    $insert->bind_param("iss", $employee_id, $time, $date);
    $insert->execute();

    echo "Time In recorded for Employee $employee_id";
}
?>
