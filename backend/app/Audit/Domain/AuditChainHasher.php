<?php

declare(strict_types=1);

namespace App\Audit\Domain;

final class AuditChainHasher
{

    public function compute(
        ?string $prevHash,
        string $uuid,
        string $restaurantUuid,
        string $createdAtIso,
        string $actionSlug,
        string $entityType,
        string $entityId,
        ?string $userUuid,
        string $summary,
        array $metadata,
        ?array $before,
        ?array $after,
    ): string {
        $payload = implode("\n", [
            $prevHash ?? '',
            $uuid,
            $restaurantUuid,
            $createdAtIso,
            $actionSlug,
            $entityType,
            $entityId,
            $userUuid ?? '',
            $summary,
            $this->canonicalJson($metadata),
            $this->canonicalJson($before),
            $this->canonicalJson($after),
        ]);

        return hash('sha256', $payload);
    }

    private function canonicalJson(?array $data): string
    {
        if ($data === null) {
            return 'null';
        }

        return json_encode(
            $this->sortKeysRecursively($data),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    private function sortKeysRecursively(array $arr): array
    {
        if (! array_is_list($arr)) {
            ksort($arr);
        }
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = $this->sortKeysRecursively($v);
            }
        }

        return $arr;
    }
}
