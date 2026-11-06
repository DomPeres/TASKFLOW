<?php

require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo !== 'GET') {
    json_response(false, 'Método não permitido', null, 405);
}

verificar_autenticacao();

try {

    $sql = "
        SELECT id, nome, email, created_at
        FROM users
        ORDER BY id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $usuarios = $stmt->fetchAll();

    json_response(
        true,
        'Usuários encontrados com sucesso',
        $usuarios,
        200
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    json_response(false, 'Erro ao buscar usuários', null, 500);
}