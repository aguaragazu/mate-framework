<?php

namespace Mate\Support;

use Carbon\Carbon;
use DateTimeInterface;



class Date
{
  protected $carbonInstance;

  public function __construct(?DateTimeInterface $dateTime = null)
    {
        $this->carbonInstance = new Carbon($dateTime);
    }

    public static function create(DateTimeInterface $dateTime = null): self
    {
        return new self($dateTime);
    }

    public static function now(): self
    {
        return new self();
    }

    public function format(string $format): string
    {
        return $this->carbonInstance->format($format);
    }

    public function diffInYears(DateTimeInterface $endDate): int
    {
        return $this->carbonInstance->diffInYears($endDate);
    }

    public function diffInMonths(DateTimeInterface $endDate): int
    {
        return $this->carbonInstance->diffInMonths($endDate);
    }

    public function diffInDays(DateTimeInterface $endDate): int
    {
        return $this->carbonInstance->diffInDays($endDate);
    }

    public function diffInHours(DateTimeInterface $endDate): int
    {
        return $this->carbonInstance->diffInHours($endDate);
    }

    public function diffInMinutes(DateTimeInterface $endDate): int
    {
        return $this->carbonInstance->diffInMinutes($endDate);
    }

    public function diffInSeconds(DateTimeInterface $endDate): int
    {
        return $this->carbonInstance->diffInSeconds($endDate);
    }
}