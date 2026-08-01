<?php

namespace App\Http\Controllers;

use App\Models\AdminSettings;
use Barryvdh\DomPDF\Facade\Pdf;

class EarningsController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->middleware('auth');
        $this->settings = AdminSettings::first();
    }

    /**
     * Listado de meses con ingresos + enlaces de descarga.
     */
    public function statements()
    {
        $months = auth()->user()->myPaymentsReceived()
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as total, SUM(amount) as gross, SUM(earning_net_user) as net')
            ->groupBy('y', 'm')
            ->orderByRaw('y DESC, m DESC')
            ->get();

        return view('earnings.statements', [
            'months' => $months,
            'settings' => $this->settings,
        ]);
    }

    /**
     * Genera y descarga la liquidacion mensual en PDF.
     */
    public function downloadPdf($year, $month)
    {
        $year = (int) $year;
        $month = (int) $month;

        $transactions = auth()->user()->myPaymentsReceived()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at')
            ->get();

        abort_if($transactions->isEmpty(), 404);

        $gross = $transactions->sum('amount');
        $net = $transactions->sum('earning_net_user');
        $commission = $gross - $net;

        $pdf = Pdf::loadView('earnings.monthly-statement', [
            'user' => auth()->user(),
            'transactions' => $transactions,
            'year' => $year,
            'month' => $month,
            'gross' => $gross,
            'net' => $net,
            'commission' => $commission,
            'settings' => $this->settings,
        ]);

        return $pdf->download('liquidacion-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf');
    }
}
