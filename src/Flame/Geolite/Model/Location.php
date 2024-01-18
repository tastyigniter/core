<?php

namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Formatter\StringFormatter;
use InvalidArgumentException;

class Location implements Contracts\LocationInterface
{
    protected ?Coordinates $coordinates = null;

    protected ?Bounds $bounds = null;

    protected string|int|null $streetNumber = null;

    protected ?string $streetName = null;

    protected ?string $subLocality = null;

    protected ?string $locality = null;

    protected ?string $postalCode = null;

    protected ?AdminLevelCollection $adminLevels = null;

    protected ?string $countryName = null;

    protected ?string $countryCode = null;

    protected ?string $formattedAddress = null;

    protected ?string $timezone = null;

    protected string $providedBy;

    protected array $data;

    public function __construct(string $providedBy, array $data = [])
    {
        $this->providedBy = $providedBy;
        $this->fillFromData($data);
    }

    /**
     * Create an Address with an array.
     */
    public static function createFromArray(array $data): static
    {
        return new static(array_get($data, 'providedBy', 'n/a'), $data);
    }

    public function isValid(): bool
    {
        return $this->hasCoordinates();
    }

    public function format(string $mapping = '%n %S %L %z'): string
    {
        return (new StringFormatter)->format($this, $mapping);
    }

    public function getFormattedAddress(): ?string
    {
        return $this->formattedAddress;
    }

    public function withFormattedAddress(?string $formattedAddress = null): self
    {
        $new = clone $this;
        $new->formattedAddress = $formattedAddress;

        return $new;
    }

    public function setBounds(?float $south, ?float $west, ?float $north, ?float $east): self
    {
        try {
            $this->bounds = new Bounds($south, $west, $north, $east);
        } catch (InvalidArgumentException $e) {
            $this->bounds = null;
        }

        return $this;
    }

    public function setCoordinates(float $latitude, float $longitude): self
    {
        try {
            $this->coordinates = new Coordinates($latitude, $longitude);
        } catch (InvalidArgumentException $e) {
            $this->coordinates = null;
        }

        return $this;
    }

    public function addAdminLevel(int $level, string $name, ?string $code = null): self
    {
        $this->adminLevels->put($level, new AdminLevel($level, $name, $code));

        return $this;
    }

    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    public function setStreetName(?string $streetName): self
    {
        $this->streetName = $streetName;

        return $this;
    }

    public function setLocality(?string $locality): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function setSubLocality(?string $subLocality): self
    {
        $this->subLocality = $subLocality;

        return $this;
    }

    public function setAdminLevels(?AdminLevelCollection $adminLevels): self
    {
        $this->adminLevels = $adminLevels;

        return $this;
    }

    public function setCountryName(?string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function setValue(string $name, mixed $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function getValue(string $name, mixed $default = null): mixed
    {
        if ($this->hasValue($name)) {
            return $this->data[$name];
        }

        return $default;
    }

    public function hasValue(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function getProvidedBy(): string
    {
        return $this->providedBy;
    }

    public function getCoordinates(): ?Coordinates
    {
        return $this->coordinates;
    }

    public function getBounds(): ?Bounds
    {
        return $this->bounds;
    }

    public function getStreetNumber(): int|string|null
    {
        return $this->streetNumber;
    }

    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getSubLocality(): ?string
    {
        return $this->subLocality;
    }

    public function getAdminLevels(): AdminLevelCollection
    {
        return $this->adminLevels;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function hasCoordinates(): bool
    {
        if (!$coordinates = $this->getCoordinates()) {
            return false;
        }

        [$latitude, $longitude] = $coordinates->toArray();

        return !empty($latitude) && !empty($longitude);
    }

    public function toArray(): array
    {
        $adminLevels = [];
        foreach ($this->adminLevels as $adminLevel) {
            $level = $adminLevel->getLevel();
            $adminLevels[$level] = [
                'name' => $adminLevel->getName(),
                'code' => $adminLevel->getCode(),
                'level' => $level,
            ];
        }

        $coordinates = $this->getCoordinates();

        $noBounds = [
            'south' => null, 'west' => null,
            'north' => null, 'east' => null,
        ];

        return [
            'providedBy' => $this->providedBy,
            'latitude' => $coordinates ? $coordinates->getLatitude() : null,
            'longitude' => $coordinates ? $coordinates->getLongitude() : null,
            'bounds' => $this->bounds ? $this->bounds->toArray() : $noBounds,
            'streetNumber' => $this->streetNumber,
            'streetName' => $this->streetName,
            'postalCode' => $this->postalCode,
            'locality' => $this->locality,
            'subLocality' => $this->subLocality,
            'adminLevels' => $adminLevels,
            'countryName' => $this->getCountryName(),
            'countryCode' => $this->getCountryCode(),
            'timezone' => $this->timezone,
        ];
    }

    protected function fillFromData(array $data)
    {
        $this->data = $data = $this->mergeWithDefaults($data);

        $this->adminLevels = $this->makeAdminLevels($data);
        $this->coordinates = $this->createCoordinates($data);
        $this->bounds = $this->createBounds($data);
        $this->streetNumber = $data['streetNumber'];
        $this->streetName = $data['streetName'];
        $this->postalCode = $data['postalCode'];
        $this->locality = $data['locality'];
        $this->subLocality = $data['subLocality'];
        $this->countryName = $data['countryName'];
        $this->countryCode = $data['countryCode'];
        $this->timezone = $data['timezone'];
        $this->formattedAddress = $data['formattedAddress'];
    }

    protected function createCoordinates(array $data): ?Coordinates
    {
        if (
            !($latitude = array_get($data, 'latitude'))
            || !($longitude = array_get($data, 'longitude'))
        ) {
            return null;
        }

        return new Coordinates($latitude, $longitude);
    }

    protected function createBounds(array $data): ?Bounds
    {
        if (!($south = array_get($data, 'bounds.south'))
            || !($west = array_get($data, 'bounds.west'))
            || !($north = array_get($data, 'bounds.north'))
            || !($east = array_get($data, 'bounds.east'))
        ) {
            return null;
        }

        return new Bounds($south, $west, $north, $east);
    }

    protected function mergeWithDefaults(array $data): array
    {
        $defaults = [
            'latitude' => null,
            'longitude' => null,
            'bounds' => [
                'south' => null,
                'west' => null,
                'north' => null,
                'east' => null,
            ],
            'streetNumber' => null,
            'streetName' => null,
            'locality' => null,
            'postalCode' => null,
            'subLocality' => null,
            'adminLevels' => [],
            'countryName' => null,
            'countryCode' => null,
            'timezone' => null,
            'formattedAddress' => null,
        ];

        return array_merge($defaults, $data);
    }

    protected function makeAdminLevels(array $data): AdminLevelCollection
    {
        $adminLevels = [];
        foreach ($data['adminLevels'] as $adminLevel) {
            if (empty($adminLevel['level'])) {
                continue;
            }

            $name = $adminLevel['name'] ?? $adminLevel['code'] ?? null;
            if (empty($name)) {
                continue;
            }

            $adminLevels[] = new AdminLevel($adminLevel['level'], $name, $adminLevel['code'] ?? null);
        }

        return new AdminLevelCollection($adminLevels);
    }
}
