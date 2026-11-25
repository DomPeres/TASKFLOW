
<?php

function validar_campo(?string $valor): bool
{
    return $valor !== null && trim($valor) !== '';
}

function ler_json_corpo(): ?array
{
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados) || array_is_list($dados)) {
        return null;
    }

    return $dados;
}

function validar_data_iso(string $data): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

    return $d !== false && $d->format('Y-m-d') === $data;
}
