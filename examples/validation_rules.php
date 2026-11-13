<?php

declare(strict_types=1);

/**
 * Exemplo completo de validação declarativa com Validator
 * Execute: php examples/validation_rules.php
 */

require __DIR__ . '/../vendor/autoload.php';

use JonatasFenske\Validator\Validator;
use JonatasFenske\Validator\ValidationException;

// =============================================
// 1. VALIDAÇÃO BÁSICA
// =============================================
echo "=== VALIDAÇÃO BÁSICA ===\n\n";

$v = Validator::make([
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'age' => 30,
    'website' => 'https://joao.dev',
    'role' => 'editor',
], [
    'name' => 'required|string|min:2|max:100',
    'email' => 'required|email',
    'age' => 'required|integer|between:18,120',
    'website' => 'url',
    'role' => 'required|in:admin,editor,author',
]);

if ($v->passes()) {
    echo "✅ Validação passou!\n";
    echo "Dados validados: " . json_encode($v->validated(), JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "❌ Erros: " . json_encode($v->errors(), JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n";

// =============================================
// 2. VALIDAÇÃO COM FALHAS
// =============================================
echo "=== VALIDAÇÃO COM FALHAS ===\n\n";

$v2 = Validator::make([
    'name' => '',
    'email' => 'invalid-email',
    'age' => 15,
], [
    'name' => 'required|min:2',
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
]);

$v2->setLabels([
    'name' => 'Nome Completo',
    'email' => 'E-mail',
    'age' => 'Idade',
]);

if ($v2->fails()) {
    echo "❌ Validação falhou (esperado):\n";
    foreach ($v2->firstErrors() as $field => $error) {
        echo "  - {$field}: {$error}\n";
    }
}

echo "\n";

// =============================================
// 3. MENSAGENS CUSTOMIZADAS
// =============================================
echo "=== MENSAGENS CUSTOMIZADAS ===\n\n";

$v3 = Validator::make(
    ['email' => 'bad'],
    ['email' => 'required|email']
);
$v3->setMessages([
    'email.email' => 'Por favor, informe um email corporativo válido (@empresa.com.br).',
]);
$v3->validate();

echo "Mensagem customizada: " . $v3->firstError() . "\n\n";

// =============================================
// 4. CONFIRMED E SAME
// =============================================
echo "=== CONFIRMED E SAME ===\n\n";

$v4 = Validator::make([
    'password' => 'senha123',
    'password_confirmation' => 'senha123',
    'email' => 'joao@test.com',
    'email_backup' => 'joao@test.com',
], [
    'password' => 'required|confirmed|min:8',
    'email' => 'required|same:email_backup',
]);

echo "Password confirmed: " . ($v4->validate() ? '✅' : '❌') . "\n\n";

// =============================================
// 5. REGRA CUSTOMIZADA (CPF)
// =============================================
echo "=== REGRA CUSTOMIZADA (CPF) ===\n\n";

Validator::extend('cpf', function (array $data, string $field): bool {
    $cpf = preg_replace('/[^0-9]/', '', (string)($data[$field] ?? ''));
    return strlen($cpf) === 11;
}, 'O campo :attribute deve conter um CPF válido (11 dígitos).');

$v5 = Validator::make(
    ['cpf' => '123.456.789-01'],
    ['cpf' => 'required|cpf']
);
$v5->setLabels(['cpf' => 'CPF']);

echo "CPF '123.456.789-01': " . ($v5->validate() ? '✅ Válido' : '❌ Inválido') . "\n";

$v5b = Validator::make(['cpf' => '123'], ['cpf' => 'cpf']);
$v5b->setLabels(['cpf' => 'CPF']);
$v5b->validate();
echo "CPF '123': " . ($v5b->passes() ? '✅ Válido' : '❌ ' . $v5b->firstError()) . "\n\n";

// =============================================
// 6. VALIDATE OR FAIL (EXCEÇÃO)
// =============================================
echo "=== VALIDATE OR FAIL ===\n\n";

try {
    $v6 = Validator::make(['email' => 'bad'], ['email' => 'required|email']);
    $v6->validateOrFail();
    echo "✅ Passou (inesperado)\n";
} catch (ValidationException $e) {
    echo "❌ ValidationException capturada (código {$e->getCode()}):\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   Erros: " . json_encode($e->getFirstErrors(), JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n";

// =============================================
// 7. VALIDATED vs REJECTED
// =============================================
echo "=== VALIDATED vs REJECTED ===\n\n";

$v7 = Validator::make([
    'name' => 'João',
    'email' => 'joao@test.com',
    'csrf_token' => 'abc123',
    'utm_source' => 'google',
], [
    'name' => 'required',
    'email' => 'required|email',
]);
$v7->validate();

echo "Validated (nas regras): " . json_encode($v7->validated()) . "\n";
echo "Rejected (fora das regras): " . json_encode($v7->rejected()) . "\n\n";

// =============================================
// 8. CENÁRIO REAL-WORLD COMPLETO
// =============================================
echo "=== CENÁRIO REAL-WORLD ===\n\n";

$registrationData = [
    'name' => 'Maria Silva',
    'email' => 'maria@example.com',
    'age' => 30,
    'website' => 'https://maria.dev',
    'role' => 'editor',
    'bio' => 'Desenvolvedora PHP apaixonada por código limpo',
    'password' => 'senhaSegura123',
    'password_confirmation' => 'senhaSegura123',
];

$vReal = Validator::make($registrationData, [
    'name' => 'required|string|min:2|max:100',
    'email' => 'required|email',
    'age' => 'required|integer|between:18,120',
    'website' => 'url',
    'role' => 'required|in:admin,editor,author',
    'bio' => 'string|max:500',
    'password' => 'required|confirmed|min:8',
]);

if ($vReal->passes()) {
    echo "✅ Registro válido! Dados prontos para persistência:\n";
    $clean = $vReal->validated();
    unset($clean['password_confirmation']);
    echo json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Erros no registro:\n";
    foreach ($vReal->firstErrors() as $field => $error) {
        echo "  - {$field}: {$error}\n";
    }
}

echo "\n✅ Exemplo concluído!\n";