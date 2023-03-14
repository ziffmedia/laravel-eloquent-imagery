<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Carbon\Carbon;
use Closure;
use finfo;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;
use OutOfBoundsException;
use RuntimeException;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\ImageTransformer;
use ZiffMedia\LaravelEloquentImagery\UrlHandler\UrlHandler;

/**
 * @property-read $index
 * @property-read $path,
 * @property-read $extension,
 * @property-read $animated
 * @property-read $width,
 * @property-read $height,
 * @property-read $hash,
 * @property-read $timestamp,
 */
class Image implements JsonSerializable
{
    use Macroable;

    const MIME_TYPE_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/bmp'  => 'bmp',
    ];

    protected static ?Filesystem $filesystem = null;

    protected ?int $index = null;

    protected string $path = '';

    protected string $extension = '';

    protected bool $animated = false;

    protected ?int $width = null;

    protected ?int $height = null;

    protected ?string $hash = null;

    protected ?int $timestamp = 0;

    public Collection $metadata;

    protected bool $exists = false;

    protected bool $flush = false;

    protected ?string $data = null;

    protected ?string $removeAtPathOnFlush = null;

    protected bool $isReadOnly = false;

    public static function onFilesystemWith(Filesystem $filesystem, Closure $callback): mixed
    {
        $previousFilesystem = static::$filesystem;

        try {
            static::$filesystem = $filesystem;

            $return = $callback();
        } finally {
            static::$filesystem = $previousFilesystem;
        }

        return $return;
    }

    public function __construct(
        protected string $pathTemplate,
        protected array $presets = []
    ) {
        // the filesystem should come from the configuration, unless Image is extended and configured statically set with a filesystem
        if (! static::$filesystem) {
            static::$filesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.filesystem', config('filesystems.default')));
        }

        if (! Str::endsWith($this->pathTemplate, '.{extension}')) {
            throw new InvalidArgumentException("{$this->pathTemplate} must end with .{extension}");
        }

        $this->metadata = new Collection;
    }

    public function setIndex($index): void
    {
        $this->index = $index;
    }

    public function setReadOnly(): void
    {
        $this->isReadOnly = true;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function url($transformations = null): string
    {
        $renderRouteEnabled = config('eloquent-imagery.render.enable');

        if ($renderRouteEnabled === false && $transformations) {
            throw new RuntimeException('Cannot process render transformation options unless the rendering route is enabled');
        }

        if ($renderRouteEnabled === false && static::$filesystem instanceof Cloud) {
            return static::$filesystem->url($this->path);
        }

        $globalPresets = config('eloquent-imagery.urls.presets');

        $transformations = $this->presets[$transformations]
            ?? $globalPresets[$transformations]
            ?? $transformations;

        return app(UrlHandler::class)->createUrl($this, $transformations);
    }

    public function setStateFromAttributeData($attributeData): void
    {
        $this->index = $attributeData['index'] ?? null;
        $this->path = $attributeData['path'] ?? null;
        $this->extension = $attributeData['extension'] ?? null;
        $this->animated = $attributeData['animated'] ?? false;
        $this->width = $attributeData['width'] ?? null;
        $this->height = $attributeData['height'] ?? null;
        $this->hash = $attributeData['hash'] ?? null;
        $this->timestamp = $attributeData['timestamp'] ?? null;

        $this->metadata = new Collection($attributeData['metadata'] ?? []);

        $this->exists = true;
    }

    public function getStateAsAttributeData(): array
    {
        return [
            'index'     => $this->index,
            'path'      => $this->path,
            'extension' => $this->extension,
            'animated'  => $this->animated,
            'width'     => $this->width,
            'height'    => $this->height,
            'hash'      => $this->hash,
            'timestamp' => $this->timestamp,
            'metadata'  => $this->metadata->toArray(),
        ];
    }

    public function setData($data): void
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot call setData on an image marked as read only');
        }

        if ($this->path && static::$filesystem->exists($this->path)) {
            $this->removeAtPathOnFlush = $this->path;
        }

        static $fInfo = null;

        if (! $fInfo) {
            $fInfo = new finfo;
        }

        if ($data instanceof UploadedFile) {
            $data = file_get_contents($data->getRealPath());
        }

        if (str_starts_with($data, 'data:')) {
            $data = file_get_contents($data);
        }

        [$width, $height] = getimagesizefromstring($data);

        $mimeType = $fInfo->buffer($data, FILEINFO_MIME_TYPE);

        if ($mimeType === 'image/x-ms-bmp') {
            $mimeType = 'image/bmp';
        }

        if (! $mimeType) {
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

        if (isset(static::MIME_TYPE_EXTENSIONS[$mimeType])) {
            $this->extension = static::MIME_TYPE_EXTENSIONS[$mimeType];

            if ($mimeType === 'image/gif') {
                // magic bytes
                $this->animated = (bool) preg_match('#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', $data);
            }
        } else {
            throw new RuntimeException('Unsupported mime-type for expected image: ' . $mimeType);
        }
    }

    public function metadata(): Collection
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
            if (in_array($pathReplacement, ['index', 'extension', 'width', 'height', 'hash', 'timestamp'])) {
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

    public function pathHasReplacements(): bool
    {
        return (bool) preg_match('#{(\w+)}#', $this->path);
    }

    public function isFullyRemoved(): bool
    {
        return $this->flush === true && $this->removeAtPathOnFlush !== '' && $this->path === '';
    }

    public function remove(): void
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot remove an image marked as read only');
        }

        if ($this->path == '') {
            throw new RuntimeException('Called remove on an image that has no path');
        }

        $this->exists = false;
        $this->flush = true;
        $this->removeAtPathOnFlush = $this->path;

        $this->index = null;
        $this->path = '';
        $this->extension = '';
        $this->width = null;
        $this->height = null;
        $this->hash = '';
        $this->timestamp = 0;
        $this->metadata = new Collection;
    }

    public function requiresFlush(): bool
    {
        return $this->flush;
    }

    public function flush()
    {
        if ($this->isReadOnly) {
            throw new RuntimeException('Cannot flush an image marked as read only');
        }

        if (! $this->flush) {
            return;
        }

        if ($this->removeAtPathOnFlush) {
            static::$filesystem->delete($this->removeAtPathOnFlush);

            $removeAtPathExtension = pathinfo($this->removeAtPathOnFlush, PATHINFO_EXTENSION);
            $optimizedRemoveAtPath = Str::replace(".{$removeAtPathExtension}", ".optimized.{$removeAtPathExtension}", $this->removeAtPathOnFlush);

            if (static::$filesystem->exists($optimizedRemoveAtPath)) {
                static::$filesystem->delete($optimizedRemoveAtPath);
            }
        }

        if ($this->data) {
            if ($this->pathHasReplacements()) {
                throw new RuntimeException('The image path still has an unresolved replacement in it ("{...}") and cannot be saved: ' . $this->path);
            }

            static::$filesystem->put($this->path, $this->data);
        }

        $this->flush = false;
    }

    public function resetToFreshState()
    {
        $data = static::$filesystem->get($this->path);

        $this->path = $this->pathTemplate;
        $this->exists = true;
        $this->flush = true;
        $this->data = $data;
        $this->timestamp = Carbon::now()->unix();
    }

    public function hasOptimizedCopy()
    {
        $pathExtension = pathinfo($this->path, PATHINFO_EXTENSION);
        $optimizedPath = Str::replace(".{$pathExtension}", ".optimized.{$pathExtension}", $this->path);

        return static::$filesystem->exists($optimizedPath);
    }

    public function canBeOptimized()
    {
        return $this->width > 1920 || $this->height > 1080;
    }

    public function optimize()
    {
        $transformer = new ImageTransformer(ImageTransformer::createTransformationCollection(['gifoptimize', 'fit', 'quality']));

        $pathExtension = pathinfo($this->path, PATHINFO_EXTENSION);
        $optimizedPath = Str::replace(".{$pathExtension}", ".optimized.{$pathExtension}", $this->path);

        $imageBytes = static::$filesystem->get($this->path);

        static::$filesystem->put($optimizedPath, $transformer->transform(collect(['fit' => 'limit', 'height' => 1080, 'width' => 1920]), $imageBytes));
    }

    public function __get($name): mixed
    {
        $properties = [
            'index'     => $this->index,
            'path'      => $this->path,
            'extension' => $this->extension,
            'animated'  => $this->animated,
            'width'     => $this->width,
            'height'    => $this->height,
            'hash'      => $this->hash,
            'timestamp' => $this->timestamp,
        ];

        if (array_key_exists($name, $properties)) {
            return $properties[$name];
        }

        throw new OutOfBoundsException("Property $name is not accessible");
    }

    public function __isset(string $name): bool
    {
        $properties = [
            'index'     => $this->index,
            'path'      => $this->path,
            'extension' => $this->extension,
            'animated'  => $this->animated,
            'width'     => $this->width,
            'height'    => $this->height,
            'hash'      => $this->hash,
            'timestamp' => $this->timestamp,
        ];

        if (! array_key_exists($name, $properties)) {
            throw new OutOfBoundsException("Property $name is not accessible");
        }

        return isset($properties[$name]);
    }

    public function toArray(): array
    {
        return $this->getStateAsAttributeData();
    }

    public function jsonSerialize(): mixed
    {
        if ($this->exists) {
            return [
                'path'     => $this->path,
                'metadata' => $this->metadata,
            ];
        }

        return null;
    }

    public function __clone()
    {
        $this->metadata = clone $this->metadata;
    }
}
