<?php
class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function get(): array|null
    {
        if (!isset($_SESSION['flash'])) return null;
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    public static function has(): bool
    {
        return isset($_SESSION['flash']);
    }
}
