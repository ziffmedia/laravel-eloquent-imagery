<?php

use App\ImageCollectionExample;
use App\SingleImageExample;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->user();
        $this->singleImageExamples();
        $this->imageCollectionExamples();
    }

    public function user()
    {
        DB::table('users')->insert([
            'name' => 'Developer',
            'email' => 'developer@example.com',
            'password' => bcrypt('password')
        ]);
    }

    public function singleImageExamples()
    {
        tap(new SingleImageExample, function ($model) {
            $model->name = 'Green Box PNG Original 150x150';
            $model->variations = [
                'no transformations' => '',
                'Grayscale' => 'grayscale',
                'Fit Scale Up to 200 pixels in width' => 'fit_scale|size_200x',
                'Fit Limit Pad Up to 500 pixels in width, extra space added has purple background' => 'fit_lpad|size_500x|bg_800080',
                'Fit Resize to 100x50, will skew image' => 'fit_resize|size_100x50',
                'Fit Limit to 300 in width, 200 in height - should not scale up' => 'fit_limit|size_300x200',
                'Crop 100x100, gravity south-west' => 'crop_100x100|g_south_west',
                'Crop 100x100, gravity auto - means center' => 'crop_100x100|g_auto',
                'Crop 100x100, gravity auto - means center by default' => 'crop_100x100',
                'Fill 150x100, gravity auto' => 'fill|size_150x100',
                'Fill 150x100, gravity north-west' => 'fill|size_150x100|g_north_west',
                'Fill 120x100, gravity auto' => 'fill|size_120x100',
                'Fill 300x200, gravity auto' => 'fill|size_300x200',
            ];
            $model->image->setData(file_get_contents(resource_path('example-images/080-150x150.png')));
            $model->save();
        });

        tap(new SingleImageExample, function ($model) {
            $model->name = 'Red Rectangle JPG Original 500x200';
            $model->variations = [
                'no transformations' => '',
                'Grayscale' => 'grayscale',
                'Fit Scale Down to 200 pixels in width' => 'fit_scale|size_200x',
                'Fit Limit Pad Up to 800 pixels in width, extra space added has purple background' => 'fit_lpad|size_800x|bg_800080',
                'Fit Resize to 300x100, will skew image' => 'fit_resize|size_300x100',
                'Fit Limit to 600 in width, 300 in height - should not scale up' => 'fit_limit|size_600x300',
            ];
            $model->image->setData(file_get_contents(resource_path('example-images/f00-500x200.jpg')));
            $model->save();
        });

        tap(new SingleImageExample, function ($model) {
            $model->name = 'Animated GIF Original 480x270';
            $model->variations = [
                'no transformations' => '',
                'Grayscale' => 'grayscale',
                'Fit Scale Down to 200 pixels in width' => 'fit_scale|size_200x',
                'Fit Limit Pad Up to 800 pixels in width, extra space added has purple background' => 'fit_lpad|size_800x|bg_800080',
                'Fit Resize to 300x100, will skew image' => 'fit_resize|size_300x100',
                'Fit Limit to 600 in width, 300 in height - should not scale up' => 'fit_limit|size_600x300',
                'Static / Single Frame Of Gif' => 'static',
                'Static Single Frame, using frame 20, also grayscale' => 'static_20|grayscale'
            ];
            $model->image->setData(file_get_contents(resource_path('example-images/animated-480x270.gif')));
            $model->save();
        });
    }

    public function imageCollectionExamples()
    {
        tap(new ImageCollectionExample, function ($model) {
            $model->name = 'A few images';
            $model->images[] = file_get_contents(resource_path('example-images/008-150x300.png'));
            $model->images[] = file_get_contents(resource_path('example-images/080-150x150.png'));
            $model->images[] = file_get_contents(resource_path('example-images/f00-500x200.jpg'));
            $model->images[] = file_get_contents(resource_path('example-images/animated-480x270.gif'));
            $model->save();
        });
    }
}
