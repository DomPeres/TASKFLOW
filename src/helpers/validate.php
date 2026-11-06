<?php

function validar_campo(?string $valor): bool
{
    return $valor !== null && trim($valor) !== '';
}