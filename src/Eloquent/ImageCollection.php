<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use JsonSerializable;

/**
 * @mixin Collection
 */
class ImageCollection implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable
{
    use ForwardsCalls;

    /** @var Image */
    protected $imagePrototype;

    /** @var Collection|Image[] */
    protected $images;

    /** @var int */
    protected $autoincrement = 1;

    /** @var Collection */
    protected $metadata;

    protected $deletedImages = [];

    public function __construct($imagePrototype)
    {
        $this->imagePrototype = $imagePrototype;
        $this->images = new Collection;
        $this->metadata = new Collection;
    }

    public function getImagePrototype()
    {
        return $this->imagePrototype;
    }

    public function getAutoincrement()
    {
        return $this->autoincrement;
    }

    public function createImage($imageData)
    {
        $image = clone $this->imagePrototype;
        $image->setData($imageData);
        return $image;
    }

    public function getWrappedCollectionForImages()
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

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->images->getIterator();
    }

    /**
     * Determine if the given item exists.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->images->has($key);
    }

    /**
     * Get the item at the given offset.
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->images->get($key);
    }

    /**
     * Set the item at the given offset.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (!$value instanceof Image) {
            $value = $this->createImage($value);
        }

        $this->images->put($key, $value);
    }

    /**
     * Unset the item at the given key.
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key)
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
    public function count()
    {
        return $this->images->count();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getStateAsAttributeData();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
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

    public function getStateAsAttributeData()
    {
        $images = $this->images->map(function (Image $image) {
            return $image->getStateAsAttributeData();
        })->toArray();

        return [
            'autoincrement' => $this->autoincrement,
            'images'        => $images,
            'metadata'      => $this->metadata->toArray()
        ];
    }

    public function pathHasReplacements()
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

    public function updatePath(array $replacements, Model $model)
    {
        $this->images->each(function (Image $image) use ($replacements, $model) {

            if ($image->index === null) {
                $image->setIndex($this->autoincrement++);
            }

            $updatedPathParts = $image->updatePath($replacements, $model);
        });
    }

    /**
     * Called to remove all the images from this collection, generally in a workflow to remove an entire entity
     */
    public function remove()
    {
        $this->images = $this->images->filter(function (Image $image) {
            $this->deletedImages[] = $image;
            $image->remove();
            return false; // returning false will remove from new collection
        });
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

    public function exists()
    {
        return true;
    }

    /**
     * Make dynamic calls into the collection.
     *
     * @param string $method
     * @param array $parameters
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
