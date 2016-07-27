@extends('layouts.app')

@section('content')

    <h1>Please signin using google to continue</h1>
    <a href="{{ route('social.redirect', ['provider' => 'google']) }}" class="btn btn-lg btn-primary btn-block google" type="submit">Google</a>


@stop