<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Hub Manager Class
 */
class HubManager
{
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

        return collect(array_get($response, 'data', []))->map(function($package) use ($itemNames) {
            $package['installedVersion'] = $package['type'] == 'core'
                ? Igniter::version()
                : array_get($itemNames->firstWhere('name', $package['code']), 'ver', '0.0.0');

            return PackageInfo::fromArray($package);
        });
    }

    public function setCarte(string $key, ?array $info = null): void
    {
        $params['carte_key'] = $key;

        if (!is_null($info)) {
            $params['carte_info'] = $info;
        }

        setting()->setPref($params);
    }

    protected function requestRemoteData(string $uri, array $params = [], ?string $eTag = null): array
    {
        throw_unless($endpoint = config('igniter-system.updatesEndpoint'), new SystemException(
            'Updates endpoint not configured'
        ));

        $client = Http::baseUrl($endpoint);

        $response = $client->acceptJson()
            ->withHeaders($this->prepareHeaders($params))
            ->post($uri, $this->prepareRequest($params));

        if (!$response->ok()) {
            if ($errors = $response->json('errors')) {
                logger()->debug('Server validation errors: '.print_r($errors, true));
            }

            throw new SystemException($response->json('message'));
        }

        throw_if(
            $eTag && $response->header('TI-ETag') !== $eTag,
            new SystemException('ETag mismatch, please try again.')
        );

        return $response->json();
    }

    protected function prepareRequest(array $params): array
    {
        $params['client'] = 'tastyigniter';
        $params['server'] = base64_encode(serialize([
            'php' => PHP_VERSION,
            'url' => url()->to('/'),
            'version' => Igniter::version(),
            'host' => gethostname() ?: 'unknown',
        ]));

        if (config('igniter-system.edgeUpdates', false)) {
            $params['edge'] = 1;
        }

        return $params;
    }

    protected function prepareHeaders(array $params): array
    {
        $headers = [];
        $siteKey = config('igniter-system.carteKey') ?: params('carte_key', '');
        if ($siteKey) {
            $headers['Authorization'] = 'Bearer '.$siteKey;
        }

        if (!app()->runningInConsole()) {
            $headers['X-Igniter-Host'] = gethostname() ?: 'unknown';
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

    public function getLanguage(string $locale): array
    {
        return $this->requestRemoteData('language/'.$locale);
    }

    public function applyLanguagePack(string $locale, ?array $items = null): array
    {
        return $this->requestRemoteData('language/apply', [
            'locale' => $locale,
            'items' => $items,
        ]);
    }

    public function downloadLanguagePack(string $eTag, array $params = []): array
    {
        return $this->requestRemoteData('language/download', $params, $eTag);
    }

    public function publishTranslations(string $locale, array $packs = []): array
    {
        return $this->requestRemoteData('language/upload', [
            'locale' => $locale,
            'packs' => $packs,
        ]);
    }
}
