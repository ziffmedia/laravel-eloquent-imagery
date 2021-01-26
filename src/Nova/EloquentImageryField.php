<?php

namespace ZiffMedia\LaravelEloquentImagery\Nova;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\Eloquent\ImageCollection;

class EloquentImageryField extends Field
{
    public $component = 'eloquent-imagery';

    public $showOnIndex = false;

    protected $thumbnailUrlModifiers;
    protected $previewUrlModifiers;

    protected $validationRules = [];

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if (!$request->exists($requestAttribute)) {
            return;
        }

        $value = json_decode($request[$requestAttribute], true);

        /** @var Image|ImageCollection $fieldAttribute */
        $fieldAttribute = $model->{$attribute};

        if (!$fieldAttribute instanceof Image && !$fieldAttribute instanceof ImageCollection) {
            throw new \RuntimeException('Field must be an EloquentImagery field');
        }

        ($fieldAttribute instanceof ImageCollection)
            ? $this->resolveImageCollectionFromFormData($value, $fieldAttribute)
            : $this->resolveImageFromFormData($value, $fieldAttribute);

        $fieldAttribute->updatePath([], $model);
    }


    public function jsonSerialize()
    {
        if ($this->value instanceof ImageCollection) {
            $isCollection = true;

            $value = [
                'autoincrement' => $this->value->getAutoincrement(),
                'images'        => []
            ];

            foreach ($this->value as $image) {
                $value['images'][] = $this->jsonSerializeImage($image);
            }
        } else {
            $isCollection = false;

            $value = ($this->value->exists()) ? $this->jsonSerializeImage($this->value) : null;
        }

        return array_merge(parent::jsonSerialize(), [
            'value'           => $value,
            'isCollection'    => $isCollection,
            'validationRules' => $this->validationRules
        ]);
    }

    protected function jsonSerializeImage(Image $image)
    {
        return [
            'previewUrl' => $image->url(
                ($this->previewUrlModifiers ? $this->previewUrlModifiers . '|' : '')
                . 'v' . $image->timestamp
            ),
            'thumbnailUrl' => $image->url(
                ($this->thumbnailUrlModifiers ? $this->thumbnailUrlModifiers . '|' : '')
                . 'v' . $image->timestamp
            ),
            'path'       => $image->path,
            'metadata'   => $image->metadata
        ];
    }

    /**
     * @param $previewUrlModifiers
     * @return $this
     */
    public function previewUrlModifiers($previewUrlModifiers)
    {
        $this->previewUrlModifiers = $previewUrlModifiers;

        return $this;
    }

    /**
     * @param $thumbnailUrlModifiers
     * @return $this
     */
    public function thumbnailUrlModifiers($thumbnailUrlModifiers)
    {
        $this->thumbnailUrlModifiers = $thumbnailUrlModifiers;

        return $this;
    }

    public function withMaximumSizeSoftLimit($limit)
    {
        $this->validationRules['size_limit'] = array_merge(
            $this->validationRules['size_limit'] ?? [],
            ['soft_limit' => $this->parseSize($limit)]
        );

        return $this;
    }

    public function withMaximumSizeHardLimit($limit, $ui = 'modal')
    {
        throw_if(!in_array($ui, ['modal', 'error']), '$ui must be either "modal" or "error"');

        $this->validationRules['size_limit'] = array_merge(
            $this->validationRules['size_limit'] ?? [],
            ['hard_limit' => $this->parseSize($limit), 'hard_limit_ui' => $ui]
        );

        return $this;
    }

    public function withOnlySpecificTypes($types, $ui = 'modal')
    {
        throw_if(!in_array($ui, ['modal', 'error']), '$ui must be either "modal" or "error"');

        $this->validationRules['type_limit'] = [
            'types' => is_array($types) ? $types : explode(',', $types),
            'ui' => $ui
        ];

        return $this;
    }

    protected function resolveImageFromFormData($formData, Image $image)
    {
        if ($formData === null) {

            if ($image->exists()) {
                $image->remove();
            }

            return;
        }

        if ($formData['fileData']) {
            $image->setData($formData['fileData']);
        }

        $image->metadata = new Collection($formData['metadata']);
    }

    protected function resolveImageCollectionFromFormData(array $formData, ImageCollection $imageCollection)
    {
        // create a collection of mapped path=>image of the existing images
        $existingImages = $imageCollection->mapWithKeys(function ($image, $index) {
            return [$image->path => ['image' => $image, 'original_index' => $index]];
        });

        $newCollectionForImages = new Collection;

        // iterate over provided value from form, start creating an array of images for the new ImageCollection
        foreach ($formData as $imageIndex => $imageData) {
            if ($imageData['path']) {
                $image = $existingImages[$imageData['path']]['image'];
                unset($existingImages[$imageData['path']]);
            } else {
                $image = $imageCollection->createImage($imageData['fileData']);
            }

            // if bytes were provided, set them
            if (isset($imageData['fileData'])) {
                $image->setData($imageData['fileData']);
            }

            // store the metadata
            $image->metadata = new Collection($imageData['metadata']);

            $newCollectionForImages[$imageIndex] = $image;
        }

        // what is left over needs to be removed from the original attribute
        foreach ($existingImages as $leftOverImages) {
            unset($imageCollection[$leftOverImages['original_index']]);
        }

        // finally replace the image collection's interal/wrapped collection
        $imageCollection->replaceWrappedCollectionForImages($newCollectionForImages);
    }

    protected function parseSize($size)
    {
        if (is_int($size)) {
            return $size;
        }

        // allow configured sizes to be any of the following KB, Kb, kb, k (all resolve to k, w/ or w/o b)
        [$sizeInt, $unit] = [(int) $size, substr(rtrim(strtolower($size), 'b'), -1)];

        switch ($unit) {
            case 'g': return $sizeInt * 1073741824;
            case 'm': return $sizeInt * 1048576;
            case 'k': return $sizeInt * 1024;

            default:
                if (!is_numeric($unit)) {
                    throw new InvalidArgumentException("$size was provided but is not a valid configuration value in " . __CLASS__);
                }
        }

        return $sizeInt;
    }
}

