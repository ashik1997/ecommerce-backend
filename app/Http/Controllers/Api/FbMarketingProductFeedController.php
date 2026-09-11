<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmProductFeedService;
use Illuminate\Http\Response;

class FbMarketingProductFeedController extends Controller
{
    public function __invoke(FbmProductFeedService $feed): Response
    {
        return response($feed->xml(), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
