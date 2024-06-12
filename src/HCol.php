<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

/**
 * HCol is a column in an HGrid.
 *
 * @see <a href='http://project-haystack.org/doc/Grids'>Project Haystack</a>
 */
class HCol
{
    public int $index;
    public string $name;
    public HDict $meta;

    /** Package private constructor */
    public function __construct(int $index, string $name, HDict $meta)
    {
        $this->index = $index;
        $this->name = $name;
        $this->meta = $meta;
    }

    /** Return programmatic name of column */
    public function getName(): string
    {
        return $this->name;
    }

    /** Return display name of column which is meta.dis or name */
    public function getDisplayName(): string
    {
        $dis = $this->meta->get('dis', false);
        if ($dis instanceof HStr) {
            return $dis->val;
        }
        return $this->name;
    }

    /** Column meta-data tags */
    public function getMeta(): HDict
    {
        return $this->meta;
    }

    /** Hash code is based on name and meta */
    public function hashCode(): int
    {
        return (hash('crc32', $this->name) << 13) ^ $this->meta->hashCode();
    }

    /** Equality is name and meta */
    public function equals(object $that): bool
    {
        if (!$that instanceof HCol) {
            return false;
        }
        return $this->name === $that->name && $this->meta->equals($that->meta);
    }
}
