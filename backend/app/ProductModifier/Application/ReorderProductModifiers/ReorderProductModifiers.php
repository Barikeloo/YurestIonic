<?php

namespace App\ProductModifier\Application\ReorderProductModifiers;

use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class ReorderProductModifiers
{
    public function __construct(
        private ProductModifierRepositoryInterface $modifierRepository,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(ReorderProductModifiersCommand $command): void
    {
        $this->transactionManager->run(function () use ($command): void {
            foreach ($command->items as $item) {
                $modifier = $this->modifierRepository->findById($item['id'])
                    ?? throw ProductModifierNotFoundException::withId($item['id']);

                if ($modifier->productId()->value() !== $command->productId) {
                    throw ProductModifierNotFoundException::withId($item['id']);
                }

                $modifier->reorder((int) $item['sort_order']);
                $this->modifierRepository->save($modifier);
            }
        });
    }
}
