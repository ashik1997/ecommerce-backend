@extends('backend.hrat.masters.master-table', [
    'title' => 'Departments',
    'heading' => 'Departments',
    'storeRoute' => route('hrat.departments.store'),
    'updateRouteName' => 'hrat.departments.update',
    'destroyRouteName' => 'hrat.departments.destroy',
    'items' => $items,
])
