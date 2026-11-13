<?php

declare(strict_types=1);

namespace JonatasFenske\Validator;

use InvalidArgumentException;
use JsonException;

/**
 * Validador standalone com rules declarativas
 * PHP 8.5: tipagem estrita, match expressions, union types
 *
 * Sintaxe inspirada no Laravel:
 * $rules = [
 *     'name' => 'required|min:2|max:100',
 *     'email' => 'required|email|unique:users,email',
 *     'age' => 'required|integer|min:18',
 *     'password' => 'required|confirmed|min:8',
 * ];
 */
final class Validator
{
 /** @var array<string, string|array<string>> Regras de validação */
 private array $rules = [];

 /** @var array<string, mixed> Dados a validar */
 private array $data = [];

 /** @var array<string, array<string>> Erros encontrados */
 private array $errors = [];

 /** @var array<string, string> Mensagens customizadas por campo e regra */
 private array $customMessages = [];

 /** @var array<string, string> Labels customizados por campo */
 private array $customLabels = [];

 /** @var array<string, callable(array<string, mixed>, string, string): bool|string> Rules customizadas */
 private static array $customRules = [];

 /** @var array<string, string> Mensagens padrão em português */
 private static array $defaultMessages = [
 'required' => 'O campo :attribute é obrigatório.',
 'email' => 'O campo :attribute deve conter um email válido.',
 'integer' => 'O campo :attribute deve ser um número inteiro.',
 'numeric' => 'O campo :attribute deve ser numérico.',
 'string' => 'O campo :attribute deve ser um texto.',
 'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
 'array' => 'O campo :attribute deve ser um array.',
 'min' => 'O campo :attribute deve ter no mínimo :min.',
 'max' => 'O campo :attribute deve ter no máximo :max.',
 'between' => 'O campo :attribute deve estar entre :min e :max.',
 'confirmed' => 'A confirmação do campo :attribute não corresponde.',
 'same' => 'O campo :attribute deve ser igual a :other.',
 'different' => 'O campo :attribute deve ser diferente de :other.',
 'in' => 'O campo :attribute deve ser um dos valores: :values.',
 'not_in' => 'O campo :attribute não pode ser um dos valores: :values.',
 'regex' => 'O formato do campo :attribute é inválido.',
 'url' => 'O campo :attribute deve conter uma URL válida.',
 'date' => 'O campo :attribute deve conter uma data válida.',
 'before' => 'O campo :attribute deve ser anterior a :date.',
 'after' => 'O campo :attribute deve ser posterior a :date.',
 'alpha' => 'O campo :attribute deve conter apenas letras.',
 'alpha_num' => 'O campo :attribute deve conter apenas letras e números.',
 'alpha_dash' => 'O campo :attribute deve conter apenas letras, números, traços e underscores.',
 'size' => 'O campo :attribute deve ter tamanho :size.',
 'ip' => 'O campo :attribute deve conter um IP válido.',
 'json' => 'O campo :attribute deve conter JSON válido.',
 'uuid' => 'O campo :attribute deve conter um UUID válido.',
 'unique' => 'O campo :attribute já está em uso.',
 'exists' => 'O campo :attribute selecionado é inválido.',
 'min_length' => 'O campo :attribute deve ter no mínimo :min caracteres.',
 'max_length' => 'O campo :attribute deve ter no máximo :max caracteres.',
 ];

 /**
 * Define regras de validação
 *
 * @param array<string, string|array<string>> $rules
 * @return static
 */
 public function setRules(array $rules): static
 {
 $this->rules = $rules;
 return $this;
 }

 /**
 * Define dados a validar
 *
 * @param array<string, mixed> $data
 * @return static
 */
 public function setData(array $data): static
 {
 $this->data = $data;
 return $this;
 }

 /**
 * Define mensagens customizadas
 *
 * @param array<string, string> $messages Formato: 'campo.regra' => 'mensagem'
 * @return static
 */
 public function setMessages(array $messages): static
 {
 $this->customMessages = $messages;
 return $this;
 }

 /**
 * Define labels customizados para os campos
 *
 * @param array<string, string> $labels Formato: 'campo' => 'Nome Legível'
 * @return static
 */
 public function setLabels(array $labels): static
 {
 $this->customLabels = $labels;
 return $this;
 }

