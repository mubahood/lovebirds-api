<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\ProfileBoost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;

class RevenueDashboardController extends Controller
{
    public function index(Content $content)
    {
        // Revenue metrics
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        $monthlyRevenue = Order::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $avgOrderValue = Order::where('status', 'completed')->avg('total_amount');

        $summaries = [
            ['Total Revenue', 'dollar-sign', 'green', admin_url('orders'), '$' . number_format($totalRevenue, 2)],
            ['Monthly Revenue', 'trending-up', 'blue', '#', '$' . number_format($monthlyRevenue, 2)],
            ['Active Subscriptions', 'repeat', 'purple', admin_url('subscriptions'), number_format($activeSubscriptions)],
            ['Avg Order Value', 'bar-chart', 'orange', '#', '$' . number_format($avgOrderValue, 2)],
        ];

        return $content
            ->title('Revenue & Monetization Dashboard')
            ->description('Comprehensive revenue analytics and monetization metrics')
            
            // Key revenue metrics
            ->row(function(Row $row) use ($summaries) {
                foreach ($summaries as [$label, $icon, $color, $link, $value]) {
                    $row->column(3, function(Column $col) use ($label, $icon, $color, $link, $value) {
                        $col->append(
                            (new InfoBox($label, $icon, $color, $link, $value))
                                ->solid()
                        );
                    });
                }
            })

            ->row(function(Row $row) {
                $row->column(12, function(Column $col) {
                    $col->append(
                        (new Box('Revenue Details', 'Detailed revenue analytics will be implemented here'))
                            ->style('info')
                            ->solid()
                    );
                });
            });
    }

    public function subscriptions(Content $content)
    {
        return $content
            ->title('Subscription Analytics')
            ->description('Subscription performance and retention metrics')
            ->body('Subscription analytics will be implemented here');
    }

    public function purchases(Content $content)
    {
        return $content
            ->title('Purchase Analytics')
            ->description('In-app purchase and boost analytics')
            ->body('Purchase analytics will be implemented here');
    }
}
