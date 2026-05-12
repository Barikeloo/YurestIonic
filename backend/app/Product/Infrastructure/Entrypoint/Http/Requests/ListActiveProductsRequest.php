<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\ListActiveProducts\ListActiveProductsCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListActiveProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): ListActiveProductsCommand
    {
        return new ListActiveProductsCommand;
    }
}
