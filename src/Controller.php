<?php

namespace Tnapf\MysqlSessions;

use CommandString\Utils\GeneratorUtils;
use PDO;
use stdClass;
use Tnapf\SessionInterfaces\Exceptions\SessionDoesNotExist;
use Tnapf\SessionInterfaces\Controller as ControllerInterface;
use Tnapf\SessionInterfaces\Session as SessionInterface;

class Controller implements ControllerInterface {
    /**
     * @var stdClass
     */
    private stdClass $sessions;

    public function __construct(private readonly PDO $driver) {
        $this->sessions = new stdClass;

        $this->sessions->existing = $this->sessions->new = [];
    }

    /**
     * @param integer|null $expiresTimestamp a timestamp (in seconds) of when the session should expire; Default: 1 week
     * @return Session
     */
    public function create(?int $expiresTimestamp = null): Session
    {
        while (!isset($id)) {
            $id = GeneratorUtils::uuid();

            $stmt = $this->driver->prepare("SELECT id FROM sessions WHERE id = :id");
            $stmt->execute(compact('id'));

            if ($stmt->rowCount()) {
                unset($id);
            }
        }

        $expires = $expiresTimestamp ?? time() + 604800;

        $session = new Session($id, $expires);

        $this->sessions->new[] = &$session;

        return $session;
    }

    public function get(string $id): Session
    {
        $id = self::filterId($id);

        if (isset($this->sessions->existing[$id]) || isset($this->sessions->new[$id])) {
            return $this->sessions->existing[$id] ?? $this->sessions->new[$id];
        }

        $stmt = $this->driver->prepare("SELECT * FROM sessions WHERE id = :id");
        $stmt->execute(compact('id'));

        if (!$stmt->rowCount()) {
            throw new SessionDoesNotExist($id);
        }

        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if ($row->expires < time()) {
            $this->delete($id);
            throw new SessionDoesNotExist($id);
        }

        $session = new Session($id, $row->expires, json_decode($row->data));

        $this->sessions->existing[] = &$session;

        return $session;
    }

    public function delete(SessionInterface|string $session): void
    {
        if ($session instanceof SessionInterface) {
            $session = $session->id;
        }

        $stmt = $this->driver->prepare("DELETE FROM sessions WHERE id = :id");
        $stmt->execute(["id" => $session]);
    }

    public function __destruct() {
        foreach ($this->sessions->new as $session) {
            $stmt = $this->driver->prepare("INSERT INTO sessions (id, data, expires) VALUES (:id, :data, :expires)");

            $stmt->execute([
                "id" => $session->id,
                "data" => (string)$session,
                "expires" => $session->expires
            ]);
        }

        foreach ($this->sessions->existing as $session) {
            $stmt = $this->driver->prepare("UPDATE sessions SET data = :data, expires = :expires WHERE id = :id");

            $stmt->execute([
                "id" => $session->id,
                "data" => (string)$session,
                "expires" => $session->expires
            ]);
        }
    }

    private static function filterId($id): string
    {
        return preg_replace("/[^a-zA-Z0-9]/", "", $id);
    }
}
