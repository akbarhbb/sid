<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\LedgerTransaction;
use App\Models\Region;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $region = Region::query()->first();
        $announcements = $region
            ? $region->announcements()->where('is_published', true)->latest('published_at')->take(3)->get()
            : collect();

        $transactions = $region
            ? LedgerTransaction::query()->where('region_id', $region->id)->where('status', 'disetujui')->where('is_public', true)->latest('occurred_at')->take(5)->get()
            : collect();
        $balance = $region ? $this->publicBalance($region->id) : 0;

        return view('home', compact('region', 'announcements', 'transactions', 'balance'));
    }

    public function announcements(): View
    {
        $region = Region::query()->first();
        $announcements = Announcement::query()
            ->when($region, fn ($query) => $query->where('region_id', $region->id))
            ->where('is_published', true)
            ->latest('published_at')
            ->paginate(10);

        return view('announcements', compact('announcements'));
    }

    public function admin(): View
    {
        $regionId = request()->user()->region_id;
        $pendingRequests = ServiceRequest::query()->where('region_id', $regionId)->whereIn('status', ['diajukan', 'diproses'])->count();
        $pendingResidents = User::query()->where('region_id', $regionId)->where('account_status', 'pending')->count();
        $pendingCctv = DB::table('cctv_access_requests')->where('region_id', $regionId)->where('status', 'diajukan')->count();
        $announcements = Announcement::query()->where('region_id', $regionId)->latest()->take(5)->get();

        $totalResidents = User::query()->where('region_id', $regionId)->count();
        $approvedResidents = User::query()->where('region_id', $regionId)->where('account_status', 'approved')->count();
        $verifiedPercent = $totalResidents > 0 ? (int) round(($approvedResidents / $totalResidents) * 100) : 0;

        $invoicesPaidThisMonth = DB::table('dues_invoices')
            ->where('region_id', $regionId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $announcementsActive = Announcement::query()
            ->where('region_id', $regionId)
            ->where('is_published', true)
            ->whereBetween('published_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return view('admin.dashboard', compact(
            'pendingRequests',
            'pendingResidents',
            'pendingCctv',
            'announcements',
            'verifiedPercent',
            'approvedResidents',
            'totalResidents',
            'invoicesPaidThisMonth',
            'announcementsActive',
        ));
    }

    private function publicBalance(int $regionId): int
    {
        return (int) LedgerTransaction::query()
            ->where('region_id', $regionId)
            ->where('status', 'disetujui')
            ->where('is_public', true)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE -amount END), 0) as balance")
            ->value('balance');
    }
}
