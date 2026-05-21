<?php
/**
 * Validator - Classe de Validação de Dados
 * Validação centralizada para formulários
 */

namespace App;

class Validator
{
    protected $errors = [];
    
    /**
     * Valida campo obrigatório
     */
    public function required($value, $fieldName)
    {
        if (empty($value) && $value !== '0') {
            $this->errors[$fieldName] = "O campo {$fieldName} é obrigatório.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida email
     */
    public function email($value, $fieldName = 'E-mail')
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "O {$fieldName} não é válido.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida tamanho mínimo
     */
    public function min($value, $min, $fieldName)
    {
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ter no mínimo {$min} caracteres.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida tamanho máximo
     */
    public function max($value, $max, $fieldName)
    {
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ter no máximo {$max} caracteres.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida se é numérico
     */
    public function numeric($value, $fieldName)
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ser numérico.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida data
     */
    public function date($value, $fieldName = 'Data')
    {
        if (!empty($value)) {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->errors[$fieldName] = "O campo {$fieldName} não é uma data válida.";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida se valores são iguais (confirmação de senha)
     */
    public function match($value1, $value2, $fieldName = 'Confirmação')
    {
        if ($value1 !== $value2) {
            $this->errors[$fieldName] = "Os campos não coincidem.";
            return false;
        }
        return true;
    }
    
    /**
     * Verifica se há erros
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
    
    /**
     * Retorna todos os erros
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Retorna primeiro erro
     */
    public function getFirstError()
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Limpa erros
     */
    public function clearErrors()
    {
        $this->errors = [];
    }
}
