<?php

require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo !== 'POST') {
    json_response(false, 'Método não permitido', null, 405);
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!is_array($dados)) {
    json_response(false, 'Dados inválidos', null, 400);
}

$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

if ($email === '' || $senha === '') {
    json_response(false, 'Informe o e-mail e a senha', null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'E-mail inválido', null, 400);
}

try {

    $sql = "
        SELECT id, nome, email, senha_hash
        FROM users
        WHERE email = :email
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        json_response(false, 'E-mail ou senha incorretos', null, 401);
    }

    iniciar_sessao();

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];

    unset($usuario['senha_hash']);

    json_response(
        true,
        'Login realizado com sucesso',
        $usuario,
        200
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    json_response(false, 'Erro ao realizar login', null, 500);
}