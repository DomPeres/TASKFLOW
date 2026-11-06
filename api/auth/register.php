
<?php

require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/validate.php';
require_once __DIR__ . '/../../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo !== 'POST') {
    json_response(false, 'Método não permitido', null, 405);
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!is_array($dados)) {
    json_response(false, 'Dados inválidos', null, 400);
}

$nome = trim($dados['nome'] ?? '');
$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

if (
    !validar_campo($nome) ||
    !validar_campo($email) ||
    !validar_campo($senha)
) {
    json_response(false, 'Preencha todos os campos', null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'E-mail inválido', null, 400);
}

try {

    $sql = "SELECT id FROM users WHERE email = :email";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        json_response(false, 'E-mail já cadastrado', null, 409);
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "
        INSERT INTO users (nome, email, senha_hash)
        VALUES (:nome, :email, :senha_hash)
        RETURNING id, nome, email, created_at
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'senha_hash' => $senhaHash
    ]);

    $usuario = $stmt->fetch();

    json_response(
        true,
        'Usuário cadastrado com sucesso',
        $usuario,
        201
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    json_response(false, 'Erro ao cadastrar usuário', null, 500);
}
