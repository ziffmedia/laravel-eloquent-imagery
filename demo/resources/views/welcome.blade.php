@extends('layout')

@section('content')

    <h2>Rendering Examples (Legacy URL Strategy)</h2>

    @foreach ($singleImageExamples as $singleImageExample)
        <div class="mb-20">
            <h3 class="bg-gray-500 p-2 text-3xl text-bold text-right mb-8">
                {{ $singleImageExample->name  }}
            </h3>

            <div class="space-y-6">
                @foreach ($singleImageExample->variations as $title => $transformation)

                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ $title }}
                            </h3>
                            <div class="mt-2 text-sm leading-5 text-gray-500">
                                <a class="hover:underline" href="{{ $singleImageExample->image->url($transformation) }}" target="_blank">
                                    {{ $singleImageExample->image->url($transformation) }}
                                </a>
                            </div>
                            <div class="mt-5">
                                <img src="{{ $singleImageExample->image->url($transformation) }}">
                            </div>
                        </div>
                    </div>

                @endforeach

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Using the "thumbnail preset"
                        </h3>
                        <div class="mt-2 text-sm leading-5 text-gray-500">
                            <a class="hover:underline" href="{{ $singleImageExample->image->url('thumbnail') }}" target="_blank">
                                {{ $singleImageExample->image->url('thumbnail') }}
                            </a>
                        </div>
                        <div class="mt-5">
                            <img src="{{ $singleImageExample->image->url('thumbnail') }}">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endforeach



@endsection
