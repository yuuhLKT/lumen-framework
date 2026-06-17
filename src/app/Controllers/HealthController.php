<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class HealthController extends Controller
{
    /** @param array<string, string> $params */
    public function show(Request $request, array $params): Response
    {
        return $this->ok(['status' => 'ok']);
    }
}
