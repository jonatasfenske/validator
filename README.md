# JonatasFenske\Validator

<p align="center">
 <strong>Validação declarativa PHP 8.5 com 30+ rules e mensagens em português</strong>
</p>

<p align="center">
 <img src="https://img.shields.io/badge/PHP-8.5%2B-8892BF?logo=php" alt="PHP 8.5+">
 <img src="https://img.shields.io/badge/license-MIT-blue" alt="MIT License">
</p>

## Instalação

```bash
composer require jonatasfenske/validator
```

## Recursos

- **30+ rules declarativas** — required, email, integer, numeric, boolean, array, json, uuid, url, ip, date, alpha, alpha_num, alpha_dash, regex, min, max, between, size, min_length, max_length, confirmed, same, different, in, not_in, before, after
- **Mensagens em português** do Brasil com placeholders (`:attribute`, `:min`, `:max`)
- **Labels customizados** por campo para mensagens legíveis
- **Mensagens customizadas** por `campo.regra` ou regra genérica
- **Rules customizáveis** via `Validator::extend()` com closures
- **Factory estático** `Validator::make()` para validação rápida
- **validateOrFail()** com `ValidationException` (código HTTP 422)
- **validated()** retorna apenas campos definidos nas regras
- **rejected()** retorna campos fora das regras
- **Stubs para unique/exists** integráveis com qualquer ORM/QueryBuilder

## Uso Rápido

### Validação em Controllers

```php
use JonatasFenske\Validator\Validator;
use JonatasFenske\Validator\ValidationException;

$v = Validator::make($_POST, [
 'name' => 'required|string|min:2|max:100',
 'email' => 'required|email',
 'age' => 'required|integer|between:18,120',
 'password' => 'required|confirmed|min:8',
 'role' => 'required|in:admin,editor,author',
]);

if ($v->fails()) {
 http_response_code(422);
 echo json_encode(['errors' => $v->errors()]);
 exit;
}

$data = $v->validated(); // apenas campos das regras
```

### Com Exceção Automática

```php
try {
 $v = Validator::make($_POST, ['email' => 'required|email']);
 $v->validateOrFail();
 $data = $v->validated();
} catch (ValidationException $e) {
 http_response_code(422);
 echo json_encode(['errors' => $e->getFirstErrors()]);
}
```

### Mensagens e Labels Customizados

```php
$v->setMessages([
 'email.email' => 'Informe um email corporativo válido.',
 'password.min' => 'A senha deve ter pelo menos :min caracteres.',
]);

$v->setLabels([
 'user_email' => 'E-mail Corporativo',
 'full_name' => 'Nome Completo',
]);
```

### Regra Customizada (CPF)

```php
Validator::extend('cpf', function (array $data, string $field): bool {
 $cpf = preg_replace('/[^0-9]/', '', (string)($data[$field] ?? ''));
 return strlen($cpf) === 11; // simplificado; use algoritmo completo em produção
}, 'O campo :attribute deve conter um CPF válido.');

$v = Validator::make(['cpf' => '12345678901'], ['cpf' => 'required|cpf']);
```

### Integração com DataLayer (unique/exists)

```php
use JonatasFenske\DataLayer\QueryBuilder;

Validator::extend('unique', function (array $data, string $field, string $param): bool {
 [$table, $column] = explode(',', $param);
 return !(new QueryBuilder($table))->where($column, '=', $data[$field])->exists();
}, 'O :attribute já está em uso.');
```

## Testes

```bash
composer test
```

## Licença

MIT