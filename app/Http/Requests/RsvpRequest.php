<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // formulário público
    }

    public function rules(): array
    {
        return [
            'nome'              => ['required', 'string', 'max:255'],
            'confirma_presenca' => ['required', 'boolean'],
            'adultos'           => ['nullable', 'integer', 'min:1', 'max:10'],
            'criancas'          => ['nullable', 'integer', 'min:0', 'max:10'],
            'email'             => ['required', 'email', 'max:255'],
            'telefone'          => ['nullable', 'string', 'max:20'],
            'observacoes'       => ['nullable', 'string', 'max:500'],
            'consentimento'     => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'          => 'Informe seu nome completo.',
            'email.required'         => 'Informe seu e-mail.',
            'email.email'            => 'Esse e-mail não parece válido.',
            'consentimento.accepted' => 'Para continuar, aceite o uso dos seus dados.',
        ];
    }
}