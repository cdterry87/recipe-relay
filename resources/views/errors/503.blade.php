@extends('errors.layout')

@section('code', '503')
@section('title', 'Back soon')
@section('message')
{{ config('app.name') }} is getting a quick tidy-up. Please check back shortly.
@endsection
