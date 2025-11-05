<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function stockSummary(Request $r)
    {
        return response()->json([
            'ok' => true,
            'summary' => [],
        ]);
    }

    public function expiryReport(Request $r)
    {
        return response()->json([
            'ok' => true,
            'near_expiry' => [],
        ]);
    }

    public function procurementReport(Request $r)
    {
        return response()->json([
            'ok' => true,
            'purchases' => [],
        ]);
    }

    public function logisticsReport(Request $r)
    {
        return response()->json([
            'ok' => true,
            'deliveries' => [],
        ]);
    }

    public function qualityReport(Request $r)
    {
        return response()->json([
            'ok' => true,
            'quality' => [],
        ]);
    }

    public function vendorPerformance(Request $r)
    {
        return response()->json([
            'ok' => true,
            'vendors' => [],
        ]);
    }
}
