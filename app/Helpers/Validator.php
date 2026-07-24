<?php

namespace App\Helpers;

use App\Database\DatabaseConnection;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }

    public function validate(array $rules): void
    {
        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    list($ruleName, $paramStr) = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $value, $ruleName, $params);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, array $params): void
    {
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

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "The {$field} must be an integer.");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (is_string($value) && strlen($value) < $min) {
                    $this->addError($field, "The {$field} must be at least {$min} characters.");
                } elseif (is_numeric($value) && $value < $min) {
                    $this->addError($field, "The {$field} must be at least {$min}.");
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 0);
                if (is_string($value) && strlen($value) > $max) {
                    $this->addError($field, "The {$field} must not exceed {$max} characters.");
                } elseif (is_numeric($value) && $value > $max) {
                    $this->addError($field, "The {$field} must not exceed {$max}.");
                }
                break;

            case 'in':
                if (!empty($value) && !in_array((string)$value, $params, true)) {
                    $this->addError($field, "The selected {$field} is invalid. Allowed values: " . implode(', ', $params));
                }
                break;

            case 'unique':
                // Format: unique:table,column
                if (!empty($value) && count($params) >= 2) {
                    $table = $params[0];
                    $column = $params[1];
                    $exceptId = $params[2] ?? null;

                    $sql = "SELECT id FROM `{$table}` WHERE `{$column}` = ? ";
                    $sqlParams = [$value];

                    if ($exceptId !== null) {
                        $sql .= "AND id != ? ";
                        $sqlParams[] = $exceptId;
                    }
                    $sql .= "LIMIT 1";

                    $exists = DatabaseConnection::fetchOne($sql, $sqlParams);
                    if ($exists) {
                        $this->addError($field, "The {$field} has already been taken.");
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
