<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/config.php';

$results = [
    'members' => [],
    'plans' => [],
    'trainers' => []
];

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $query = $conn->real_escape_string(trim($_GET['query']));

    // Search Members
    $sql_members = "SELECT id, name, email FROM members WHERE name LIKE '%$query%' OR email LIKE '%$query%' LIMIT 5";
    $result_members = $conn->query($sql_members);
    if ($result_members && $result_members->num_rows > 0) {
        while ($row = $result_members->fetch_assoc()) {
            $row['url'] = SITE_URL . 'admin/members.php?action=view&id=' . $row['id'];
            $results['members'][] = $row;
        }
    }

    // Search Plans
    $sql_plans = "SELECT id, name, duration, price FROM plans WHERE name LIKE '%$query%' LIMIT 5";
    $result_plans = $conn->query($sql_plans);
    if ($result_plans && $result_plans->num_rows > 0) {
        while ($row = $result_plans->fetch_assoc()) {
            $row['url'] = SITE_URL . 'admin/plans.php';
            $results['plans'][] = $row;
        }
    }

    // Search Trainers
    $sql_trainers = "SELECT id, name, specialization FROM trainers WHERE name LIKE '%$query%' LIMIT 5";
    $result_trainers = $conn->query($sql_trainers);
    if ($result_trainers && $result_trainers->num_rows > 0) {
        while ($row = $result_trainers->fetch_assoc()) {
            $row['url'] = SITE_URL . 'admin/trainers.php?action=view&id=' . $row['id'];
            $results['trainers'][] = $row;
        }
    }
}

echo json_encode($results);
?>