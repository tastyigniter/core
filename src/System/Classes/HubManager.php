<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/**
 * Hub Manager Class
 */
class HubManager
{
    const ENDPOINT = 'https://api.tastyigniter.com/v2';

    public function listItems(array $filter = []): array
    {
        return $this->requestRemoteData('items', array_merge(['include' => 'require'], $filter));
    }

    public function getDetail(string $type, array $itemName = []): array
    {
        return $this->requestRemoteData("$type/detail", ['item' => $itemName]);
    }

    public function getDetails(string $type, array $itemNames = []): array
    {
        return $this->requestRemoteData("$type/details", ['items' => $itemNames]);
    }

    public function applyItems(array $itemNames = [], array $params = []): Collection
    {
        $response = $this->requestRemoteData('core/apply', array_merge($params, [
            'items' => $itemNames,
        ]));

        $itemNames = collect($itemNames);

        return collect(array_get($response, 'data', []))->map(function ($package) use ($itemNames) {
            $package['installedVersion'] = $package['type'] == 'core'
                ? Igniter::version()
                : array_get($itemNames->firstWhere('name', $package['code']), 'ver', '0.0.0');

            return PackageInfo::fromArray($package);
        });
    }

    public function getDataset(string $type): array
    {
        return array_get($this->requestRemoteData("dataset/$type"), 'data', []);
    }

    protected function requestRemoteData(string $uri, array $params = []): array
    {
        $client = Http::baseUrl(Config::get('igniter.system.hubEndpoint', static::ENDPOINT));

        $response = $client->acceptJson()
            ->withHeaders($this->prepareHeaders($params))
            ->post($uri, $this->prepareRequest($params));

        if (!$response->ok()) {
            if ($errors = $response->json('errors')) {
                logger()->debug('Server validation errors: '.print_r($errors, true));
            }

            throw new SystemException($response->json('message'));
        }

        return $response->json();
    }

    protected function prepareRequest(array $params): array
    {
        $params['client'] = 'tastyigniter';
        $params['server'] = base64_encode(serialize([
            'php' => PHP_VERSION,
            'url' => url()->to('/'),
            'version' => Igniter::version(),
        ]));

        if (Config::get('igniter-system.edgeUpdates', false)) {
            $params['edge'] = 1;
        }

        return $params;
    }

    protected function prepareHeaders(array $params): array
    {
        $headers = [];
        if ($siteKey = config('igniter-system.carteKey', params('carte_key', ''))) {
            $headers['TI-Rest-Key'] = 'Bearer '.$siteKey;
        }

        if (!app()->runningInConsole()) {
            $headers['X-Igniter-Host'] = request()->host();
            $headers['X-Igniter-User-Ip'] = request()->ip();
        }

        $headers['X-Igniter-Platform'] = sprintf('php:%s;version:%s;url:%s',
            PHP_VERSION, Igniter::version(), url()->current()
        );

        return $headers;
    }

    //
    // Language Packs
    //

    public function listLanguages(array $filter = []): array
    {
        return $this->requestRemoteData('languages', $filter);
    }

    public function applyLanguagePack(string $locale, ?string $build = null): array
    {
        return $this->requestRemoteData('language/apply', [
            'locale' => $locale,
            'build' => $build,
        ]);
    }

    public function downloadLanguagePack(string $filePath, string $fileHash, array $params = []): array
    {
        return $this->requestRemoteData('language/download', $params, $filePath, $fileHash);
    }
}
