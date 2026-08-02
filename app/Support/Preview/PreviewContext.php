<?php

namespace App\Support\Preview;

class PreviewContext
{
    public function __construct(
        public readonly string $previewType,
        public readonly array $normalizedState,
        public readonly ?array $recordSnapshot,
        public readonly string $mode,
        public readonly array $routeTokenMetadata
    ) {}

    public static function fromPayload(array $payload, array $metadata = []): self
    {
        if (
            !isset($payload['version']) || $payload['version'] !== 1 ||
            !isset($payload['type']) || $payload['type'] !== 'news' ||
            !isset($payload['mode']) || !in_array($payload['mode'], ['create', 'edit'], true) ||
            !isset($payload['state']) || !is_array($payload['state']) ||
            (!array_key_exists('snapshot', $payload) || (!is_array($payload['snapshot']) && !is_null($payload['snapshot']))) ||
            (!array_key_exists('record_id', $payload) || (!is_numeric($payload['record_id']) && !is_null($payload['record_id'])))
        ) {
            abort(404);
        }

        if ($payload['mode'] === 'edit') {
            if ($payload['record_id'] === null) {
                abort(404);
            }
            if (!empty($payload['snapshot']) && isset($payload['snapshot']['id']) && $payload['snapshot']['id'] != $payload['record_id']) {
                abort(404);
            }
        } elseif ($payload['mode'] === 'create') {
            if ($payload['record_id'] !== null || !empty($payload['snapshot'])) {
                abort(404);
            }
        }

        return new self(
            $payload['type'],
            $payload['state'],
            $payload['snapshot'],
            $payload['mode'],
            $metadata
        );
    }
}
