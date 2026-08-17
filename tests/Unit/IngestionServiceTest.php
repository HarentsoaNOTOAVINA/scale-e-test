<?php

namespace App\Tests\Unit;

use App\Domain\Repositories\CustomerRepositoryInterface;
use App\Domain\Repositories\EventRepositoryInterface;
use App\Services\IngestionService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IngestionServiceTest extends TestCase
{
    public function testIngestFailsWithInvalidEmail(): void
    {
        $customerRepo = $this->createMock(CustomerRepositoryInterface::class);
        $eventRepo = $this->createMock(EventRepositoryInterface::class);

        $service = new IngestionService($customerRepo, $eventRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A valid customer email is required.");

        $service->ingest([
            'customer' => ['email' => 'not-an-email'],
            'event' => 'purchase'
        ]);
    }
}
