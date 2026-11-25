
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

$metodosPermitidos = ['GET', 'PATCH', 'PUT', 'DELETE'];

if (!in_array($metodo, $metodosPermitidos, true)) {
    header('Allow: GET, PATCH, PUT, DELETE');
    json_response(false, 'Método não permitido', null, 405);
}

$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    json_response(false, 'ID da tarefa inválido', null, 400);
}

try {

    if ($metodo === 'GET') {

        $tarefa = $model->buscarPorId($id, $userId);

        if ($tarefa === null) {
            json_response(false, 'Tarefa não encontrada', null, 404);
        }

        json_response(true, 'Tarefa encontrada', $tarefa, 200);
    }

    elseif ($metodo === 'PATCH') {

        $dados = ler_json_corpo();

        if ($dados === null) {
            json_response(false, 'JSON inválido', null, 400);
        }

        $status = $dados['status'] ?? null;

        if (
            !is_string($status) ||
            !in_array($status, Task::STATUS, true)
        ) {
            json_response(false, 'Status inválido', null, 400);
        }

        $tarefa = $model->atualizarStatus(
            $id,
            $userId,
            $status
        );

        if ($tarefa === null) {
            json_response(false, 'Tarefa não encontrada', null, 404);
        }

        json_response(true, 'Status atualizado', $tarefa, 200);
    }

    elseif ($metodo === 'PUT') {

        $dados = ler_json_corpo();

        if ($dados === null) {
            json_response(false, 'JSON inválido', null, 400);
        }

        $titulo = $dados['titulo'] ?? null;
        $status = $dados['status'] ?? null;
        $prioridade = $dados['prioridade'] ?? null;

        if (
            !is_string($titulo) ||
            trim($titulo) === '' ||
            mb_strlen(trim($titulo)) > 200
        ) {
            json_response(false, 'Título inválido ou obrigatório', null, 400);
        }

        if (
            !is_string($status) ||
            !in_array($status, Task::STATUS, true)
        ) {
            json_response(false, 'Status inválido', null, 400);
        }

        if (
            !is_string($prioridade) ||
            !in_array($prioridade, Task::PRIORIDADES, true)
        ) {
            json_response(false, 'Prioridade inválida', null, 400);
        }

        $descricao = $dados['descricao'] ?? null;

        if ($descricao !== null && !is_string($descricao)) {
            json_response(false, 'Descrição inválida', null, 400);
        }

        if (is_string($descricao)) {
            $descricao = trim($descricao);
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

        $tarefa = $model->atualizar(
            $id,
            $userId,
            trim($titulo),
            $descricao,
            $status,
            $prioridade,
            $dataInicio,
            $dataPrazo
        );

        if ($tarefa === null) {
            json_response(false, 'Tarefa não encontrada', null, 404);
        }

        json_response(true, 'Tarefa atualizada', $tarefa, 200);
    }

    elseif ($metodo === 'DELETE') {

        $excluida = $model->deletar($id, $userId);

        if (!$excluida) {
            json_response(false, 'Tarefa não encontrada', null, 404);
        }

        json_response(true, 'Tarefa excluída', null, 200);
    }

} catch (PDOException $e) {

    error_log($e->getMessage());

    json_response(false, 'Erro interno do servidor', null, 500);
}
