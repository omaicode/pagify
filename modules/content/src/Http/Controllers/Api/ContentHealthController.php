<?php

namespace Modules\Content\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\ApiController;
use Modules\Core\Support\SiteContext;

class ContentHealthController extends ApiController
{
    public function public(SiteContext $siteContext): JsonResponse
    {
        return $this->success([
            'module' => 'content',
            'site_id' => $siteContext->siteId(),
            'locale' => app()->getLocale(),
        ]);
    }

    public function admin(SiteContext $siteContext): JsonResponse
    {
        return $this->success([
            'module' => 'content',
            'site_id' => $siteContext->siteId(),
            'locale' => app()->getLocale(),
            'scope' => 'admin',
        ]);
    }
}
