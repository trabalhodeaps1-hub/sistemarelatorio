<?php

return [
    'master' => [
        'name' => env('MASTER_NAME', 'Administrador'),
        'email' => env('MASTER_EMAIL', 'admin@autorelatorio.local'),
        'matricula' => env('MASTER_MATRICULA', 'M0001'),
        'password' => env('MASTER_PASSWORD', 'password'),
    ],

    'fotos' => [
        'max_por_envio' => 5,
        'max_kb' => 10240,
        'largura_max' => 1600,
        'qualidade' => 80,
    ],

    'relatorios' => [
        'max_meses' => 12,
    ],

    // Identidade exibida na tela de login
    'empresa' => env('SISTEMA_EMPRESA', 'ArcelorMittal Brasil'),
    'suporte_email' => env('SISTEMA_SUPORTE_EMAIL', 'ti.suporte@arcelormittal.com.br'),
    'versao' => env('SISTEMA_VERSAO', '1.0.0'),
    'plantas' => (int) env('SISTEMA_PLANTAS', 4),
];
