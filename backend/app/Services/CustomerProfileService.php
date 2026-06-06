<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Support\Enums\CustomerType;

class CustomerProfileService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function updateProfile(Customer $customer, array $data): Customer
    {
        $fields = array_filter([
            'name'       => $data['name'] ?? null,
            'email'      => $data['email'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
        ], fn ($v) => $v !== null);

        // Providing a GST number upgrades the account to B2B
        if (! empty($fields['gst_number'])) {
            $fields['type'] = CustomerType::B2B;
        }

        return $this->customerRepository->update($customer, $fields);
    }
}