 /**
 * Valida os dados contra as regras definidas
 *
 * @param array<string, mixed>|null $data Dados (opcional, usa setData se já configurado)
 * @return bool True se passou em todas as regras
 */
 public function validate(?array $data = null): bool
 {
 if ($data !== null) {
 $this->data = $data;
 }

 $this->errors = [];

 foreach ($this->rules as $field => $ruleSet) {
 $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
 $isRequired = in_array('required', $rules, true);
 $value = $this->data[$field] ?? null;
 $present = array_key_exists($field, $this->data);

 // Se não é required e o valor está vazio/nulo, pula as outras regras
 if (!$isRequired && ($value === null || $value === '' || $value === []) && !$present) {
 continue;
 }

 foreach ($rules as $rule) {
 $this->applyRule($field, $rule, $value);
 }
 }

 return $this->errors === [];
 }

 /**
 * Verifica se a validação falhou
 *
 * @return bool
 */
 public function fails(): bool
 {
 return $this->errors !== [];
 }

 /**
 * Verifica se a validação passou
 *
 * @return bool
 */
 public function passes(): bool
 {
 return $this->errors === [];
 }

 /**
 * Retorna todos os erros encontrados
 *
 * @return array<string, array<string>>
 */
 public function errors(): array
 {
 return $this->errors;
 }

 /**
 * Retorna primeiro erro de cada campo
 *
 * @return array<string, string>
 */
 public function firstErrors(): array
 {
 $first = [];
 foreach ($this->errors as $field => $fieldErrors) {
 $first[$field] = $fieldErrors[0] ?? '';
 }
 return $first;
 }

 /**
 * Retorna todos os erros em lista plana
 *
 * @return array<string>
 */
 public function flatErrors(): array
 {
 $flat = [];
 foreach ($this->errors as $fieldErrors) {
 foreach ($fieldErrors as $error) {
 $flat[] = $error;
 }
 }
 return $flat;
 }

 /**
 * Retorna primeiro erro absoluto (primeiro campo, primeira mensagem)
 *
 * @return string|null
 */
 public function firstError(): ?string
 {
 foreach ($this->errors as $fieldErrors) {
 return $fieldErrors[0] ?? null;
 }
 return null;
 }

 /**
 * Lança exceção se a validação falhar
 *
 * @param array<string, mixed>|null $data
 * @return static
 * @throws ValidationException
 */
 public function validateOrFail(?array $data = null): static
 {
 if (!$this->validate($data)) {
 throw new ValidationException($this->errors, $this->firstError());
 }
 return $this;
 }

 /**
 * Registra rule customizada
 *
 * @param string $name Nome da regra (ex: 'cpf')
 * @param callable(array<string, mixed>, string, string): (bool|string) $callback
 * Recebe ($data, $field, $param) e retorna true/false ou string de erro
 * @param string|null $messageTemplate Mensagem padrão com :attribute e :param
 */
 public static function extend(string $name, callable $callback, ?string $messageTemplate = null): void
 {
 self::$customRules[$name] = $callback;

 if ($messageTemplate !== null) {
 self::$defaultMessages[$name] = $messageTemplate;
 }
 }

 /**
 * Retorna dados validados (somente campos definidos nas regras)
 *
 * @return array<string, mixed>
 */
 public function validated(): array
 {
 $validated = [];
 foreach (array_keys($this->rules) as $field) {
 if (array_key_exists($field, $this->data)) {
 $validated[$field] = $this->data[$field];
 }
 }
 return $validated;
 }

 /**
 * Retorna somente os dados que NÃO estão nas regras
 *
 * @return array<string, mixed>
 */
 public function rejected(): array
 {
 return array_diff_key($this->data, $this->rules);
 }

