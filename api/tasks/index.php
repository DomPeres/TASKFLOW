
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/helpers/validate.php';
require_once __DIR__ . '/../../src/models/Task.php';

verificar_autenticacao();

$userId = (int) $_SESSION['usuario_id'];
$model = new Task($pdo);

$metodo = $_SERVER['REQUEST_METHOD'];

try {

    if ($metodo === 'POST') {

        $dados = ler_json_corpo();

        if ($dados === null) {
            json_response(false, 'JSON inválido', null, 400);
        }

        $projectId = filter_var(
            $dados['project_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($projectId === false || $projectId === null) {
            json_response(false, 'Projeto inválido', null, 400);
        }

        $titulo = $dados['titulo'] ?? null;

        if (!is_string($titulo) || trim($titulo) === '') {
            json_response(false, 'Título obrigatório', null, 400);
        }

        $titulo = trim($titulo);

        if (mb_strlen($titulo) > 200) {
            json_response(false, 'Título deve ter no máximo 200 caracteres', null, 400);
        }

        $descricao = $dados['descricao'] ?? null;

        if ($descricao !== null && !is_string($descricao)) {
            json_response(false, 'Descrição inválida', null, 400);
        }

        if (is_string($descricao)) {
            $descricao = trim($descricao);
        }

        $prioridade = $dados['prioridade'] ?? 'media';

        if (
            !is_string($prioridade) ||
            !in_array($prioridade, Task::PRIORIDADES, true)
        ) {
            json_response(false, 'Prioridade inválida', null, 400);
        }

        $dataInicio = $dados['data_inicio'] ?? null;
        $dataPrazo = $dados['data_prazo'] ?? null;

        if (
            $dataInicio !== null &&
            (!is_string($dataInicio) || !validar_data_iso($dataInicio))
        ) {
            json_response(false, 'Data de início inválida', null, 400);
        }

        if (
            $dataPrazo !== null &&
            (!is_string($dataPrazo) || !validar_data_iso($dataPrazo))
        ) {
            json_response(false, 'Data de prazo inválida', null, 400);
        }

        if (
            $dataInicio !== null &&
            $dataPrazo !== null &&
            $dataPrazo < $dataInicio
        ) {
            json_response(false, 'O prazo não pode ser anterior à data de início', null, 400);
        }

        $tarefa = $model->criar(
            $projectId,
            $titulo,
            $descricao,
            $prioridade,
            $dataInicio,
            $dataPrazo,
            $userId
        );

        if ($tarefa === null) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(true, 'Tarefa criada', $tarefa, 201);
    }

    elseif ($metodo === 'GET') {

        $filtros = [];

        if (isset($_GET['project_id'])) {
            $projectId = filter_var(
                $_GET['project_id'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($projectId === false || $projectId === null) {
                json_response(false, 'ID do projeto inválido', null, 400);
            }

            $filtros['project_id'] = $projectId;
        }

        if (isset($_GET['status'])) {
            if (
                !is_string($_GET['status']) ||
                !in_array($_GET['status'], Task::STATUS, true)
            ) {
                json_response(false, 'Status inválido', null, 400);
            }

            $filtros['status'] = $_GET['status'];
        }

        if (isset($_GET['prioridade'])) {
            if (
                !is_string($_GET['prioridade']) ||
                !in_array($_GET['prioridade'], Task::PRIORIDADES, true)
            ) {
                json_response(false, 'Prioridade inválida', null, 400);
            }

            $filtros['prioridade'] = $_GET['prioridade'];
        }

        if (isset($_GET['limite'])) {
            $limite = filter_var(
                $_GET['limite'],
                FILTER_VALIDATE_INT,
                ['options' => [
                    'min_range' => 1,
                    'max_range' => 100
                ]]
            );

            if ($limite === false || $limite === null) {
                json_response(false, 'Limite inválido', null, 400);
            }

            $filtros['limite'] = $limite;
        }

        $tarefas = $model->listar($userId, $filtros);

        json_response(true, 'Tarefas listadas', $tarefas, 200);
    }

    else {
        header('Allow: GET, POST');
        json_response(false, 'Método não permitido', null, 405);
    }

} catch (PDOException $e) {

    error_log($e->getMessage());

    json_response(false, 'Erro interno do servidor', null, 500);
}
