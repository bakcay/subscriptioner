<?php

namespace App\Http\Controllers;

use App\Exceptions\ReportException;
use App\Models\Event;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function report(Request $request, $date)
    {
        try{
             $dateStart = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
             $dateEnd   = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
        }catch (\Exception $e){
            throw new ReportException('Invalid date format.',400);
        }


        $keys   = ['started', 'cancelled', 'reactivated', 'renewed', 'failed', 'extended', 'shrinked'];
        $report = [];

        foreach ($keys as $key) {
            $report[$key] = Cache::remember("report_date_range_{$key}_{$dateStart}_{$dateEnd}", 512,function () use ($key, $dateStart, $dateEnd) {
                                    return Event::where('event', $key)
                                                ->whereBetween('created_at', [$dateStart, $dateEnd])
                                                ->count();
                            });

        }

        return response()->json($report);
    }

    public function dateRangeReport(Request $request, $start_date, $end_date)
    {
        try{
            $startDate = Carbon::createFromFormat('Y-m-d', $start_date)->startOfDay();
            $endDate   = Carbon::createFromFormat('Y-m-d', $end_date)->endOfDay();
        }catch (\Exception $e) {
            throw new ReportException('Invalid date format.', 400);
        }


        if ($startDate->gt($endDate)) {
            throw new ReportException( 'Start date must be less than end date.',400);
        }

        $daysDifference = $startDate->diffInDays($endDate);
        if ($daysDifference > 10) {
            throw new ReportException( 'The date range should not exceed 10 days.',400);
        }

        $i      = 0;
        $keys   = ['started', 'cancelled', 'reactivated', 'renewed', 'failed', 'extended', 'shrinked'];
        $report = [];

        for ($dateStart = $startDate; $dateStart->lte($endDate); $dateStart->addDay()) {
            $dateEnd = $dateStart->copy()
                            ->addDay();

            $report[$i]['date'] = $dateStart->toDateString();


            foreach ($keys as $key) {
                $report[$i][$key] = Cache::remember("report_date_range_{$key}_{$dateStart}_{$dateEnd}", 512, function () use ($key, $dateStart, $dateEnd) {
                                        return Event::where('event', $key)
                                                    ->whereBetween('created_at', [$dateStart, $dateEnd])
                                                    ->count();
                                    });
            }

            $i++;
        }

        return response()->json($report);
    }
}
