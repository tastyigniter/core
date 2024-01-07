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

    public function listItems($filter = [])
    {
        return $this->requestRemoteData('items', array_merge(['include' => 'require'], $filter));
    }

    public function getDetail($type, $itemName = [])
    {
        return $this->requestRemoteData("{$type}/detail", ['item' => $itemName]);
    }

    public function getDetails($type, $itemNames = [])
    {
        return $this->requestRemoteData("{$type}/details", ['items' => $itemNames]);
    }

    public function applyItems($itemNames = [], $params = []): Collection
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

    public function getDataset($type)
    {
        return array_get($this->requestRemoteData("dataset/$type"), 'data', []);
    }

    protected function requestRemoteData($uri, $params = [])
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

    protected function prepareRequest($params)
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

    public function listLanguages($filter = [])
    {
        return $this->requestRemoteData('languages', $filter);
    }

    public function applyLanguagePack($locale, $build = null)
    {
        return $this->requestRemoteData('language/apply', [
            'locale' => $locale,
            'build' => $build,
        ]);
    }

    public function downloadLanguagePack($filePath, $fileHash, $params = [])
    {
        return $this->requestRemoteData('language/download', $params, $filePath, $fileHash);
    }
}
