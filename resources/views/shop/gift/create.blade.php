@extends('layouts.app')

@section('content')
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            <i class="fa-solid fa-border-all"></i> {{ __('Create New Gift') }}
        </div>
    </div>
    <form action="{{ route('shop.gift.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8 m-auto">
                <div class="card mt-3">
                    <div class="card-body">

                        <div class="d-flex gap-2 border-bottom pb-2">
                            <i class="fa-solid fa-user"></i>
                            <h5>
                                {{ __('Create New Gift') }}
                            </h5>
                        </div>

                        <div class="mt-3 d-flex align-items-center justify-content-center">
                            <div class="ratio1x1">
                                <img id="previewProfile" src="https://placehold.co/500x500/f1f5f9/png" alt=""
                                    width="100%">
                            </div>
                        </div>
                        <div class="mt-3">
                            <x-file name="thumbnail" label="Thumbnail (Ratio 1:1) *" preview="previewProfile" />
                        </div>

                        <div class="mt-3">
                            <x-input label="Name" name="name" type="text" placeholder="Enter Name" required/>
                        </div>

                        <div class="mt-3">
                            <x-input label="Price" name="price" type="text" placeholder="Price" onlyNumber="true" />
                        </div>

                        <div class="mt-5 d-flex gap-2 justify-content-between">
                            <a href="{{ route('shop.category.index') }}" class="btn btn-secondary py-2 px-4">
                                {{ __('Back') }}
                            </a>

                            <button type="submit" class="btn btn-primary py-2 px-4">
                                {{ __('Submit') }}
                            </button>

                        </div>

                    </div>

                </div>
            </div>
        </div>

    </form>
@endsection
