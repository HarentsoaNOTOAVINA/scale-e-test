<?php

namespace App\Services;

use App\Domain\Repositories\CustomerRepositoryInterface;
use App\Domain\Repositories\EventRepositoryInterface;
use Exception;

class CustomerProfileService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepo,
        private EventRepositoryInterface $eventRepo
    ) {}

    public function getProfileById(int $id): array
    {
        $customer = $this->customerRepo->findById($id);
        if (!$customer) {
            throw new Exception("Customer with ID $id not found.", 404);
        }

        return $this->buildProfileData($customer->id, $customer->toArray());
    }

    public function getProfileByEmail(string $email): array
    {
        $customer = $this->customerRepo->findByEmail($email);
        if (!$customer) {
            throw new Exception("Customer with email '$email' not found.", 404);
        }

        return $this->buildProfileData($customer->id, $customer->toArray());
    }

    private function buildProfileData(int $customerId, array $customerData): array
    {
        $recentEvents = $this->eventRepo->getRecentByCustomerId($customerId, 10);
        $statistics = $this->customerRepo->getCustomerStats($customerId);

        return [
            'customer' => $customerData,
            'statistics' => $statistics,
            'recent_events' => $recentEvents,
        ];
    }
}