 /**
 * Aplica uma regra específica a um campo
 */
 private function applyRule(string $field, string $rule, mixed $value): void
 {
 $param = null;
 $ruleName = $rule;

 // Extrai parâmetro se existir (ex: min:10, in:a,b,c)
 if (str_contains($rule, ':')) {
 [$ruleName, $param] = explode(':', $rule, 2);
 }

 // Verifica se é uma rule customizada
 if (isset(self::$customRules[$ruleName])) {
 $result = (self::$customRules[$ruleName])($this->data, $field, $param ?? '');
 if ($result === false || (is_string($result) && $result !== '')) {
 $this->addError($field, $ruleName, is_string($result) ? $result : null, $param);
 }
 return;
 }

 $passed = match ($ruleName) {
 'required' => $this->ruleRequired($value),
 'email' => $this->ruleEmail($value),
 'integer' => $this->ruleInteger($value),
 'numeric' => $this->ruleNumeric($value),
 'string' => is_string($value),
 'boolean' => $this->ruleBoolean($value),
 'array' => is_array($value),
 'min' => $this->ruleMin($value, (float)($param ?? 0)),
 'max' => $this->ruleMax($value, (float)($param ?? 0)),
 'between' => $this->ruleBetween($value, $param ?? ''),
 'confirmed' => $this->ruleConfirmed($field, $value),
 'same' => $this->ruleSame($value, $param ?? ''),
 'different' => $this->ruleDifferent($value, $param ?? ''),
 'in' => $this->ruleIn($value, $param ?? ''),
 'not_in' => $this->ruleNotIn($value, $param ?? ''),
 'regex' => $this->ruleRegex($value, $param ?? ''),
 'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
 'date' => $this->ruleDate($value),
 'before' => $this->ruleBefore($value, $param ?? ''),
 'after' => $this->ruleAfter($value, $param ?? ''),
 'alpha' => is_string($value) && $value !== '' && ctype_alpha($value),
 'alpha_num' => is_string($value) && $value !== '' && ctype_alnum($value),
 'alpha_dash' => is_string($value) && preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1,
 'size' => $this->ruleSize($value, (float)($param ?? 0)),
 'ip' => is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false,
 'json' => $this->ruleJson($value),
 'uuid' => $this->ruleUuid($value),
 'unique' => $this->ruleUnique($value, $param ?? ''),
 'exists' => $this->ruleExists($value, $param ?? ''),
 'min_length' => is_string($value) && mb_strlen($value) >= (int)($param ?? 0),
 'max_length' => is_string($value) && mb_strlen($value) <= (int)($param ?? 0),
 default => $this->applyUnknownRule($field, $ruleName, $value, $param),
 };

 if (!$passed) {
 $this->addError($field, $ruleName, param: $param);
 }
 }

 /**
 * Handler para regras desconhecidas
 */
 private function applyUnknownRule(string $field, string $ruleName, mixed $value, ?string $param): bool
 {
 if (!isset(self::$customRules[$ruleName])) {
 throw new InvalidArgumentException("Regra de validação desconhecida: {$ruleName}");
 }

 $result = (self::$customRules[$ruleName])($this->data, $field, $param ?? '');
 return $result !== false && !is_string($result);
 }

 // ==================== RULE IMPLEMENTATIONS ====================

 private function ruleRequired(mixed $value): bool
 {
 if ($value === null) return false;
 if (is_string($value) && trim($value) === '') return false;
 if (is_array($value) && $value === []) return false;
 return true;
 }

 private function ruleEmail(mixed $value): bool
 {
 if (!is_string($value) || $value === '') return false;
 return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
 }

 private function ruleInteger(mixed $value): bool
 {
 if ($value === null || $value === '') return false;
 return filter_var($value, FILTER_VALIDATE_INT) !== false;
 }

 private function ruleNumeric(mixed $value): bool
 {
 if ($value === null || $value === '') return false;
 return is_numeric($value);
 }

 private function ruleBoolean(mixed $value): bool
 {
 return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
 }

 private function ruleMin(mixed $value, float $min): bool
 {
 if (is_string($value)) return mb_strlen($value) >= $min;
 if (is_numeric($value)) return (float)$value >= $min;
 if (is_array($value)) return count($value) >= $min;
 return false;
 }

 private function ruleMax(mixed $value, float $max): bool
 {
 if (is_string($value)) return mb_strlen($value) <= $max;
 if (is_numeric($value)) return (float)$value <= $max;
 if (is_array($value)) return count($value) <= $max;
 return false;
 }

 private function ruleBetween(mixed $value, string $param): bool
 {
 $parts = explode(',', $param);
 if (count($parts) !== 2) return false;
 $min = (float)$parts[0];
 $max = (float)$parts[1];
 return $this->ruleMin($value, $min) && $this->ruleMax($value, $max);
 }

 private function ruleConfirmed(string $field, mixed $value): bool
 {
 $confirmField = $field . '_confirmation';
 return isset($this->data[$confirmField]) && $this->data[$confirmField] === $value;
 }

 private function ruleSame(mixed $value, string $otherField): bool
 {
 return isset($this->data[$otherField]) && $this->data[$otherField] === $value;
 }

 private function ruleDifferent(mixed $value, string $otherField): bool
 {
 return !isset($this->data[$otherField]) || $this->data[$otherField] !== $value;
 }

