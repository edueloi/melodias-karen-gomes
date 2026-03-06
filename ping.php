<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verifica se a sessão ainda está ativa
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['id_usuario'])) {
    // Sessão válida
    echo json_encode([
        'status' => 'ok',
        'user'   => $_SESSION['nome_usuario'] ?? '',
        'role'   => $_SESSION['role'] ?? 'user'
    ]);
} else {
    // Sessão expirada
    http_response_code(401);
    echo json_encode([
        'status'  => 'expired',
        'message' => 'Sessão expirada'
    ]);
}
