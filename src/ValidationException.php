<?php

declare(strict_types=1);

namespace JonatasFenske\Validator;

use RuntimeException;

/**
 * Exceção lançada quando validação falha via validateOrFail()
 * PHP 8.5: tipagem estrita, readonly properties
 */
class ValidationException extends RuntimeException
{
    /** @var array<string, array<string>> Erros por campo */
    private readonly array $errors;

    /**
     * @param array<string, array<string>> $errors Erros de validação
     * @param string|null $message Primeira mensagem de erro
     */
    public function __construct(array $errors, ?string $message = null)
    {
        $this->errors = $errors;
        parent::__construct($message ?? 'Erro de validação', 422);
    }

    /**
     * Retorna todos os erros de validação
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna primeiro erro de cada campo
     *
     * @return array<string, string>
     */
    public function getFirstErrors(): array
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
    public function getFlatErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flat[] = $error;
            }
        }
        return $flat;
    }
}