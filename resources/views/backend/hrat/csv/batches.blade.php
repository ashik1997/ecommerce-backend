@extends('backend.master')
@section('page_title', 'Attendance Import Batches')
@section('page_heading', 'Attendance Import Batches')
@section('content')
    @include('backend.hrat.csv._batch_table', ['batches' => $batches])
@endsection
