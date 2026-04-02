<?php

namespace App\Restaurant\Application\ValidateRestaurantCompanyMode;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

final class ValidateRestaurantCompanyMode
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $taxId, string $companyMode): ValidateRestaurantCompanyModeResponse
    {
        $companyExists = count($this->restaurantRepository->findByTaxId($taxId)) > 0;

        if ($companyMode === 'new' && $companyExists) {
            return ValidateRestaurantCompanyModeResponse::taxIdAlreadyExists();
        }

        if ($companyMode === 'existing' && ! $companyExists) {
            return ValidateRestaurantCompanyModeResponse::taxIdDoesNotExist();
        }

        return ValidateRestaurantCompanyModeResponse::success();
    }
}
