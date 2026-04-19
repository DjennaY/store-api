<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use OpenApi\Generator;

final class DocsController
{
    public function spec(): void
    {
        $openapi = Generator::scan([dirname(__DIR__, 4) . '/src']);

        if ($openapi === null) {
            http_response_code(500);
            echo json_encode(['error' => 'Could not generate OpenAPI specification']);
            exit;
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo $openapi->toJson();
        exit;
    }
}
