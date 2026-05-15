<?php

namespace App\ProductModifier\Application\DeleteProductModifier;

use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;

class DeleteProductModifier
{
    public function __construct(
        private ProductModifierRepositoryInterface $modifierRepository,
    ) {}

    public function __invoke(DeleteProductModifierCommand $command): void
    {
        $modifier = $this->modifierRepository->findById($command->id)
            ?? throw ProductModifierNotFoundException::withId($command->id);

        $this->modifierRepository->deleteById($command->id);
    }
}
