<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @return array<string, mixed>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $value = $data[$field] ?? null;
            $isRequired = in_array('required', $fieldRules, true);
            $isNullable = in_array('nullable', $fieldRules, true);

            if ($isRequired && self::isEmpty($value)) {
                $errors[$field][] = 'O campo é obrigatório.';
                continue;
            }

            if (!$isRequired && self::isEmpty($value)) {
                if ($isNullable) {
                    $validated[$field] = null;
                }

                continue;
            }

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                $message = self::validateRule($field, $value, $rule);

                if ($message !== null) {
                    $errors[$field][] = $message;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    private static function validateRule(string $field, mixed $value, string $rule): ?string
    {
        if ($rule === 'string' && !is_string($value)) {
            return 'O campo deve ser um texto.';
        }

        if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            return 'O campo deve ser um numero inteiro.';
        }

        if ($rule === 'numeric' && !is_numeric($value)) {
            return 'O campo deve ser numerico.';
        }

        if ($rule === 'boolean' && !is_bool($value) && !in_array($value, [0, 1, '0', '1'], true)) {
            return 'O campo deve ser booleano.';
        }

        if ($rule === 'array' && !is_array($value)) {
            return 'O campo deve ser uma lista.';
        }

        if ($rule === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return 'O campo deve ser um email valido.';
        }

        if ($rule === 'url' && filter_var($value, FILTER_VALIDATE_URL) === false) {
            return 'O campo deve ser uma URL valida.';
        }

        if (str_starts_with($rule, 'min:') && is_string($value) && strlen($value) < (int) substr($rule, 4)) {
            return 'O campo deve ter no minimo ' . substr($rule, 4) . ' caracteres.';
        }

        if (str_starts_with($rule, 'max:') && is_string($value) && strlen($value) > (int) substr($rule, 4)) {
            return 'O campo deve ter no maximo ' . substr($rule, 4) . ' caracteres.';
        }

        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));

            if (!in_array((string) $value, $allowed, true)) {
                return 'O campo deve ser um destes valores: ' . implode(', ', $allowed) . '.';
            }
        }

        return null;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
