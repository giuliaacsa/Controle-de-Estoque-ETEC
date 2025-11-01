<?php
require_once '../config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

try {
    // Dados por categoria
    $stmt = $db->query("
        SELECT 
            c.nome as categoria,
            COUNT(p.id) as total_produtos,
            SUM(p.estoque_atual) as total_pacotes
        FROM categorias c
        LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = TRUE
        GROUP BY c.id, c.nome
        HAVING total_produtos > 0
        ORDER BY c.nome
    ");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dados de status
    $stmt = $db->query("
        SELECT 
            COUNT(CASE WHEN estoque_atual > (estoque_minimo * 1.5) THEN 1 END) as normal,
            COUNT(CASE WHEN estoque_atual > estoque_minimo AND estoque_atual <= (estoque_minimo * 1.5) THEN 1 END) as baixo,
            COUNT(CASE WHEN estoque_atual <= estoque_minimo THEN 1 END) as critico
        FROM produtos 
        WHERE ativo = TRUE
    ");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'categorias' => $categorias,
        'status' => $status
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>