<?php

namespace Mate\Support;


class Stringable
{
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public static function create(string $string): self
    {
        return new self($string);
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function upper(): self
    {
        $this->string = strtoupper($this->string);
        return $this;
    }

    public function lower(): self
    {
        $this->string = strtolower($this->string);
        return $this;
    }

    public function length(): int
    {
        return strlen($this->string);
    }

    public function trim(string $charlist = null): self
    {
        $this->string = trim($this->string, $charlist);
        return $this;
    }

    public function ltrim(string $charlist = null): self
    {
        $this->string = ltrim($this->string, $charlist);
        return $this;
    }

    public function rtrim(string $charlist = null): self
    {
        $this->string = rtrim($this->string, $charlist);
        return $this;
    }

    public function substring(int $start, int $length = null): self
    {
        $this->string = substr($this->string, $start, $length);
        return $this;
    }

    public function replace(string $from, string $to): self
    {
        $this->string = str_replace($from, $to, $this->string);
        return $this;
    }

    public function replaceFirst(string $search, string $replace): self
    {
      return new static(Str::replaceFirst($search, $replace, $this->string));
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param  string  $separator
     * @param  string|null  $language
     * @param  array<string, string>  $dictionary
     * @return static
     */
    public function slug($separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
    {
        return new static(Str::slug($this->string, $separator, $language, $dictionary));
    }

    public function contains(string $subString, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return strpos($this->string, $subString) !== false;
        }

        return stripos($this->string, $subString) !== false;
    }

    public function startsWith(string $prefix): bool
    {
        return strpos($this->string, $prefix) === 0;
    }

    public function endsWith(string $suffix): bool
    {
        $strlen = strlen($this->string);
        $suffixLen = strlen($suffix);

        if ($suffixLen === 0) {
            return true;
        }

        return $strlen >= $suffixLen && substr($this->string, $strlen - $suffixLen) === $suffix;
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string  $delimiter
     * @return static
     */
    public function snake($delimiter = '_')
    {
        return new static(Str::snake($this->string, $delimiter));
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string  $cap
     * @return static
     */
    public function finish($cap)
    {
        return new static(Str::finish($this->string, $cap));
    }

    /**
     * Append the given values to the string.
     *
     * @param  array|string  ...$values
     * @return static
     */
    public function append(...$values)
    {
        return new static($this->string.implode('', $values));
    }

}