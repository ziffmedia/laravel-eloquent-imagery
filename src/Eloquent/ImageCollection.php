<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

/**
 * @mixin Collection
 */
class ImageCollection implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable
{
    use ForwardsCalls;

    protected Image $imagePrototype;

    protected Collection $images;

    protected int $autoincrement = 1;

    protected Collection $metadata;

    protected array $deletedImages = [];

    public function __construct($imagePrototype)
    {
        $this->imagePrototype = $imagePrototype;
        $this->images = new Collection;
        $this->metadata = new Collection;
    }

    public function getImagePrototype(): Image
    {
        return $this->imagePrototype;
    }

    public function getAutoincrement(): int
    {
        return $this->autoincrement;
    }

    public function createImage($imageData): Image
    {
        $image = clone $this->imagePrototype;
        $image->setData($imageData);

        return $image;
    }

    public function getWrappedCollectionForImages(): Collection
    {
        return $this->images;
    }

    public function replaceWrappedCollectionForImages(Collection $images)
    {
        $this->images = $images;
    }

    public function metadata()
    {
        return $this->metadata;
    }

    public function getIterator(): ArrayIterator
    {
        return $this->images->getIterator();
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->images->has($key);
    }

    /**
     * Get the item at the given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->images->get($key);
    }

    /**
     * Set the item at the given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (! $value instanceof Image) {
            $value = $this->createImage($value);
        }

        $this->images->put($key, $value);
    }

    /**
     * Unset the item at the given key.
     *
     * @param  mixed  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->deletedImages[] = $this->images[$key];

        $this->images[$key]->remove();

        $this->images->forget($key);
    }

    /**
     * Get the number of items for the current page.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->images->count();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getStateAsAttributeData();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function setStateFromAttributeData($attributeData)
    {
        $this->autoincrement = $attributeData['autoinc'] ?? $attributeData['autoincrement'] ?? 1;

        // replace collection, as Collection has no method to cleanly empty itself, same with metadata
        $this->images = new Collection;
        $this->metadata = new Collection;

        foreach ($attributeData['images'] as $imageState) {
            $image = clone $this->imagePrototype;
            $image->setStateFromAttributeData($imageState);

            $this->images->push($image);
        }

        if (isset($attributeData['metadata']) && is_iterable($attributeData['metadata'])) {
            foreach ($attributeData['metadata'] as $key => $value) {
                $this->metadata[$key] = $value;
            }
        }
    }

    #[ArrayShape(['autoincrement' => 'int', 'images' => 'mixed', 'metadata' => 'array'])]
    public function getStateAsAttributeData(): array
    {
        $images = $this->images->map(function (Image $image) {
            return $image->getStateAsAttributeData();
        })->toArray();

        return [
            'autoincrement' => $this->autoincrement,
            'images'        => $images,
            'metadata'      => $this->metadata->toArray(),
        ];
    }

    public function pathHasReplacements(): bool
    {
        if ($this->images->count() === 0) {
            return false;
        }

        return $this->images->contains(function ($image) {
            return $image->pathHasReplacements();
        });
    }

    public function purgeRemovedImages()
    {
        foreach ($this->images as $i => $image) {
            if ($image->isFullyRemoved()) {
                $this->deletedImages[] = $image;
                unset($this->images[$i]);
            }
        }
    }

    public function updatePath(array $replacements): void
    {
        $this->images->each(function (Image $image) use ($replacements) {
            if ($image->index === null) {
                $image->setIndex($this->autoincrement++);
            }

            $image->updatePath($replacements);
        });
    }

    /**
     * Called to remove all the images from this collection, generally in a workflow to remove an entire entity
     */
    public function remove(): void
    {
        $this->images = $this->images->filter(function (Image $image) {
            $this->deletedImages[] = $image;
            $image->remove();

            return false; // returning false will remove from new collection
        });
    }

    public function requiresFlush(): bool
    {
        if ($this->deletedImages) {
            return true;
        }

        $image = $this->images->first(fn (Image $image) => $image->requiresFlush());

        return $image instanceof Image;
    }

    public function flush()
    {
        foreach ($this->deletedImages as $image) {
            $image->flush();
        }

        $this->images->each(function (Image $image) {
            $image->flush();
        });
    }

    public function resetToFreshState()
    {
        $this->images->each(function (Image $image) {
            $image->resetToFreshState();
        });
    }

    public function exists(): bool
    {
        return true;
    }

    /**
     * Make dynamic calls into the collection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->getWrappedCollectionForImages(), $method, $parameters);
    }

    public function __clone()
    {
        $this->images = clone $this->images;
        $this->metadata = clone $this->metadata;
    }
}
