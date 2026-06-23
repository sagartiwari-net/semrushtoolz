<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AffiliateReportFilters
{
    public function __construct(
        public string $view,
        public Carbon $from,
        public Carbon $to,
        public ?string $month,
        public bool $hideEmpty,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $hideEmpty = $request->boolean('hide_empty');
        $month = $request->query('report_month');
        $view = $request->query('report_view', 'monthly');

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $view = 'daily';
            $from = Carbon::parse($month.'-01')->startOfMonth();
            $to = $from->copy()->endOfMonth()->min(now()->endOfDay());
        } elseif ($request->filled('report_from') && $request->filled('report_to')) {
            $from = Carbon::parse($request->query('report_from'))->startOfDay();
            $to = Carbon::parse($request->query('report_to'))->endOfDay();
            $view = $request->query('report_view', 'monthly');
        } elseif ($view === 'daily') {
            $from = now()->startOfMonth();
            $to = now()->endOfDay();
            $month = now()->format('Y-m');
        } else {
            $from = now()->subMonths(11)->startOfMonth();
            $to = now()->endOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return new self($view, $from, $to, $month, $hideEmpty);
    }

    public function queryParams(): array
    {
        return array_filter([
            'tab' => 'reports',
            'report_view' => $this->view,
            'report_from' => $this->from->toDateString(),
            'report_to' => $this->to->toDateString(),
            'report_month' => $this->month,
            'hide_empty' => $this->hideEmpty ? '1' : null,
        ]);
    }

    public function periodLabel(): string
    {
        if ($this->month) {
            return Carbon::parse($this->month.'-01')->format('F Y');
        }

        if ($this->from->isSameMonth($this->to) && $this->from->isSameYear($this->to)) {
            return $this->from->format('F Y');
        }

        return $this->from->format('M d, Y').' – '.$this->to->format('M d, Y');
    }
}
