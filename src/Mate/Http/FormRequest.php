<?php

namespace Mate\Http;

abstract class FormRequest
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}