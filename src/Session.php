<?php


namespace Tnapf\MysqlSessions;

use DateTime;
use stdClass;
use Tnapf\SessionInterfaces\Session as SessionInterface;

class Session extends SessionInterface {
    private stdClass $data;

    public function __construct(public readonly string $id, public readonly int $expires, ?stdClass $data = null) {
        $this->data = $data ?? new stdClass;
    }

    public function set(string $name, mixed $value): self
    {
        $this->data->$name = $value;

        return $this;
    }

    public function setCookieHeader(string $path = "/", string $domain = "", bool $secure = false, bool $httponly = false): string
    {
        $expiresFormat = (new DateTime())->setTimestamp($this->expires)->format("D, j n Y G:i:s");

        $cookieHeader = sprintf('%s=%s; Expires=%s', "session_id", $this->id, $expiresFormat);

        $empty = ["Path", "Domain"];

        foreach ($empty as $key) {
            $keyValue = ${strtolower($key)};
            if (!empty($keyValue)) {
                $cookieHeader .= sprintf('; %s=%s', $key, $keyValue);
            }
        }

        $true = ["Secure", "HttpOnly"];

        foreach ($true as $key) {
            $keyValue = ${strtolower($key)};
            if ($keyValue) {
                $cookieHeader .= sprintf('; %s', $key);
            }
        }

        return $cookieHeader;
    }

    public function unset(string $name, string ...$names): self
    {
        foreach ([$name, ...$names] as $name) {
            unset($this->data->$name);
        }

        return $this;
    }

    public function get(string $name): mixed
    {
        return $this->data->$name ?? null;
    }

    public function __set(string $name, mixed $value) {
        $this->set($name, $value);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __unset(string $name): void
    {
        $this->unset($name);
    }

    public function __toString(): string
    {
        return json_encode($this->data);
    }
}
