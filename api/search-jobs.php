<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $keyword = isset($_GET['keyword']) ? '%' . $_GET['keyword'] . '%' : '%%';
    $location = isset($_GET['location']) ? '%' . $_GET['location'] . '%' : '%%';
    
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
            AND (j.title LIKE ? OR j.description LIKE ? OR cp.company_name LIKE ?)
            AND j.location LIKE ?
            ORDER BY j.created_at DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $location);
    $stmt->execute();
    $result = $stmt->get_result();
    
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
    echo json_encode(['success' => false, 'message' => 'Error searching jobs']);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
