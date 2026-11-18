
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

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            json_response(false, 'ID inválido', null, 400);
        }

        $projeto = $project->buscarPorId($id, $userId);

        if (!$projeto) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(true, 'Projeto encontrado com sucesso', $projeto, 200);
    }

    if ($metodo === 'PUT') {

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            json_response(false, 'ID inválido', null, 400);
        }

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
            json_response(false, 'Nome inválido', null, 422);
        }

        $nome = trim($nome);

        if (
            !validar_campo($nome) ||
            mb_strlen($nome, 'UTF-8') > 150
        ) {
            json_response(false, 'O nome deve ter entre 1 e 150 caracteres', null, 422);
        }

        if ($descricao !== null && !is_string($descricao)) {
            json_response(false, 'Descrição inválida', null, 422);
        }

        if ($descricao !== null) {
            $descricao = trim($descricao);

            if ($descricao === '') {
                $descricao = null;
            }
        }

        $projeto = $project->atualizar(
            $id,
            $userId,
            $nome,
            $descricao
        );

        if (!$projeto) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(true, 'Projeto atualizado com sucesso', $projeto, 200);
    }

    if ($metodo === 'DELETE') {

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            json_response(false, 'ID inválido', null, 400);
        }

        $deletado = $project->deletar($id, $userId);

        if (!$deletado) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(true, 'Projeto excluído com sucesso', null, 200);
    }

    header('Allow: GET, PUT, DELETE');

    json_response(false, 'Método não permitido', null, 405);

} catch (PDOException $e) {

    error_log($e->getMessage());

    if ($metodo === 'DELETE' && $e->getCode() === '23503') {
        json_response(
            false,
            'Não é possível excluir o projeto porque existem tarefas vinculadas',
            null,
            409
        );
    }

    json_response(false, 'Erro interno ao processar projeto', null, 500);

} catch (RuntimeException $e) {

    error_log($e->getMessage());

    json_response(false, 'Erro interno ao processar projeto', null, 500);
}
