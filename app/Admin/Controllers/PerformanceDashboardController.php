<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;

class PerformanceDashboardController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Performance Dashboard')
            ->description('Technical performance metrics and system health')
            ->body('Performance analytics will be implemented here');
    }

    public function technical(Content $content)
    {
        return $content
            ->title('Technical Metrics')
            ->description('API performance, response times, and error rates')
            ->body('Technical metrics will be implemented here');
    }

    public function system(Content $content)
    {
        return $content
            ->title('System Health')
            ->description('System health monitoring and infrastructure metrics')
            ->body('System health metrics will be implemented here');
    }
}
