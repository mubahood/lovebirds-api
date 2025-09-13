<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;

class MarketplaceDashboardController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Marketplace Dashboard')
            ->description('Marketplace analytics and product performance')
            ->body('Marketplace analytics will be implemented here');
    }

    public function products(Content $content)
    {
        return $content
            ->title('Product Analytics')
            ->description('Product performance and sales metrics')
            ->body('Product analytics will be implemented here');
    }

    public function orders(Content $content)
    {
        return $content
            ->title('Order Analytics')
            ->description('Order management and fulfillment metrics')
            ->body('Order analytics will be implemented here');
    }
}
