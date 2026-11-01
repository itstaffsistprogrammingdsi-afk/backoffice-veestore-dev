@extends('layouts/contentNavbarLayout')

@section('title', 'Backoffice VeeStore')

@section('content')
    @vite(['resources/js/app.ts'])
    @inertia
@endsection
