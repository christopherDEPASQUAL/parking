<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Web;

final class AuthController
{
    public function login(): void
    {
        $this->render('Login', '<form method="post"><input name="email" placeholder="Email"><input type="password" name="password" placeholder="Password"><button>Login</button></form>');
    }

    public function register(): void
    {
        $this->render('Register', '<form method="post"><input name="first_name" placeholder="First name"><input name="last_name" placeholder="Last name"><input name="email" placeholder="Email"><input type="password" name="password" placeholder="Password"><button>Register</button></form>');
    }

    private function render(string $title, string $body): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title></head><body>';
        echo '<h1>' . htmlspecialchars($title) . '</h1>';
        echo $body;
        echo '</body></html>';
    }
}
