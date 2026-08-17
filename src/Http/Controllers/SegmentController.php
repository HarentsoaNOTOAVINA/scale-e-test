<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\SegmentationEngine;
use InvalidArgumentException;
use Exception;

class SegmentController
{
    private SegmentationEngine $engine;

    public function __construct(?SegmentationEngine $engine = null)
    {
        $this->engine = $engine ?? new SegmentationEngine();
    }

    public function query(Request $request): Response
    {
        $body = $request->getBody();
        if (!is_array($body)) {
            return Response::error("Invalid JSON body payload.", 400);
        }

        try {
            $result = $this->engine->query($body);
            return Response::json($result, 200);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Exception $e) {
            return Response::error("Segmentation query error: " . $e->getMessage(), 500);
        }
    }
}
