<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Infrastructure\Persistence\MySqlCustomerRepository;
use App\Infrastructure\Persistence\MySqlEventRepository;
use App\Services\CustomerProfileService;
use Exception;

class CustomerController
{
    private CustomerProfileService $profileService;
    private MySqlCustomerRepository $customerRepo;
    private MySqlEventRepository $eventRepo;

    public function __construct(?CustomerProfileService $profileService = null)
    {
        $this->customerRepo = new MySqlCustomerRepository();
        $this->eventRepo = new MySqlEventRepository();
        $this->profileService = $profileService ?? new CustomerProfileService(
            $this->customerRepo,
            $this->eventRepo
        );
    }

    public function getProfile(Request $request, string $idOrEmail): Response
    {
        try {
            if (is_numeric($idOrEmail)) {
                $data = $this->profileService->getProfileById((int)$idOrEmail);
            } else {
                $data = $this->profileService->getProfileByEmail($idOrEmail);
            }

            return Response::json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return Response::error($e->getMessage(), $code);
        }
    }

    public function listCustomers(Request $request): Response
    {
        $limit = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);

        $customers = $this->customerRepo->getAll($limit, $offset);
        $total = $this->customerRepo->count();

        return Response::json([
            'status' => 'success',
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'data' => $customers
        ]);
    }

    public function listRecentEvents(Request $request): Response
    {
        $limit = (int)$request->getQuery('limit', 50);
        $events = $this->eventRepo->getAllRecent($limit);

        return Response::json([
            'status' => 'success',
            'meta' => [
                'total' => count($events),
                'limit' => $limit,
            ],
            'data' => $events
        ]);
    }
}
