<?php

namespace Aerys\Session;

use Amp\Promise;
use function Amp\call;

class RedisPublishDriver extends RedisDriver {
    public function regenerate(string $oldId): Promise {
        return call(function () use ($oldId) {
            try {
                $newId = yield parent::regenerate($oldId);
                yield $this->getClient()->publish("sess:regenerate", "{$oldId} {$newId}");
            } catch (\Throwable $error) {
                throw new SessionException("Failed to publish regeneration", 0, $error);
            }

            return $newId;
        });
    }

    public function save(string $id, $data, int $ttl): Promise {
        return call(function () use ($id, $data, $ttl) {
            try {
                yield parent::save($id, $data, $ttl);

                $data = \addslashes(\serialize($data));
                yield $this->getClient()->publish("sess:update", "{$id} {$data}");
            } catch (\Throwable $error) {
                throw new SessionException("Failed to publish update", 0, $error);
            }
        });
    }
}
