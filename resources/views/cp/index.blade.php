@extends('statamic::layout')
@section('title', __('Consent Manager'))

@section('content')
    <consent-manager-publish-form
        title="{{ __('Consent Manager') }}"
        action="{{ cp_route('consent-manager.update') }}"
        :blueprint='@json($blueprint)'
        :meta='@json($meta)'
        :values='@json($values)'
    ></consent-manager-publish-form>
@stop
