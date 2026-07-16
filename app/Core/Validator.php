<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reusable server-side validator. Rules are declared per field as a
 * pipe-delimited string, e.g. 'required|email|max:255'.
 *
 * Supported rules: required, email, numeric, integer, min:N, max:N,
 * between:A,B, in:a,b,c, date, regex:/.../ , confirmed, boolean, decimal,
 * phone, alpha_num, string, url.
 */
final class Validator
{
    /** @var array<string,mixed> */
    private array $data;
    /** @var array<string,string> */
    private array $rules;
    /** @var array<string,string> */
    private array $labels;
    /** @var array<string,string> */
    private array $errors = [];

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->labels = $labels;
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function passes(): bool
    {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleString);
            $isRequired = in_array('required', $rules, true);

            // Skip optional empty fields.
            if (!$isRequired && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === '') {
                    continue;
                }
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if (!$this->applyRule($field, $name, $param, $value)) {
                    break; // stop on first error per field
                }
            }
        }
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucwords(str_replace(['_', '-'], ' ', $field));
    }

    private function fail(string $field, string $message): bool
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return false;
    }

    private function applyRule(string $field, string $name, ?string $param, mixed $value): bool
    {
        $label = $this->label($field);
        $str = is_scalar($value) ? (string) $value : '';

        return match ($name) {
            'required'  => ($value !== null && $value !== '' && $value !== [])
                ? true : $this->fail($field, "{$label} is required."),
            'string'    => is_string($value) ? true : $this->fail($field, "{$label} must be text."),
            'email'     => filter_var($str, FILTER_VALIDATE_EMAIL)
                ? true : $this->fail($field, "{$label} must be a valid email address."),
            'url'       => filter_var($str, FILTER_VALIDATE_URL)
                ? true : $this->fail($field, "{$label} must be a valid URL."),
            'numeric'   => is_numeric($str) ? true : $this->fail($field, "{$label} must be a number."),
            'decimal'   => preg_match('/^-?\d{1,10}(\.\d{1,2})?$/', $str)
                ? true : $this->fail($field, "{$label} must be a valid amount."),
            'integer'   => preg_match('/^-?\d+$/', $str)
                ? true : $this->fail($field, "{$label} must be a whole number."),
            'boolean'   => in_array($str, ['0', '1', 'true', 'false', 'on', ''], true)
                ? true : $this->fail($field, "{$label} is invalid."),
            'alpha_num' => preg_match('/^[A-Za-z0-9 ]+$/', $str)
                ? true : $this->fail($field, "{$label} may only contain letters and numbers."),
            'phone'     => preg_match('/^[0-9+\-\s()]{6,20}$/', $str)
                ? true : $this->fail($field, "{$label} must be a valid phone number."),
            'date'      => $this->validDate($str)
                ? true : $this->fail($field, "{$label} must be a valid date."),
            'min'       => $this->checkMin($str, (int) $param)
                ? true : $this->fail($field, "{$label} must be at least {$param} characters."),
            'max'       => $this->checkMax($str, (int) $param)
                ? true : $this->fail($field, "{$label} may not be more than {$param} characters."),
            'min_val'   => (is_numeric($str) && (float) $str >= (float) $param)
                ? true : $this->fail($field, "{$label} must be at least {$param}."),
            'max_val'   => (is_numeric($str) && (float) $str <= (float) $param)
                ? true : $this->fail($field, "{$label} may not be greater than {$param}."),
            'between'   => $this->checkBetween($str, $param)
                ? true : $this->fail($field, "{$label} is out of the allowed range."),
            'in'        => in_array($str, explode(',', (string) $param), true)
                ? true : $this->fail($field, "{$label} is invalid."),
            'confirmed' => (($this->data[$field . '_confirmation'] ?? null) === $value)
                ? true : $this->fail($field, "{$label} confirmation does not match."),
            'regex'     => (@preg_match((string) $param, $str) === 1)
                ? true : $this->fail($field, "{$label} format is invalid."),
            default     => true,
        };
    }

    private function checkMin(string $value, int $min): bool
    {
        return mb_strlen($value) >= $min;
    }

    private function checkMax(string $value, int $max): bool
    {
        return mb_strlen($value) <= $max;
    }

    private function checkBetween(string $value, ?string $param): bool
    {
        [$a, $b] = array_pad(explode(',', (string) $param), 2, null);
        if (!is_numeric($value)) {
            return false;
        }
        return (float) $value >= (float) $a && (float) $value <= (float) $b;
    }

    private function validDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $d = date_create($value);
        return $d !== false;
    }
}
