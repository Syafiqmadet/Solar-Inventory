@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title','New Project')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-folder-plus me-2"></i>New Project</h1>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-light">Back</a>
    </div>
</div>
<div class="content-area">
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
<div class="card-body p-4">
<form method="POST" action="{{ route('projects.store') }}">
@csrf
@include('projects._form', ['project' => null, 'assignedUserIds' => []])
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-solar"><i class="bi bi-check-lg me-2"></i>Create Project</button>
    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
</div></div></div></div>
</div>
@endsection