 private function ruleIn(mixed $value, string $param): bool
 {
 $allowed = explode(',', $param);
 return in_array((string)$value, $allowed, true);
 }

 private function ruleNotIn(mixed $value, string $param): bool
 {
 $disallowed = explode(',', $param);
 return !in_array((string)$value, $disallowed, true);
 }

 private function ruleRegex(mixed $value, string $pattern): bool
 {
 if (!is_string($value)) return false;
 return preg_match($pattern, $value) === 1;
 }

 private function ruleDate(mixed $value): bool
 {
 if (!is_string($value) || $value === '') return false;
 return strtotime($value) !== false;
 }

 private function ruleBefore(mixed $value, string $date): bool
 {
 if (!is_string($value)) return false;
 $ts1 = strtotime($value);
 $ts2 = $date === 'now' ? time() : strtotime($date);
 return $ts1 !== false && $ts2 !== false && $ts1 < $ts2;
 }

 private function ruleAfter(mixed $value, string $date): bool
 {
 if (!is_string($value)) return false;
 $ts1 = strtotime($value);
 $ts2 = $date === 'now' ? time() : strtotime($date);
 return $ts1 !== false && $ts2 !== false && $ts1 > $ts2;
 }

 private function ruleSize(mixed $value, float $size): bool
 {
 if (is_string($value)) return mb_strlen($value) === (int)$size;
 if (is_numeric($value)) return (float)$value === $size;
 if (is_array($value)) return count($value) === (int)$size;
 return false;
 }

 private function ruleJson(mixed $value): bool
 {
 if (!is_string($value) || $value === '') return false;

 try {
 json_decode($value, false, 512, JSON_THROW_ON_ERROR);
 return true;
 } catch (JsonException) {
 return false;
 }
 }

 private function ruleUuid(mixed $value): bool
 {
 if (!is_string($value)) return false;
 return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
 }

 private function ruleUnique(mixed $value, string $param): bool
 {
 // Formato: "tabela,coluna,exceptId"
 // Esta rule requer integração externa; retorna true por padrão
 // O usuário deve implementar via Validator::extend('unique', ...)
 // Mantemos aqui para compatibilidade com regras declarativas
 return true;
 }

 private function ruleExists(mixed $value, string $param): bool
 {
 // Formato: "tabela,coluna"
 // Esta rule requer integração externa; retorna true por padrão
 // O usuário deve implementar via Validator::extend('exists', ...)
 return true;
 }

 /**
 * Adiciona erro ao campo
 */
 private function addError(string $field, string $rule, ?string $customMessage = null, ?string $param = null): void
 {
 if (!isset($this->errors[$field])) {
 $this->errors[$field] = [];
 }

 $label = $this->customLabels[$field] ?? str_replace('_', ' ', $field);

 // Mensagem customizada tem prioridade: 'field.rule' ou 'rule'
 $customKey = "{$field}.{$rule}";
 if (isset($this->customMessages[$customKey])) {
 $message = $this->customMessages[$customKey];
 } elseif (isset($this->customMessages[$rule])) {
 $message = $this->customMessages[$rule];
 } elseif ($customMessage !== null) {
 $message = $customMessage;
 } else {
 $message = self::$defaultMessages[$rule] ?? "O campo :attribute falhou na validação :rule.";
 }

 $message = str_replace(':attribute', $label, $message);
 $message = str_replace(':rule', $rule, $message);

 if ($param !== null) {
 // Parseia between:min,max em :min e :max
 if (str_contains($param, ',')) {
 $parts = explode(',', $param, 2);
 $message = str_replace(':min', $parts[0], $message);
 $message = str_replace(':max', $parts[1], $message);
 $message = str_replace(':values', $param, $message);
 } else {
 $message = str_replace(':min', $param, $message);
 $message = str_replace(':max', $param, $message);
 $message = str_replace(':size', $param, $message);
 $message = str_replace(':date', $param, $message);
 $message = str_replace(':other', $param, $message);
 $message = str_replace(':values', $param, $message);
 }
 }

 $this->errors[$field][] = $message;
 }

 /**
 * Factory estático para validação rápida
 *
 * @param array<string, mixed> $data
 * @param array<string, string|array<string>> $rules
 * @param array<string, string> $messages
 * @return static
 */
 public static function make(array $data, array $rules, array $messages = []): static
 {
 $instance = new static();
 $instance->setData($data);
 $instance->setRules($rules);
 if ($messages !== []) {
 $instance->setMessages($messages);
 }
 return $instance;
 }
}