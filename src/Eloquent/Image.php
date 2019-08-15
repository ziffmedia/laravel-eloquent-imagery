<?php

namespace ZiffMedia\Laravel\EloquentImagery\Eloquent;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use finfo;
use OutOfBoundsException;
use RuntimeException;

/**
 * @property-read string $path
 * @property-read Collection $metadata
 */
class Image implements \JsonSerializable
{
    /** @var Filesystem|Cloud */
    protected static $filesystem = null;

    /** @var string */
    protected $pathTemplate = null;

    protected $path = '';
    protected $extension = '';
    protected $width = null;
    protected $height = null;
    protected $hash = '';
    protected $timestamp = 0;
    /** @var Collection */
    protected $metadata = null;

    protected $exists = false;
    protected $flush = false;
    protected $data = null;
    protected $removeAtPathOnFlush = null;

    public function __construct($pathTemplate)
    {
        if (!static::$filesystem) {
            static::$filesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.filesystem', config('filesystems.default')));
        }

        $this->pathTemplate = $pathTemplate;
        $this->metadata = new Collection;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function url($modifiers = null)
    {
        $renderRouteEnabled = config('eloquent-imagery.render.enable');

        if ($renderRouteEnabled === false && $modifiers) {
            throw new RuntimeException('Cannot process render options unless the rendering route is enabled');
        }

        if ($renderRouteEnabled === false && static::$filesystem instanceof Cloud) {
            return static::$filesystem->url($this->path);
        }

        if ($modifiers) {
            $modifierParts = explode('|', $modifiers);
            sort($modifierParts);
            $modifiers = implode('.', $modifierParts);
            $modifiers = str_replace(':', '_', $modifiers);
        }

        // keyed with [dirname, filename, basename, extension]
        $pathinfo = pathinfo($this->path);

        $pathWithModifiers =
            (($pathinfo['dirname'] !== '.') ? "{$pathinfo['dirname']}/" : '')
            . $pathinfo['filename']
            . ($modifiers ? ".{$modifiers}" : '')
            . ".{$pathinfo['extension']}";

        if ($this->flush === true) {
            return '';
        }

        return url()->route('eloquent-imagery.render', $pathWithModifiers);
    }

    public function setStateFromAttributeData($attributeData)
    {
        $this->path = $attributeData['path'];
        $this->extension = $attributeData['extension'];
        $this->width = $attributeData['width'];
        $this->height = $attributeData['height'];
        $this->hash = $attributeData['hash'];
        $this->timestamp = $attributeData['timestamp'];

        $this->metadata = new Collection($attributeData['metadata']);

        $this->exists = true;
    }

    public function getStateAsAttributeData()
    {
        return [
            'path'      => $this->path,
            'extension' => $this->extension,
            'width'     => $this->width,
            'height'    => $this->height,
            'hash'      => $this->hash,
            'timestamp' => $this->timestamp,
            'metadata'  => $this->metadata->toArray()
        ];
    }

    public function setData($data)
    {
        if ($this->path && static::$filesystem->exists($this->path)) {
            $this->removeAtPathOnFlush = $this->path;
        }

        static $fInfo = null;
        if (!$fInfo) {
            $fInfo = new finfo;
        }

        if ($data instanceof UploadedFile) {
            $data = file_get_contents($data->getRealPath());
        }

        if (strpos($data, 'data:') === 0) {
            $data = file_get_contents($data);
        }

        list ($width, $height) = getimagesizefromstring($data);

        $mimeType = $fInfo->buffer($data, FILEINFO_MIME_TYPE);
        if (!$mimeType) {
            throw new RuntimeException('Mime type could not be discovered');
        }

        $this->path = $this->pathTemplate;
        $this->exists = true;
        $this->flush = true;
        $this->data = $data;
        $this->width = $width;
        $this->height = $height;
        $this->timestamp = Carbon::now()->unix();
        $this->hash = md5($data);

        switch ($mimeType) {
            case 'image/jpeg':
                $this->extension = 'jpg';
                break;
            case 'image/png':
                $this->extension = 'png';
                break;
            case 'image/gif':
                $this->extension = 'gif';
                break;
            default:
                throw new RuntimeException('Unsupported mime-type for expected image: ' . $mimeType);
        }
    }

    public function metadata()
    {
        return $this->metadata;
    }

    public function updatePath(array $replacements, Model $model)
    {
        $path = $this->path;

        $updatedPathParts = [];

        $pathReplacements = [];
        preg_match_all('#{(\w+)}#', $path, $pathReplacements);

        foreach ($pathReplacements[1] as $pathReplacement) {
            if (in_array($pathReplacement, ['extension', 'width', 'height', 'hash', 'timestamp'])) {
                $path = str_replace("{{$pathReplacement}}", $this->{$pathReplacement}, $path);
                $updatedPathParts[] = $pathReplacement;
                continue;
            }

            if ($replacements && isset($replacements[$pathReplacement]) && $replacements[$pathReplacement] != '') {
                $path = str_replace("{{$pathReplacement}}", $replacements[$pathReplacement], $path);
                $updatedPathParts[] = $pathReplacement;
                continue;
            }

            if ($model && $model->offsetExists($pathReplacement) && $model->offsetGet($pathReplacement) != '') {
                $path = str_replace("{{$pathReplacement}}", $model->offsetGet($pathReplacement), $path);
                $updatedPathParts[] = $pathReplacement;
                continue;
            }
        }

        $this->path = $path;

        return $updatedPathParts;
    }

    public function pathHasReplacements()
    {
        return (bool) preg_match('#{(\w+)}#', $this->path);
    }

    public function isFullyRemoved()
    {
        return ($this->flush === true && $this->removeAtPathOnFlush !== '' && $this->path === '');
    }

    public function remove()
    {
        if ($this->path == '') {
            throw new RuntimeException('Called remove on an image that has no path');
        }
        $this->exists = false;
        $this->flush = true;
        $this->removeAtPathOnFlush = $this->path;

        $this->path = '';
        $this->extension = '';
        $this->width = null;
        $this->height = null;
        $this->hash = '';
        $this->timestamp = 0;
        $this->metadata = new Collection;
    }

    public function flush()
    {
        if (!$this->flush) {
            return;
        }

        if ($this->removeAtPathOnFlush) {
            static::$filesystem->delete($this->removeAtPathOnFlush);
        }

        if ($this->data) {
            if ($this->pathHasReplacements()) {
                throw new RuntimeException('The image path still has an unresolved replacement in it ("{...}") and cannot be saved: ' . $this->path);
            }
            static::$filesystem->put($this->path, $this->data);
        }

        $this->flush = false;
    }

    public function __get($name)
    {
        if ($name === 'metadata') {
            return $this->metadata;
        }

        $properties = $this->toArray();

        if (!array_key_exists($name, $properties)) {
            throw new OutOfBoundsException("Property $name is not accessible");
        }

        return $properties[$name];
    }

    public function toArray()
    {
        return $this->getStateAsAttributeData();
    }

    public function jsonSerialize()
    {
        if ($this->exists) {
            return [
                'previewUrl' => $this->url('v' . $this->timestamp),
                'metadata'   => $this->metadata
            ];
        }

        return null;
    }

    public function __clone()
    {
        $this->metadata = clone $this->metadata;
    }
}