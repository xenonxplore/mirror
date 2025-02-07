@extends('layouts.app')

@section('content')
    <section id="shop" class="shop black-bg">
        <div class="container text-white text-center">
            <div class="row pt-3">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <form method="POST" action="/shop/create" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row text-white text-left">
                            <div class="form-group col-md-2">
                                <label class="">Name *</label>
                            </div>
                            <div class="form-group col-md-10">
                                <input type="text" class="form-control contact-form input-container" name="name" value="{{ old('name') }}" placeholder="Name of the product" required>
                            </div>
                        </div>

                        <div class="form-row text-white text-left">
                            <div class="form-group col-md-2">
                                <label>Price in BDT *</label>
                            </div>
                            <div class="form-group col-md-10">
                                <input type="number" class="input-container form-control contact-form" name="price" value="{{ old('price') }}" placeholder="Price of the product" required>
                            </div>
                        </div>

                        <div class="form-row text-white text-left">
                            <div class="form-group col-md-2">
                                <label>Quantity *</label>
                            </div>
                            <div class="form-group col-md-10">
                                <input type="number" class="form-control contact-form" name="quantity" value="{{ old('quantity') }}" placeholder="Quantity of the product" required>
                            </div>
                        </div>

                        <div class="form-row text-white text-left">
                            <div class="form-group col-md-2">
                                <label>Description *</label>
                            </div>
                            <div class="form-group col-md-10">
                                <textarea name="description" id="article-ckeditor" class="form-control contact-form" cols="30" rows="10"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row text-white">
                            <div class="form-group col-md-2">
                                <label>Product Image *</label>
                            </div>
                            <div class="form-group col-md-10">
                                <div>Please upload images with ratio of 3:2, e.g. 290 x 193 | max image size is 2 MB</div><br>
                                <input required type="file" class="form-control" name="img">
                            </div>
                        </div>
                        
                        <br>

                        <button type="submit" class="btn btn-danger register-btn">Save</button>
                        <br><br><br>
                    </form>
                </div>
                <div class="col-md-1"></div>
            </div>
        </div>
    </section>
@endsection