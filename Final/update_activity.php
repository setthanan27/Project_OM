<?php
session_start();
include 'config.php';
$id = $_GET['id'];
$has = $_GET['has'];

$stmt = $conn->prepare("UPDATE event_bookings SET has_activity = ? WHERE id = ? AND owner_id = ?");
$stmt->execute([$has, $id, $_SESSION['owner_id']]);

header("Location: index.php?filter=confirmed");