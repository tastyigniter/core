<?php

namespace Igniter\System\Libraries;

use Igniter\System\Models\Country as CountryModel;
use Illuminate\Support\Collection;

/**
 * Country Class
 */
class Country
{
    public const ISO_CODE_2 = 2;

    public const ISO_CODE_3 = 3;

    protected string $defaultFormat = "{address_1}\n{address_2}\n{city} {postcode}\n{state}\n{country}";

    protected array $requiredAddressKeys = [
        'address_1',
        'address_2',
        'city',
        'postcode',
        'state',
        'country',
    ];

    protected ?Collection $countriesCollection = null;

    public function addressFormat(array $address, bool $useLineBreaks = true): string
    {
        $format = $this->getDefaultFormat();

        $address = $this->evalAddress($address);

        // Override format if present in address array
        if (!empty($address['format'])) {
            $format = $address['format'];
        }

        $formattedAddress = str_replace(['\r\n', '\r', '\n'], '<br />',
            preg_replace(['/\s\s+/', '/\r\r+/', '/\n\n+/'], '<br />',
                trim(str_replace([
                    '{address_1}', '{address_2}', '{city}', '{postcode}', '{state}', '{country}',
                ], array_except($address, 'format'), $format))
            )
        );

        if (!$useLineBreaks) {
            $formattedAddress = str_replace('<br />', ', ', $formattedAddress);
        }

        return strip_tags($formattedAddress);
    }

    public function getCountryNameById(null|int|string $id = null): ?string
    {
        $this->loadCountries();

        if (!$countryModel = $this->countriesCollection->find($id)) {
            return null;
        }

        return $countryModel->country_name;
    }

    public function getCountryCodeById(null|int|string $id = null, ?string $codeType = null): ?string
    {
        $this->loadCountries();

        if (!$countryModel = $this->countriesCollection->firstWhere('country_id', $id)) {
            return null;
        }

        return (is_null($codeType) || $codeType == static::ISO_CODE_2)
            ? $countryModel->iso_code_2 : $countryModel->iso_code_3;
    }

    public function getCountryNameByCode(string $isoCodeTwo): ?string
    {
        $this->loadCountries();

        if (!$countryModel = $this->countriesCollection->firstWhere('iso_code_2', $isoCodeTwo)) {
            return null;
        }

        return $countryModel->country_name;
    }

    public function getDefaultFormat(): string
    {
        if ($defaultCountry = CountryModel::getDefault()) {
            return $defaultCountry->format;
        }

        return $this->defaultFormat;
    }

    public function listAll(?string $column = null, string $key = 'country_id'): array
    {
        $this->loadCountries();

        if (is_null($column)) {
            return $this->countriesCollection;
        }

        return $this->countriesCollection->pluck($column, $key);
    }

    protected function evalAddress(array $address): array
    {
        if (isset($address['country_id']) && !isset($address['country'])) {
            $address['country'] = $address['country_id'];
        }

        $result = [];
        foreach ($this->requiredAddressKeys as $key) {
            if ($key == 'country') {
                $this->processCountryValue($address[$key], $result);
            } else {
                $result[$key] = $address[$key] ?? '';
            }
        }

        return $result;
    }

    protected function processCountryValue(int|string|array $country, array &$result)
    {
        if (!is_string($country) && isset($country['country_name'])) {
            $result['country'] = $country['country_name'];
            $result['format'] = $country['format'];
        } elseif (is_numeric($country)) {
            $this->loadCountries();

            if ($countryModel = $this->countriesCollection->find($country)) {
                $result['country'] = $countryModel->country_name;
                $result['format'] = $countryModel->format;
            }
        }
    }

    protected function loadCountries(): Collection
    {
        if (is_null($this->countriesCollection)) {
            $this->countriesCollection = CountryModel::whereIsEnabled()->sorted()->get();
        }

        return $this->countriesCollection;
    }
}
