<?php declare(strict_types=1);

namespace App\Presentation\Http\Response;

final class HtmlResponder
{
    public function respond(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}
