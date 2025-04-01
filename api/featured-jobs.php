<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $conn = getConnection();
    
    $sql = "SELECT 
                j.id,
                j.title,
                j.description,
                j.location,
                j.type,
                j.salary_range,
                cp.company_name,
                cp.logo_path
            FROM jobs j
            INNER JOIN company_profiles cp ON j.company_id = cp.id
            WHERE j.status = 'open'
            ORDER BY j.created_at DESC
            LIMIT 6";
    
    $result = $conn->query($sql);
    
    $jobs = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Clean and format the data
            $row['description'] = strip_tags($row['description']);
            $row['logo_path'] = $row['logo_path'] ?? 'images/default-company-logo.png';
            
            $jobs[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $jobs]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching featured jobs']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
