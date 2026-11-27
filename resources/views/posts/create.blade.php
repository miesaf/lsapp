@extends('layouts.app')

@section('content')
    <h1>Create Post</h1>
    <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" class="form-control" placeholder="Title">
        </div>
        <div class="form-group">
            <label for="article-ckeditor">Body</label>
            <textarea name="body" id="article-ckeditor" class="form-control" placeholder="Body Text" rows="10" cols="50">{{ old('body') }}</textarea>
        </div>
        <div class="form-group">
            <input type="file" name="cover_image" class="form-control-file">
        </div>
        <input type="submit" value="Submit" class="btn btn-primary">
    </form>
@endsection
