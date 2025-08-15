<?php

declare(strict_types=1);

namespace Hypervel\Support;

use Hypervel\Support\Contracts\Htmlable;
use Stringable;

class HtmlString implements Htmlable, Stringable
{
    /**
     * The HTML string.
     */
    protected string $html;

    /**
     * Create a new HTML string instance.
     */
    public function __construct(string $html = '')
    {
        $this->html = $html;
    }

    /**
     * Get the HTML string.
     */
    public function toHtml(): string
    {
        return $this->html;
    }

    /**
     * Determine if the given HTML string is empty.
     */
    public function isEmpty(): bool
    {
        return $this->html === '';
    }

    /**
     * Determine if the given HTML string is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get the HTML string.
     */
    public function __toString(): string
    {
        return $this->toHtml() ?? '';
    }
}
