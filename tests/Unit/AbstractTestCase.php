<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use ZiffMedia\LaravelEloquentImagery\EloquentImageryProvider;

abstract class AbstractTestCase extends TestCase
{
    public function getEnvironmentSetUp($application)
    {
        $application['config']->set('eloquent-imagery.filesystem', 'imagery');
        $application['config']->set('filesystems.disks.imagery', [
            'driver' => 'local',
            'root' => realpath(__DIR__ . '/../') . '/storage',
        ]);

        Carbon::setTestNow(Carbon::now());
    }

    protected function getPackageProviders($app)
    {
        return [EloquentImageryProvider::class];
    }

    public function tearDown(): void
    {
        $disk = Storage::disk('imagery');

        foreach ($disk->allDirectories() as $directory) {
            $disk->deleteDirectory($directory);
        }

        parent::tearDown();
    }
}
