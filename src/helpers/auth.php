<?php

function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function verificar_autenticacao(): void
{
    iniciar_sessao();

    if (empty($_SESSION['usuario_id'])) {
        json_response(
            false,
            'Usuário não autenticado',
            null,
            401
        );
    }
}