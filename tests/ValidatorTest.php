<?php

declare(strict_types=1);

namespace JonatasFenske\Validator\Tests;

use JonatasFenske\Validator\ValidationException;
use JonatasFenske\Validator\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do componente Validator para PHP 8.5
 */
final class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        // Limpa rules customizadas entre testes
        $reflection = new \ReflectionClass(Validator::class);
        $prop = $reflection->getProperty('customRules');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testRequiredPassesWithValue(): void
    {
        $v = Validator::make(['name' => 'João'], ['name' => 'required']);
        self::assertTrue($v->validate());
    }

    public function testRequiredFailsWithEmptyString(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        self::assertFalse($v->validate());
        self::assertArrayHasKey('name', $v->errors());
    }

    public function testRequiredFailsWithNull(): void
    {
        $v = Validator::make(['name' => null], ['name' => 'required']);
        self::assertFalse($v->validate());
    }

    public function testRequiredFailsWhenFieldMissing(): void
    {
        $v = Validator::make([], ['name' => 'required']);
        self::assertFalse($v->validate());
    }

    public function testNonRequiredSkipsValidationWhenAbsent(): void
    {
        $v = Validator::make([], ['name' => 'email']);
        self::assertTrue($v->validate());
    }

    public function testEmailValidatesCorrectly(): void
    {
        $v = Validator::make(['email' => 'joao@example.com'], ['email' => 'required|email']);
        self::assertTrue($v->validate());
    }

    public function testEmailRejectsInvalidFormat(): void
    {
        $v = Validator::make(['email' => 'invalid-email'], ['email' => 'required|email']);
        self::assertFalse($v->validate());
        self::assertStringContainsString('email válido', $v->firstError());
    }

    public function testIntegerRule(): void
    {
        $pass = Validator::make(['age' => '25'], ['age' => 'integer']);
        $fail = Validator::make(['age' => 'abc'], ['age' => 'integer']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testNumericRule(): void
    {
        $pass = Validator::make(['price' => '19.99'], ['price' => 'numeric']);
        $fail = Validator::make(['price' => 'abc'], ['price' => 'numeric']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testBooleanRule(): void
    {
        foreach ([true, false, 0, 1, '0', '1', 'true', 'false'] as $val) {
            $v = Validator::make(['active' => $val], ['active' => 'boolean']);
            self::assertTrue($v->validate(), "Deveria aceitar: " . var_export($val, true));
        }

        $fail = Validator::make(['active' => 'yes'], ['active' => 'boolean']);
        self::assertFalse($fail->validate());
    }

    public function testMinRuleForString(): void
    {
        $pass = Validator::make(['name' => 'João'], ['name' => 'min:3']);
        $fail = Validator::make(['name' => 'AB'], ['name' => 'min:3']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testMinRuleForNumber(): void
    {
        $pass = Validator::make(['age' => 18], ['age' => 'min:18']);
        $fail = Validator::make(['age' => 17], ['age' => 'min:18']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testMaxRuleForString(): void
    {
        $pass = Validator::make(['bio' => 'Short'], ['bio' => 'max:100']);
        $fail = Validator::make(['bio' => str_repeat('a', 101)], ['bio' => 'max:100']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testBetweenRule(): void
    {
        $pass = Validator::make(['age' => 25], ['age' => 'between:18,65']);
        $failLow = Validator::make(['age' => 10], ['age' => 'between:18,65']);
        $failHigh = Validator::make(['age' => 70], ['age' => 'between:18,65']);

        self::assertTrue($pass->validate());
        self::assertFalse($failLow->validate());
        self::assertFalse($failHigh->validate());
    }

    public function testConfirmedRule(): void
    {
        $pass = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'secret123'],
            ['password' => 'confirmed']
        );
        $fail = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'different'],
            ['password' => 'confirmed']
        );

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testSameRule(): void
    {
        $pass = Validator::make(['a' => 'x', 'b' => 'x'], ['a' => 'same:b']);
        $fail = Validator::make(['a' => 'x', 'b' => 'y'], ['a' => 'same:b']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testDifferentRule(): void
    {
        $pass = Validator::make(['a' => 'x', 'b' => 'y'], ['a' => 'different:b']);
        $fail = Validator::make(['a' => 'x', 'b' => 'x'], ['a' => 'different:b']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testInRule(): void
    {
        $pass = Validator::make(['status' => 'active'], ['status' => 'in:active,inactive,pending']);
        $fail = Validator::make(['status' => 'deleted'], ['status' => 'in:active,inactive,pending']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testNotInRule(): void
    {
        $pass = Validator::make(['role' => 'user'], ['role' => 'not_in:admin,superadmin']);
        $fail = Validator::make(['role' => 'admin'], ['role' => 'not_in:admin,superadmin']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testUrlRule(): void
    {
        $pass = Validator::make(['site' => 'https://example.com'], ['site' => 'url']);
        $fail = Validator::make(['site' => 'not-a-url'], ['site' => 'url']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testDateRule(): void
    {
        $pass = Validator::make(['date' => '2025-06-15'], ['date' => 'date']);
        $fail = Validator::make(['date' => 'not-a-date'], ['date' => 'date']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testBeforeRule(): void
    {
        $pass = Validator::make(['start' => '2020-01-01'], ['start' => 'before:2025-01-01']);
        $fail = Validator::make(['start' => '2030-01-01'], ['start' => 'before:2025-01-01']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testAfterRule(): void
    {
        $pass = Validator::make(['end' => '2030-01-01'], ['end' => 'after:2025-01-01']);
        $fail = Validator::make(['end' => '2020-01-01'], ['end' => 'after:2025-01-01']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testAlphaRule(): void
    {
        $pass = Validator::make(['name' => 'Joao'], ['name' => 'alpha']);
        $fail = Validator::make(['name' => 'Joao123'], ['name' => 'alpha']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testAlphaNumRule(): void
    {
        $pass = Validator::make(['code' => 'ABC123'], ['code' => 'alpha_num']);
        $fail = Validator::make(['code' => 'ABC-123'], ['code' => 'alpha_num']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testAlphaDashRule(): void
    {
        $pass = Validator::make(['slug' => 'meu-post_01'], ['slug' => 'alpha_dash']);
        $fail = Validator::make(['slug' => 'meu post!'], ['slug' => 'alpha_dash']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testIpRule(): void
    {
        $pass = Validator::make(['ip' => '192.168.1.1'], ['ip' => 'ip']);
        $fail = Validator::make(['ip' => '999.999.999.999'], ['ip' => 'ip']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testJsonRule(): void
    {
        $pass = Validator::make(['data' => '{"key":"value"}'], ['data' => 'json']);
        $fail = Validator::make(['data' => '{invalid}'], ['data' => 'json']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testUuidRule(): void
    {
        $pass = Validator::make(['id' => '550e8400-e29b-41d4-a716-446655440000'], ['id' => 'uuid']);
        $fail = Validator::make(['id' => 'not-a-uuid'], ['id' => 'uuid']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testSizeRuleForString(): void
    {
        $pass = Validator::make(['code' => 'ABCDE'], ['code' => 'size:5']);
        $fail = Validator::make(['code' => 'ABC'], ['code' => 'size:5']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testMultipleRulesOnSameField(): void
    {
        $v = Validator::make(
            ['email' => 'joao@example.com'],
            ['email' => 'required|email|max:255']
        );
        self::assertTrue($v->validate());
    }

    public function testMultipleFieldsValidation(): void
    {
        $v = Validator::make(
            [
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'age' => 25,
            ],
            [
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'age' => 'required|integer|min:18',
            ]
        );
        self::assertTrue($v->validate());
    }

    public function testCustomMessages(): void
    {
        $v = Validator::make(
            ['email' => 'invalid'],
            ['email' => 'required|email'],
            ['email.email' => 'Por favor, informe um email válido.']
        );
        $v->validate();

        self::assertContains('Por favor, informe um email válido.', $v->errors()['email']);
    }

    public function testCustomLabels(): void
    {
        $v = Validator::make([], ['user_email' => 'required']);
        $v->setLabels(['user_email' => 'E-mail do Usuário']);
        $v->validate();

        self::assertStringContainsString('E-mail do Usuário', $v->firstError());
    }

    public function testFirstErrorsReturnsOnePerField(): void
    {
        $v = Validator::make(
            ['name' => '', 'email' => 'bad'],
            ['name' => 'required|min:3', 'email' => 'required|email']
        );
        $v->validate();

        $first = $v->firstErrors();
        self::assertCount(2, $first);
        self::assertArrayHasKey('name', $first);
        self::assertArrayHasKey('email', $first);
    }

    public function testFlatErrorsReturnsAllMessages(): void
    {
        $v = Validator::make(
            ['name' => '', 'email' => 'bad'],
            ['name' => 'required|min:3', 'email' => 'email']
        );
        $v->validate();

        $flat = $v->flatErrors();
        self::assertGreaterThanOrEqual(2, count($flat));
    }

    public function testValidateOrFailThrowsException(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);

        $this->expectException(ValidationException::class);
        $v->validateOrFail();
    }

    public function testValidationExceptionContainsErrors(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);

        try {
            $v->validateOrFail();
            self::fail('Deveria ter lançado ValidationException');
        } catch (ValidationException $e) {
            self::assertNotEmpty($e->getErrors());
            self::assertNotEmpty($e->getFirstErrors());
            self::assertNotEmpty($e->getFlatErrors());
            self::assertSame(422, $e->getCode());
        }
    }

    public function testValidatedReturnsOnlyRuledFields(): void
    {
        $v = Validator::make(
            ['name' => 'João', 'email' => 'joao@test.com', 'extra' => 'ignored'],
            ['name' => 'required', 'email' => 'required|email']
        );
        $v->validate();

        $validated = $v->validated();
        self::assertArrayHasKey('name', $validated);
        self::assertArrayHasKey('email', $validated);
        self::assertArrayNotHasKey('extra', $validated);
    }

    public function testRejectedReturnsNonRuledFields(): void
    {
        $v = Validator::make(
            ['name' => 'João', 'extra' => 'surprise'],
            ['name' => 'required']
        );
        $v->validate();

        $rejected = $v->rejected();
        self::assertArrayHasKey('extra', $rejected);
        self::assertArrayNotHasKey('name', $rejected);
    }

    public function testCustomRuleExtension(): void
    {
        Validator::extend('cpf', function (array $data, string $field, string $param): bool {
            $cpf = preg_replace('/[^0-9]/', '', (string)($data[$field] ?? ''));
            return strlen($cpf) === 11;
        }, 'O campo :attribute deve conter um CPF válido.');

        $pass = Validator::make(['cpf' => '12345678901'], ['cpf' => 'cpf']);
        $fail = Validator::make(['cpf' => '123'], ['cpf' => 'cpf']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
        self::assertStringContainsString('CPF válido', $fail->firstError());
    }

    public function testPassesAndFailsMethods(): void
    {
        $pass = Validator::make(['name' => 'OK'], ['name' => 'required']);
        $fail = Validator::make(['name' => ''], ['name' => 'required']);

        $pass->validate();
        $fail->validate();

        self::assertTrue($pass->passes());
        self::assertFalse($pass->fails());
        self::assertTrue($fail->fails());
        self::assertFalse($fail->passes());
    }

    public function testFluentSettersReturnSelf(): void
    {
        $v = new Validator();
        $result = $v->setRules(['a' => 'required'])->setData(['a' => '1'])->setMessages([])->setLabels([]);

        self::assertInstanceOf(Validator::class, $result);
    }

    public function testUnknownRuleThrowsException(): void
    {
        $v = Validator::make(['name' => 'test'], ['name' => 'nonexistent_rule']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Regra de validação desconhecida');
        $v->validate();
    }

    public function testArrayRule(): void
    {
        $pass = Validator::make(['items' => [1, 2, 3]], ['items' => 'array']);
        $fail = Validator::make(['items' => 'not-array'], ['items' => 'array']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testStringRule(): void
    {
        $pass = Validator::make(['name' => 'text'], ['name' => 'string']);
        $fail = Validator::make(['name' => 123], ['name' => 'string']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testMinLengthAndMaxLength(): void
    {
        $pass = Validator::make(['bio' => 'Hello World'], ['bio' => 'min_length:5|max_length:20']);
        $failShort = Validator::make(['bio' => 'Hi'], ['bio' => 'min_length:5']);
        $failLong = Validator::make(['bio' => str_repeat('a', 21)], ['bio' => 'max_length:20']);

        self::assertTrue($pass->validate());
        self::assertFalse($failShort->validate());
        self::assertFalse($failLong->validate());
    }

    public function testRegexRule(): void
    {
        $pass = Validator::make(['phone' => '(11) 99999-9999'], ['phone' => 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/']);
        $fail = Validator::make(['phone' => '11999999999'], ['phone' => 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/']);

        self::assertTrue($pass->validate());
        self::assertFalse($fail->validate());
    }

    public function testComplexRealWorldScenario(): void
    {
        $v = Validator::make(
            [
                'name' => 'Maria Silva',
                'email' => 'maria@example.com',
                'age' => 30,
                'website' => 'https://maria.dev',
                'role' => 'editor',
                'bio' => 'Desenvolvedora PHP apaixonada por código limpo',
            ],
            [
                'name' => 'required|string|min:2|max:100',
                'email' => 'required|email',
                'age' => 'required|integer|between:18,120',
                'website' => 'url',
                'role' => 'required|in:admin,editor,author',
                'bio' => 'string|max:500',
            ]
        );

        self::assertTrue($v->validate());
        self::assertTrue($v->passes());
        self::assertSame([], $v->errors());
    }
}