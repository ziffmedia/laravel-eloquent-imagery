<?php

namespace ZiffMedia\LaravelEloquentImagery\Nova;

use Illuminate\Support\Collection;
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

        if ($fieldAttribute instanceof ImageCollection) {
            $this->resolveImageCollectionFromFormData($value, $fieldAttribute);

            return;
        }

        $this->resolveImageFromFormData($value, $fieldAttribute);
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
            'value'        => $value,
            'maximumSize'  => $this->maxiumSize ?? null,
            'isCollection' => $isCollection
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

    /**
     * @param  integer|string $maximumSize
     * @return $this
     */
    public function withMaximumSize($maximumSize)
    {
        $unit = strtolower(substr($maximumSize,-2));
        $measure = (integer) $maximumSize;

        switch(true) {
            case ($unit == 'kb'):
                $this->maxiumSize = $measure * 1000;

                break;
            case ($unit == 'mb'):
                $this->maxiumSize = $measure * 1000000;

                break;
            default:
                $this->maxiumSize = $measure;
        }

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
}

