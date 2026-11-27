
<?php

require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/helpers/validate.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/models/project.php';

verificar_autenticacao();

$userId = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

$project = new Project($pdo);

try {

    if ($metodo === 'GET') {

        $projetos = $project->listar($userId);

        json_response(
            true,
            'Projetos encontrados com sucesso',
            $projetos,
            200
        );
    }

    if ($metodo === 'POST') {

        try {
            $dados = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            json_response(false, 'JSON inválido', null, 400);
        }

        if (!is_array($dados)) {
            json_response(false, 'Dados inválidos', null, 400);
        }

        $nome = $dados['nome'] ?? null;
        $descricao = $dados['descricao'] ?? null;

        if (!is_string($nome)) {
            json_response(false, 'Nome inválido', null, 400);
        }

        $nome = trim($nome);

        if (
            !validar_campo($nome) ||
            mb_strlen($nome, 'UTF-8') > 150
        ) {
            json_response(
                false,
                'O nome deve ter entre 1 e 150 caracteres',
                null,
                400
            );
        }

        if ($descricao !== null && !is_string($descricao)) {
            json_response(false, 'Descrição inválida', null, 400);
        }

        if ($descricao !== null) {
            $descricao = trim($descricao);

            if ($descricao === '') {
                $descricao = null;
            }
        }

        $novoProjeto = $project->criar(
            $nome,
            $descricao,
            $userId
        );

        json_response(
            true,
            'Projeto criado com sucesso',
            $novoProjeto,
            201
        );
    }

    header('Allow: GET, POST');

    json_response(
        false,
        'Método não permitido',
        null,
        405
    );

} catch (PDOException | RuntimeException $e) {

    error_log($e->getMessage());

    json_response(
        false,
        'Erro interno ao processar projetos',
        null,
        500
    );
}
