<?php

namespace ZiffMedia\Laravel\EloquentImagery\Nova;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use ZiffMedia\Laravel\EloquentImagery\Eloquent\Image;
use ZiffMedia\Laravel\EloquentImagery\Eloquent\ImageCollection;

class EloquentImageryField extends Field
{
    public $component = 'eloquent-imagery';

    public $showOnIndex = false;

    protected $thumbnailUrlModeifiers;
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

    protected function updateImage(Image $image, $imageData, $imageMetadata)
    {
        if ($imageData) {
            $image->setData($imageData);
        }

        $image->metadata = new Collection($imageMetadata);
    }

    public function fillUsing($fillCallback)
    {
        return parent::fillUsing($fillCallback); // TODO: Change the autogenerated stub
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
                $value['images'][] = [
                    'previewUrl' => $image->url('v' . $image->timestamp),
                    'path'       => $image->path,
                    'metadata'   => $image->metadata
                ];
            }
        } else {
            $isCollection = false;

            if ($this->value->exists()) {
                $value = [
                    'previewUrl' => $this->value->url('v' . $this->value->timestamp),
                    'path'       => $this->value->path,
                    'metadata'   => $this->value->metadata
                ];
            } else {
                $value = null;
            }
        }

        return array_merge([
            'component'       => $this->component(),
            'prefixComponent' => true,
            'indexName'       => $this->name,
            'name'            => $this->name,
            'attribute'       => $this->attribute,
            'value'           => $value,
            'panel'           => $this->panel,
            'sortable'        => $this->sortable,
            'nullable'        => $this->nullable,
            'readonly'        => $this->isReadonly(app(NovaRequest::class)),
            'textAlign'       => $this->textAlign,
            'isCollection'    => $isCollection
        ], $this->meta());
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


    protected function resolveImageFromFormData(array $formData, Image $image)
    {
        if ($value === null) {
            $image->remove();

            return;
        }

        $this->updateImage($fieldAttribute, $formData['fileData'] ?? null, $formData['metadata']);
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

