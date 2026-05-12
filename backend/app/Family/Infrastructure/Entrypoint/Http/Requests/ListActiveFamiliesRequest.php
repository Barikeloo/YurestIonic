<?php

namespace App\Family\Infrastructure\Entrypoint\Http\Requests;

use App\Family\Application\ListActiveFamilies\ListActiveFamiliesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListActiveFamiliesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): ListActiveFamiliesCommand
    {
        return new ListActiveFamiliesCommand;
    }
}
