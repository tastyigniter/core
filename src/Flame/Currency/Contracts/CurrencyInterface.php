<?php

namespace Igniter\Flame\Currency\Contracts;

interface CurrencyInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function getCode(): ?string;

    public function getSymbol(): ?string;

    public function getSymbolPosition(): ?string;

    public function getFormat(): string;

    public function getRate(): ?string;

    public function isEnabled(): bool;

    public function updateRate($rate);
}
