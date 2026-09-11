@extends('backend.hrat.masters.master-table', [
    'title' => 'Designations',
    'heading' => 'Designations',
    'storeRoute' => route('hrat.designations.store'),
    'updateRouteName' => 'hrat.designations.update',
    'destroyRouteName' => 'hrat.designations.destroy',
    'items' => $items,
])
