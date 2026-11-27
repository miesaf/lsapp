@extends('layouts.app')

@section('content')
    <a class="btn btn-primary" href="/posts">Go Back</a>
    <h1>{{$post->title}}</h1>
    <img src="/storage/cover_images/{{$post->cover_image}}" style="max-width:100%">
    <br/><br/>
    <div>
        {!! $post->safeBody() !!}
    </div>
    <hr/>
    <small>Written on {{$post->created_at}} by {{$post->user->name}}</small>
    <hr/>
    @if(!Auth::guest())
        @if(Auth::user()->id == $post->user_id)
            <a href="/posts/{{$post->id}}/edit" class="btn btn-warning">Edit</a>

            <form method="POST" action="{{ route('posts.destroy', $post->id) }}" class="float-right">
                @csrf
                @method('DELETE')
                <input type="submit" value="Delete" class="btn btn-danger">
            </form>
        @endif
    @endif
@endsection