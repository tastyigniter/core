<?php

declare(strict_types=1);

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\PlaceInterface;
use Illuminate\Contracts\Support\Arrayable;

class Place implements Arrayable, PlaceInterface
{
    protected ?string $placeId = null;

    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $provider = null;

    protected array $data = [];

    public function placeId(string $placeId): PlaceInterface
    {
        $this->placeId = $placeId;

        return $this;
    }

    public function title(string $title): PlaceInterface
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): PlaceInterface
    {
        $this->description = $description;

        return $this;
    }

    public function provider(string $provider): PlaceInterface
    {
        $this->provider = $provider;

        return $this;
    }

    public function withData(string $name, mixed $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function getPlaceId(): string
    {
        return $this->placeId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getData(string $name, mixed $default = null): mixed
    {
        if (!array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->data[$name];
    }

    public function toArray(): array
    {
        return [
            'placeId' => $this->getPlaceId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'provider' => $this->getProvider(),
            'data' => $this->data,
        ];
    }
}
