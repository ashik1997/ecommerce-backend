@extends('backend.hrat.masters.master-table', [
    'title' => 'Branches',
    'heading' => 'Branches',
    'storeRoute' => route('hrat.branches.store'),
    'updateRouteName' => 'hrat.branches.update',
    'destroyRouteName' => 'hrat.branches.destroy',
    'items' => $items,
    'isBranch' => true,
])
