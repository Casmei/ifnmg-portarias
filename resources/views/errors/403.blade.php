@extends('errors::minimal')

@section('title', __('Não autorizado'))
@section('code', '403')
@section('message', __('Essa ação não é autorizada!'))
