<?php
/**
 * Input Data Validator
 * 
 * Validates array payloads against declarative rules.
 */

declare(strict_types=1);

namespace App\Helpers;

class Validator {
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self {
        $validator = new self($data, $rules);
        $validator->validate();
        return $validator;
    }

    public function validate(): bool {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rulesList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rulesList as $rule) {
                $ruleParam = null;
                if (str_contains($rule, ':')) {
                    [$rule, $ruleParam] = explode(':', $rule, 2);
                }

                $this->applyRule($field, $value, $rule, $ruleParam);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;

            case 'min':
                $min = (int) $param;
                if (is_string($value) && strlen($value) < $min) {
                    $this->addError($field, "The {$field} must be at least {$min} characters.");
                } elseif (is_numeric($value) && (float) $value < $min) {
                    $this->addError($field, "The {$field} must be at least {$min}.");
                }
                break;

            case 'max':
                $max = (int) $param;
                if (is_string($value) && strlen($value) > $max) {
                    $this->addError($field, "The {$field} may not exceed {$max} characters.");
                } elseif (is_numeric($value) && (float) $value > $max) {
                    $this->addError($field, "The {$field} may not exceed {$max}.");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a numeric value.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if (!empty($value) && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, "The selected {$field} is invalid.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): ?string {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
}
