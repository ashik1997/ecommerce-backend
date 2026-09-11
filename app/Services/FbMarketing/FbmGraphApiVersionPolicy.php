<?php

namespace App\Services\FbMarketing;

use InvalidArgumentException;

class FbmGraphApiVersionPolicy
{
    public function defaultVersion(): string
    {
        return $this->normalize((string) config('fb_marketing.graph_api.default_version', 'v25.0')) ?: 'v25.0';
    }

    public function allowedVersions(): array
    {
        $versions = config('fb_marketing.graph_api.allowed_versions', [$this->defaultVersion()]);

        if (!is_array($versions)) {
            $versions = [$this->defaultVersion()];
        }

        $normalized = [];
        foreach ($versions as $version) {
            $version = $this->normalize(is_string($version) ? $version : null);
            if ($version !== null) {
                $normalized[] = $version;
            }
        }

        $normalized[] = $this->defaultVersion();

        return array_values(array_unique($normalized));
    }

    public function normalize(?string $version): ?string
    {
        $version = trim((string) $version);

        if ($version === '') {
            return null;
        }

        if (!preg_match('/^v\d+\.\d+$/', $version)) {
            return null;
        }

        return $version;
    }

    public function resolve(?string $version): string
    {
        return $this->normalize($version) ?: $this->defaultVersion();
    }

    public function isAllowed(?string $version): bool
    {
        $version = $this->normalize($version);

        return $version !== null && in_array($version, $this->allowedVersions(), true);
    }

    public function resolveAllowed(?string $version): string
    {
        $version = $this->resolve($version);

        if (!$this->isAllowed($version)) {
            throw new InvalidArgumentException('The configured Meta Graph API version is not permitted by the application policy.');
        }

        return $version;
    }
}
