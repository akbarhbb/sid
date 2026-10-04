<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\LedgerTransaction;
use App\Models\OrganizationMember;
use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Region;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\PaymentGatewayRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalManagementController extends Controller
{
    public function residents(): View
    {
        abort_unless(in_array(request()->user()->role, ['superadmin', 'admin_rt'], true), 403);
        $users = User::query()->where('region_id', request()->user()->region_id)->orderBy('account_status')->latest()->paginate(25);
        ActivityLog::query()->create([
            'region_id' => request()->user()->region_id,
            'user_id' => request()->user()->id,
            'action' => 'resident.sensitive_data_accessed',
            'subject_type' => User::class,
            'metadata' => ['record_count' => $users->count(), 'page' => $users->currentPage()],
            'ip_address' => request()->ip(),
        ]);

        return view('admin.residents', compact('users'));
    }

    public function reviewResident(Request $request, User $user): RedirectResponse
    {
        $this->ensureSameRegion($request, $user->region_id);
        $validated = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'is_adult' => ['nullable', 'boolean']]);
        $previousStatus = $user->account_status;
        $user->update(['account_status' => $validated['status'], 'is_adult' => $validated['status'] === 'approved' && $request->boolean('is_adult')]);
        $this->logActivity($request, 'resident.reviewed', $user, ['from' => $previousStatus, 'to' => $validated['status']]);

        return back()->with('status', 'Status akun warga diperbarui.');
    }

    public function createServiceRequest(): View
    {
        return view('portal.service-request-create');
    }

    public function storeServiceRequest(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->account_status === 'approved', 403);
        $validated = $request->validate([
            'type' => ['required', Rule::in(['surat_pengantar', 'keluhan', 'izin_kegiatan', 'peminjaman_fasilitas'])],
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        ServiceRequest::query()->create([
            ...$validated,
            'region_id' => $user->region_id,
            'resident_id' => $user->id,
            'reference' => 'RW-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
        ]);

        return to_route('service-requests.mine')->with('status', 'Pengajuan diterima. Simpan nomor referensi untuk pelacakan.');
    }

    public function myServiceRequests(): View
    {
        $requests = ServiceRequest::query()->where('resident_id', request()->user()->id)->latest()->paginate(15);

        return view('portal.service-requests', compact('requests'));
    }

    public function editResidentProfile(): View
    {
        return view('portal.profile', ['resident' => request()->user()]);
    }

    public function updateResidentProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'occupation' => ['nullable', 'string', 'max:120'],
        ]);
        $user->update($validated);

        return back()->with('status', 'Data dasar berhasil diperbarui. Untuk koreksi data lain, hubungi pengurus.');
    }

    public function serviceRequests(): View
    {
        $requests = ServiceRequest::query()->where('region_id', request()->user()->region_id)->with('resident')->latest()->paginate(25);

        return view('admin.service-requests', compact('requests'));
    }

    public function reviewServiceRequest(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->ensureSameRegion($request, $serviceRequest->region_id);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['diproses', 'disetujui', 'ditolak', 'selesai'])],
            'staff_note' => ['nullable', 'string', 'max:3000'],
        ]);
        $serviceRequest->update([...$validated, 'assignee_id' => $request->user()->id]);
        $this->logActivity($request, 'service_request.reviewed', $serviceRequest, ['status' => $validated['status']]);

        return back()->with('status', 'Status pengajuan diperbarui.');
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['ronda', 'kerja_bakti', 'iuran', 'darurat', 'kegiatan', 'lainnya'])],
            'body' => ['required', 'string', 'max:10000'],
            'is_emergency' => ['nullable', 'boolean'],
        ]);
        $announcement = Announcement::query()->create([
            ...$validated,
            'region_id' => $request->user()->region_id,
            'author_id' => $request->user()->id,
            'is_emergency' => $request->boolean('is_emergency'),
            'is_published' => true,
            'published_at' => now(),
        ]);
        $this->logActivity($request, 'announcement.published', $announcement);

        return to_route('admin.dashboard')->with('status', 'Pengumuman berhasil diterbitkan.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization' => ['required', Rule::in(['rt', 'dkm', 'karang_taruna'])],
            'type' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'occurred_at' => ['required', 'date'],
            'is_public' => ['nullable', 'boolean'],
            'evidence' => ['required_if:type,pengeluaran', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        $this->authorizeOrganization($request, $validated['organization']);
        $region = Region::query()->findOrFail($request->user()->region_id);
        $approvalLimit = (int) data_get($region->settings, 'expense_approval_limit', 0);
        $evidencePath = $request->file('evidence')?->store('ledger-evidence');
        $requiresApproval = $validated['type'] === 'pengeluaran' && $validated['amount'] > $approvalLimit;
        $transaction = LedgerTransaction::query()->create([
            ...$validated,
            'region_id' => $region->id,
            'recorded_by' => $request->user()->id,
            'status' => $requiresApproval ? 'menunggu_persetujuan' : 'disetujui',
            'is_public' => $request->boolean('is_public', true),
            'evidence_path' => $evidencePath,
        ]);

        if ($validated['type'] === 'pengeluaran') {
            $transaction->approvals()->create(['approved_by' => $request->user()->id, 'approved_at' => now()]);
        }

        $this->logActivity($request, 'ledger.transaction_created', $transaction, ['status' => $transaction->status]);

        return to_route('admin.ledger.index')->with('status', $requiresApproval ? 'Transaksi dicatat dan menunggu persetujuan pengurus kedua.' : 'Transaksi kas dicatat.');
    }

    public function ledger(): View
    {
        $transactions = LedgerTransaction::query()->where('region_id', request()->user()->region_id)->with('approvals')->latest('occurred_at')->paginate(30);
        $region = Region::query()->find(request()->user()->region_id);

        return view('admin.ledger', compact('transactions', 'region'));
    }

    public function approveTransaction(Request $request, LedgerTransaction $ledgerTransaction): RedirectResponse
    {
        $this->ensureSameRegion($request, $ledgerTransaction->region_id);
        abort_unless($ledgerTransaction->type === 'pengeluaran' && $ledgerTransaction->status === 'menunggu_persetujuan', 422);
        abort_if($ledgerTransaction->recorded_by === $request->user()->id, 403, 'Pencatat transaksi tidak dapat menyetujui pengeluarannya sendiri.');

        DB::transaction(function () use ($ledgerTransaction, $request): void {
            $ledgerTransaction->approvals()->firstOrCreate(
                ['approved_by' => $request->user()->id],
                ['approved_at' => now()],
            );

            if ($ledgerTransaction->approvals()->count() >= 2) {
                $ledgerTransaction->update(['status' => 'disetujui']);
            }
        });
        $this->logActivity($request, 'ledger.transaction_approved', $ledgerTransaction);

        return back()->with('status', 'Persetujuan dicatat.');
    }

    public function reverseTransaction(Request $request, LedgerTransaction $ledgerTransaction): RedirectResponse
    {
        $this->ensureSameRegion($request, $ledgerTransaction->region_id);
        abort_unless($ledgerTransaction->status === 'disetujui', 422);
        $validated = $request->validate(['description' => ['required', 'string', 'max:255']]);
        $reversal = LedgerTransaction::query()->create([
            'region_id' => $ledgerTransaction->region_id,
            'recorded_by' => $request->user()->id,
            'reverses_id' => $ledgerTransaction->id,
            'organization' => $ledgerTransaction->organization,
            'type' => $ledgerTransaction->type === 'pemasukan' ? 'pengeluaran' : 'pemasukan',
            'category' => 'koreksi',
            'description' => $validated['description'],
            'amount' => $ledgerTransaction->amount,
            'status' => 'disetujui',
            'is_public' => $ledgerTransaction->is_public,
            'occurred_at' => now(),
        ]);
        $this->logActivity($request, 'ledger.transaction_reversed', $reversal, ['reverses_id' => $ledgerTransaction->id]);

        return back()->with('status', 'Koreksi dicatat sebagai transaksi pembalik. Transaksi asal tetap tersimpan.');
    }

    public function cctv(): View
    {
        $regionId = request()->user()->region_id;
        $cameras = DB::table('cctv_cameras')->where('region_id', $regionId)->where('is_active', true)->get(['id', 'name', 'area', 'status']);
        $requests = DB::table('cctv_access_requests')->join('cctv_cameras', 'cctv_cameras.id', '=', 'cctv_access_requests.camera_id')->where('cctv_access_requests.resident_id', request()->user()->id)->latest('cctv_access_requests.created_at')->get(['cctv_access_requests.*', 'cctv_cameras.name as camera_name']);

        return view('portal.cctv', compact('cameras', 'requests'));
    }

    public function requestCctvAccess(Request $request): RedirectResponse
    {
        abort_unless($request->user()->account_status === 'approved', 403);
        $validated = $request->validate([
            'camera_id' => ['required', Rule::exists('cctv_cameras', 'id')->where('region_id', $request->user()->region_id)->where('is_active', true)],
            'purpose' => ['required', 'string', 'max:1000'],
        ]);
        DB::table('cctv_access_requests')->insert([
            'region_id' => $request->user()->region_id,
            'camera_id' => $validated['camera_id'],
            'resident_id' => $request->user()->id,
            'purpose' => $validated['purpose'],
            'status' => 'diajukan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('cctv.index')->with('status', 'Permintaan akses CCTV dikirim untuk persetujuan pengurus.');
    }

    public function viewCctvStream(Request $request, int $camera): RedirectResponse
    {
        $access = DB::table('cctv_access_requests')
            ->where('camera_id', $camera)
            ->where('resident_id', $request->user()->id)
            ->where('region_id', $request->user()->region_id)
            ->where('status', 'disetujui')
            ->where('approved_until', '>', now())
            ->exists();
        abort_unless($access, 403);

        $cameraRecord = DB::table('cctv_cameras')->where('id', $camera)->where('region_id', $request->user()->region_id)->where('is_active', true)->first();
        abort_unless($cameraRecord, 404);
        $this->assertSafeStreamUrl($cameraRecord->stream_url);

        ActivityLog::query()->create([
            'region_id' => $request->user()->region_id,
            'user_id' => $request->user()->id,
            'action' => 'cctv.stream_accessed',
            'subject_type' => 'cctv_camera',
            'subject_id' => $camera,
            'metadata' => ['camera_name' => $cameraRecord->name],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->away($cameraRecord->stream_url);
    }

    public function cctvRequests(): View
    {
        $requests = DB::table('cctv_access_requests')->join('users', 'users.id', '=', 'cctv_access_requests.resident_id')->join('cctv_cameras', 'cctv_cameras.id', '=', 'cctv_access_requests.camera_id')->where('cctv_access_requests.region_id', request()->user()->region_id)->latest('cctv_access_requests.created_at')->paginate(25, ['cctv_access_requests.*', 'users.name as resident_name', 'cctv_cameras.name as camera_name']);

        return view('admin.cctv-requests', compact('requests'));
    }

    public function reviewCctvRequest(Request $request, int $accessRequest): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['disetujui', 'ditolak', 'dicabut'])], 'days' => ['nullable', 'integer', 'min:1', 'max:365']]);
        $updated = DB::table('cctv_access_requests')->where('id', $accessRequest)->where('region_id', $request->user()->region_id)->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'approved_until' => $validated['status'] === 'disetujui' ? now()->addDays((int) ($validated['days'] ?? 30)) : null,
            'updated_at' => now(),
        ]);
        abort_unless($updated, 404);
        $this->logActivity($request, 'cctv.access_reviewed', null, ['request_id' => $accessRequest, 'status' => $validated['status']]);

        return back()->with('status', 'Permintaan akses CCTV diperbarui.');
    }

    public function storeCamera(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'admin_rt'], true), 403);
        $validated = $request->validate(['name' => ['required', 'string', 'max:120'], 'area' => ['required', 'string', 'max:180'], 'stream_url' => ['nullable', 'url', 'max:2000'], 'is_active' => ['nullable', 'boolean']]);
        DB::table('cctv_cameras')->insert([
            ...$validated,
            'region_id' => $request->user()->region_id,
            'stream_url' => $validated['stream_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('admin.cctv-requests.index')->with('status', 'Kamera CCTV ditambahkan. Stream hanya tersedia melalui gateway aman.');
    }

    public function storeActivity(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'admin_rt', 'pengurus_dkm', 'pengurus_karang_taruna'], true), 403);
        $validated = $request->validate(['organization' => ['required', Rule::in(['rt', 'dkm', 'karang_taruna'])], 'title' => ['required', 'string', 'max:180'], 'description' => ['required', 'string', 'max:5000'], 'location' => ['nullable', 'string', 'max:180'], 'quota' => ['nullable', 'integer', 'min:1', 'max:10000'], 'starts_at' => ['required', 'date', 'after_or_equal:now'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'requires_registration' => ['nullable', 'boolean']]);
        $organizationName = match ($validated['organization']) {
            'dkm' => 'DKM', 'karang_taruna' => 'Karang Taruna', default => 'RT'
        };
        DB::table('organizations')->insertOrIgnore(['region_id' => $request->user()->region_id, 'type' => $validated['organization'], 'name' => $organizationName, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $organization = DB::table('organizations')->where('region_id', $request->user()->region_id)->where('type', $validated['organization'])->first();
        unset($validated['organization']);
        DB::table('activities')->insert([
            ...$validated,
            'region_id' => $request->user()->region_id,
            'organization_id' => $organization->id,
            'created_by' => $request->user()->id,
            'location' => $validated['location'] ?? null,
            'quota' => $validated['quota'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'status' => 'published',
            'requires_registration' => $request->boolean('requires_registration'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('admin.dashboard')->with('status', 'Kegiatan berhasil dijadwalkan.');
    }

    public function storeFacility(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:2000'], 'capacity' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        DB::table('facilities')->insert([...$validated, 'region_id' => $request->user()->region_id, 'created_at' => now(), 'updated_at' => now()]);

        return to_route('admin.dashboard')->with('status', 'Fasilitas berhasil ditambahkan.');
    }

    public function bookFacility(Request $request, int $facility): RedirectResponse
    {
        abort_unless($request->user()->account_status === 'approved', 403);
        $validated = $request->validate(['purpose' => ['required', 'string', 'max:255'], 'starts_at' => ['required', 'date', 'after:now'], 'ends_at' => ['required', 'date', 'after:starts_at']]);
        $facilityRecord = DB::table('facilities')->where('region_id', $request->user()->region_id)->where('id', $facility)->where('status', 'tersedia')->first();
        abort_unless($facilityRecord, 404);
        $hasConflict = DB::table('facility_bookings')->where('facility_id', $facility)->whereIn('status', ['diajukan', 'disetujui'])->where('starts_at', '<', $validated['ends_at'])->where('ends_at', '>', $validated['starts_at'])->exists();
        abort_if($hasConflict, 422, 'Waktu peminjaman bertabrakan dengan permintaan lain.');
        DB::table('facility_bookings')->insert([...$validated, 'facility_id' => $facility, 'user_id' => $request->user()->id, 'status' => 'diajukan', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Permintaan peminjaman fasilitas dikirim ke pengurus.');
    }

    public function reviewFacilityBooking(Request $request, int $booking): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'admin_rt'], true), 403);
        $validated = $request->validate(['status' => ['required', Rule::in(['disetujui', 'ditolak'])]]);
        $bookingExists = DB::table('facility_bookings')->join('facilities', 'facilities.id', '=', 'facility_bookings.facility_id')->where('facilities.region_id', $request->user()->region_id)->where('facility_bookings.id', $booking)->exists();
        abort_unless($bookingExists, 404);
        $updated = DB::table('facility_bookings')->where('id', $booking)->update(['status' => $validated['status'], 'reviewed_by' => $request->user()->id, 'updated_at' => now()]);
        abort_unless($updated, 404);

        return back()->with('status', 'Permintaan peminjaman diproses.');
    }

    public function election(): View
    {
        $election = DB::table('elections')->where('region_id', request()->user()->region_id)->whereIn('status', ['registration', 'voting', 'closed'])->latest()->first();
        $candidates = $election ? DB::table('candidates')->where('election_id', $election->id)->where('is_published', true)->get() : collect();
        $eligibility = $election ? DB::table('election_eligibilities')->where('election_id', $election->id)->where('user_id', request()->user()->id)->first() : null;

        return view('portal.election', compact('election', 'candidates', 'eligibility'));
    }

    public function castVote(Request $request, int $election): RedirectResponse
    {
        abort_unless($request->user()->account_status === 'approved' && $request->user()->consented_at, 403);
        $validated = $request->validate([
            'candidate_id' => ['required', Rule::exists('candidates', 'id')->where('election_id', $election)->where('is_published', true)],
            'election_consent' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $election, $validated): void {
            $electionRecord = DB::table('elections')->where('id', $election)->where('region_id', $request->user()->region_id)->lockForUpdate()->first();
            abort_unless($electionRecord && $electionRecord->status === 'voting' && now()->between($electionRecord->voting_opens_at, $electionRecord->voting_closes_at), 422);
            $eligibility = DB::table('election_eligibilities')->where('election_id', $election)->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($eligibility && $eligibility->verified_at && ! $eligibility->voted_at, 403);
            DB::table('election_ballots')->insert(['election_id' => $election, 'candidate_id' => $validated['candidate_id'], 'created_at' => now(), 'updated_at' => now()]);
            DB::table('election_eligibilities')->where('id', $eligibility->id)->update(['consented_at' => now(), 'voted_at' => now()]);
        });

        return back()->with('status', 'Suara direkam tanpa menghubungkan pilihan dengan identitas pemilih.');
    }

    public function consentToElection(Request $request, int $election): RedirectResponse
    {
        abort_unless($request->user()->account_status === 'approved' && $request->user()->is_adult, 403);
        $request->validate(['election_consent' => ['accepted']]);
        $electionRecord = DB::table('elections')->where('id', $election)->where('region_id', $request->user()->region_id)->where('status', 'registration')->first();
        abort_unless($electionRecord, 404);
        DB::table('election_eligibilities')->updateOrInsert(
            ['election_id' => $election, 'user_id' => $request->user()->id],
            ['consented_at' => now()],
        );

        return back()->with('status', 'Persetujuan pemilih dicatat. Panitia akan memverifikasi kelayakan memilih.');
    }

    public function adminElection(): View
    {
        $elections = DB::table('elections')->where('region_id', request()->user()->region_id)->latest()->paginate(10);
        $residents = User::query()->where('region_id', request()->user()->region_id)->where('account_status', 'approved')->orderBy('name')->get();
        $activeElection = DB::table('elections')->where('region_id', request()->user()->region_id)->latest()->first();
        $candidates = $activeElection ? DB::table('candidates')->where('election_id', $activeElection->id)->get() : collect();

        return view('admin.elections', compact('elections', 'residents', 'activeElection', 'candidates'));
    }

    public function createElection(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'panitia_pemilihan'], true), 403);
        $validated = $request->validate(['title' => ['required', 'string', 'max:180'], 'registration_opens_at' => ['required', 'date'], 'voting_opens_at' => ['required', 'date', 'after:registration_opens_at'], 'voting_closes_at' => ['required', 'date', 'after:voting_opens_at']]);
        DB::table('elections')->insert([...$validated, 'region_id' => $request->user()->region_id, 'status' => 'registration', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Tahapan pemilihan dibuat.');
    }

    public function storeCandidate(Request $request, int $election): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'panitia_pemilihan'], true), 403);
        $validated = $request->validate(['name' => ['required', 'string', 'max:120'], 'profile' => ['required', 'string', 'max:3000'], 'vision' => ['nullable', 'string', 'max:3000'], 'mission' => ['nullable', 'string', 'max:5000']]);
        $electionRecord = DB::table('elections')->where('id', $election)->where('region_id', $request->user()->region_id)->first();
        abort_unless($electionRecord, 404);
        DB::table('candidates')->insert([...$validated, 'election_id' => $election, 'is_published' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Calon ditambahkan.');
    }

    public function verifyVoter(Request $request, int $election, User $user): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'panitia_pemilihan'], true), 403);
        $this->ensureSameRegion($request, $user->region_id);
        $electionRecord = DB::table('elections')->where('id', $election)->where('region_id', $request->user()->region_id)->first();
        abort_unless($electionRecord && $electionRecord->status === 'registration', 422);
        abort_unless($user->account_status === 'approved' && $user->is_adult, 422, 'Pemilih harus warga dewasa terverifikasi.');
        $eligibility = DB::table('election_eligibilities')->where('election_id', $election)->where('user_id', $user->id)->first();
        abort_unless($eligibility?->consented_at, 422, 'Warga harus memberikan persetujuan pemilih terlebih dahulu.');
        DB::table('election_eligibilities')->where('id', $eligibility->id)->update(['verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('status', 'Pemilih diverifikasi.');
    }

    public function updateElection(Request $request, int $election): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'panitia_pemilihan'], true), 403);
        $validated = $request->validate(['status' => ['required', Rule::in(['registration', 'voting', 'closed'])]]);
        $electionRecord = DB::table('elections')->where('id', $election)->where('region_id', $request->user()->region_id)->first();
        abort_unless($electionRecord, 404);
        $validTransition = match ($electionRecord->status) {
            'registration' => in_array($validated['status'], ['registration', 'voting'], true),
            'voting' => in_array($validated['status'], ['voting', 'closed'], true),
            default => $validated['status'] === 'closed',
        };
        abort_unless($validTransition, 422, 'Tahap pemilihan tidak dapat dibuka kembali setelah ditutup.');
        if ($validated['status'] === 'voting') {
            abort_unless(now()->gte($electionRecord->voting_opens_at) && now()->lt($electionRecord->voting_closes_at), 422, 'Jadwal pemungutan suara belum dimulai atau sudah lewat.');
        }
        $publishedAt = $validated['status'] === 'closed' ? now() : null;
        DB::table('elections')->where('id', $election)->update(['status' => $validated['status'], 'results_published_at' => $publishedAt, 'updated_at' => now()]);

        return back()->with('status', 'Tahap pemilihan diperbarui.');
    }

    public function electionResults(Request $request, int $election): StreamedResponse
    {
        $electionRecord = DB::table('elections')->where('id', $election)->where('region_id', $request->user()->region_id)->where('status', 'closed')->whereNotNull('results_published_at')->first();
        abort_unless($electionRecord, 404);
        $results = DB::table('candidates')->leftJoin('election_ballots', function ($join) use ($election): void {
            $join->on('candidates.id', '=', 'election_ballots.candidate_id')->where('election_ballots.election_id', '=', $election);
        })->where('candidates.election_id', $election)->select('candidates.name', 'candidates.vision', 'candidates.mission', DB::raw('COUNT(election_ballots.id) as votes'))->groupBy('candidates.id', 'candidates.name', 'candidates.vision', 'candidates.mission')->orderByDesc('votes')->get();

        return response()->streamDownload(function () use ($results, $electionRecord): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Pemilihan', $electionRecord->title]);
            fputcsv($output, ['Calon', 'Visi', 'Misi', 'Suara']);

            foreach ($results as $result) {
                fputcsv($output, [$result->name, $result->vision, $result->mission, $result->votes]);
            }

            fclose($output);
        }, 'hasil-pemilihan-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function publicElectionResults(int $election): View
    {
        $electionRecord = DB::table('elections')->where('id', $election)->where('status', 'closed')->whereNotNull('results_published_at')->first();
        abort_unless($electionRecord, 404);
        $results = DB::table('candidates')->leftJoin('election_ballots', function ($join) use ($election): void {
            $join->on('candidates.id', '=', 'election_ballots.candidate_id')->where('election_ballots.election_id', '=', $election);
        })->where('candidates.election_id', $election)->select('candidates.name', 'candidates.vision', 'candidates.mission', DB::raw('COUNT(election_ballots.id) as votes'))->groupBy('candidates.id', 'candidates.name', 'candidates.vision', 'candidates.mission')->orderByDesc('votes')->get();

        return view('portal.election-results', compact('electionRecord', 'results'));
    }

    public function activities(): View
    {
        $activities = DB::table('activities')->leftJoin('organizations', 'organizations.id', '=', 'activities.organization_id')->where('activities.region_id', request()->user()->region_id)->where('activities.status', 'published')->orderBy('activities.starts_at')->paginate(15, ['activities.*', 'organizations.name as organization_name']);

        return view('portal.activities', compact('activities'));
    }

    public function operations(): View
    {
        $regionId = request()->user()->region_id;
        $activities = DB::table('activities')->leftJoin('organizations', 'organizations.id', '=', 'activities.organization_id')->where('activities.region_id', $regionId)->orderByDesc('activities.starts_at')->limit(20)->get(['activities.*', 'organizations.name as organization_name']);
        $facilities = DB::table('facilities')->where('region_id', $regionId)->orderBy('name')->get();
        $bookings = DB::table('facility_bookings')->join('facilities', 'facilities.id', '=', 'facility_bookings.facility_id')->join('users', 'users.id', '=', 'facility_bookings.user_id')->where('facilities.region_id', $regionId)->where('facility_bookings.status', 'diajukan')->orderBy('facility_bookings.starts_at')->get(['facility_bookings.*', 'facilities.name as facility_name', 'users.name as resident_name']);

        return view('admin.operations', compact('activities', 'facilities', 'bookings'));
    }

    public function registerForActivity(Request $request, int $activity): RedirectResponse
    {
        abort_unless($request->user()->account_status === 'approved', 403);
        $record = DB::table('activities')->where('id', $activity)->where('region_id', $request->user()->region_id)->where('status', 'published')->first();
        abort_unless($record && $record->starts_at > now(), 404);
        abort_unless($record->requires_registration, 422, 'Kegiatan ini tidak memerlukan pendaftaran.');
        abort_if($record->quota && DB::table('activity_registrations')->where('activity_id', $activity)->where('status', 'terdaftar')->count() >= $record->quota, 422, 'Kuota kegiatan sudah terpenuhi.');
        DB::table('activity_registrations')->insertOrIgnore(['activity_id' => $activity, 'user_id' => $request->user()->id, 'status' => 'terdaftar', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Pendaftaran kegiatan berhasil.');
    }

    public function facilities(): View
    {
        $facilities = DB::table('facilities')->where('region_id', request()->user()->region_id)->where('status', 'tersedia')->orderBy('name')->get();
        $bookings = DB::table('facility_bookings')->join('facilities', 'facilities.id', '=', 'facility_bookings.facility_id')->where('facility_bookings.user_id', request()->user()->id)->orderByDesc('facility_bookings.created_at')->get(['facility_bookings.*', 'facilities.name as facility_name']);

        return view('portal.facilities', compact('facilities', 'bookings'));
    }

    public function settings(): View
    {
        $region = Region::query()->findOrFail(request()->user()->region_id);

        return view('admin.settings', compact('region'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'superadmin', 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'district' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'expense_approval_limit' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'phone_emergency' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
        ]);
        $region = Region::query()->findOrFail($request->user()->region_id);
        $region->update([
            'name' => $validated['name'],
            'neighborhood' => $validated['neighborhood'] ?? null,
            'district' => $validated['district'],
            'city' => $validated['city'],
            'settings' => array_merge($region->settings ?? [], [
                'expense_approval_limit' => $validated['expense_approval_limit'],
                'phone_emergency' => $validated['phone_emergency'] ?? null,
                'email' => $validated['email'] ?? null,
            ]),
        ]);
        $this->logActivity($request, 'region.settings_updated', $region);

        return back()->with('status', 'Pengaturan wilayah diperbarui.');
    }

    public function activityLogs(): View
    {
        abort_unless(in_array(request()->user()->role, ['superadmin', 'admin_rt'], true), 403);
        $logs = ActivityLog::query()->where('region_id', request()->user()->region_id)->with('user')->latest()->paginate(40);

        return view('admin.activity-logs', compact('logs'));
    }

    public function publicOrganization(): View
    {
        $members = OrganizationMember::query()
            ->where('region_id', Region::query()->orderBy('id')->value('id'))
            ->orderBy('organization_type')
            ->orderBy('display_order')
            ->get()
            ->groupBy('organization_type');

        return view('organization', compact('members'));
    }

    public function adminOrganization(): View
    {
        $members = OrganizationMember::query()->where('region_id', request()->user()->region_id)->orderBy('organization_type')->orderBy('display_order')->get();

        return view('admin.organization', compact('members'));
    }

    public function storeOrganizationMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_type' => ['required', Rule::in(['rt', 'dkm', 'karang_taruna'])],
            'name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'term_starts_at' => ['nullable', 'date'],
            'term_ends_at' => ['nullable', 'date', 'after_or_equal:term_starts_at'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $photoPath = $request->file('photo')?->store('organization-photos', 'public');
        $member = OrganizationMember::query()->create([
            ...collect($validated)->except('photo')->all(),
            'region_id' => $request->user()->region_id,
            'photo_path' => $photoPath,
            'display_order' => $validated['display_order'] ?? 0,
        ]);
        $this->logActivity($request, 'organization.member_created', $member, ['organization_type' => $member->organization_type]);

        return to_route('admin.organization.index')->with('status', 'Anggota struktur organisasi ditambahkan.');
    }

    public function updateOrganizationMember(Request $request, OrganizationMember $organizationMember): RedirectResponse
    {
        $this->ensureSameRegion($request, $organizationMember->region_id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'term_starts_at' => ['nullable', 'date'],
            'term_ends_at' => ['nullable', 'date', 'after_or_equal:term_starts_at'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $photoPath = $request->file('photo')?->store('organization-photos', 'public');
        $organizationMember->update([
            ...collect($validated)->except('photo')->all(),
            'photo_path' => $photoPath ?? $organizationMember->photo_path,
            'display_order' => $validated['display_order'] ?? $organizationMember->display_order,
        ]);
        $this->logActivity($request, 'organization.member_updated', $organizationMember);

        return to_route('admin.organization.index')->with('status', 'Data anggota struktur organisasi diperbarui.');
    }

    public function destroyOrganizationMember(Request $request, OrganizationMember $organizationMember): RedirectResponse
    {
        $this->ensureSameRegion($request, $organizationMember->region_id);
        $this->logActivity($request, 'organization.member_deleted', null, ['member_id' => $organizationMember->id, 'name' => $organizationMember->name]);
        $organizationMember->delete();

        return to_route('admin.organization.index')->with('status', 'Anggota struktur organisasi dihapus.');
    }

    public function exportResidents(Request $request): StreamedResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'admin_rt'], true), 403);
        $this->ensureSameRegion($request, $request->user()->region_id);
        $fileName = 'warga-'.now()->format('Ymd-His').'.csv';
        $users = User::query()->where('region_id', $request->user()->region_id)->orderBy('name')->get();

        return response()->streamDownload(function () use ($users): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Nama', 'Email', 'Telepon', 'Alamat', 'Nomor Rumah', 'Pekerjaan', 'Status Akun', 'Status Domisili', 'NIK Tersamar', 'KK Tersamar']);

            foreach ($users as $user) {
                fputcsv($output, [$user->name, $user->email, $user->phone, $user->address, $user->house_number, $user->occupation, $user->account_status, $user->residency_status, $this->maskSensitiveNumber($user->national_id), $this->maskSensitiveNumber($user->family_card_number)]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportLedger(Request $request): StreamedResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'admin_rt', 'bendahara'], true), 403);
        $transactions = LedgerTransaction::query()->where('region_id', $request->user()->region_id)->with('approvals')->orderBy('occurred_at')->get();
        $fileName = 'kas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($transactions): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Organisasi', 'Jenis', 'Kategori', 'Keterangan', 'Jumlah', 'Status', 'Jumlah Persetujuan', 'ID Transaksi Pembalik']);

            foreach ($transactions as $transaction) {
                fputcsv($output, [$transaction->occurred_at->toDateTimeString(), $transaction->organization, $transaction->type, $transaction->category, $transaction->description, $transaction->amount, $transaction->status, $transaction->approvals->count(), $transaction->reverses_id]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function createInvoices(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['superadmin', 'admin_rt', 'bendahara'], true), 403);
        $validated = $request->validate([
            'organization' => ['required', Rule::in(['rt', 'dkm', 'karang_taruna'])],
            'description' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ]);
        $this->authorizeOrganization($request, $validated['organization']);
        $residents = User::query()->where('region_id', $request->user()->region_id)->where('account_status', 'approved')->where('residency_status', 'resident')->get(['id']);

        $invoices = $residents->map(fn (User $resident): array => [
            'region_id' => $request->user()->region_id,
            'user_id' => $resident->id,
            'reference' => 'INV-'.now()->format('ym').'-'.Str::upper(Str::random(12)),
            ...$validated,
            'status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        foreach (array_chunk($invoices, 500) as $invoiceChunk) {
            DB::table('dues_invoices')->insert($invoiceChunk);
        }

        $this->logActivity($request, 'dues.invoices_created', null, ['count' => $residents->count(), 'organization' => $validated['organization']]);

        return back()->with('status', 'Tagihan dibuat untuk '.$residents->count().' warga terverifikasi.');
    }

    public function myInvoices(): View
    {
        $invoices = DB::table('dues_invoices')->where('user_id', request()->user()->id)->where('region_id', request()->user()->region_id)->latest('due_date')->paginate(20);
        $providers = PaymentProvider::query()->where('region_id', request()->user()->region_id)->where('is_active', true)->orderByDesc('is_default')->get();

        return view('portal.invoices', compact('invoices', 'providers'));
    }

    public function startInvoicePayment(Request $request, int $invoice, PaymentGatewayRegistry $gateways): RedirectResponse
    {
        $validated = $request->validate([
            'payment_provider_id' => ['required', Rule::exists('payment_providers', 'id')->where('region_id', $request->user()->region_id)->where('is_active', true)],
            'method' => ['required', 'string', 'max:50'],
        ]);
        $invoiceRecord = DB::table('dues_invoices')->where('id', $invoice)->where('user_id', $request->user()->id)->where('region_id', $request->user()->region_id)->first();
        abort_unless($invoiceRecord && $invoiceRecord->status === 'unpaid', 404);
        $provider = PaymentProvider::query()->where('id', $validated['payment_provider_id'])->where('region_id', $request->user()->region_id)->where('is_active', true)->firstOrFail();
        abort_unless(in_array($validated['method'], $provider->supported_methods ?? [], true), 422, 'Metode pembayaran tidak didukung penyedia ini.');

        $transaction = PaymentTransaction::query()->create([
            'region_id' => $request->user()->region_id,
            'payment_provider_id' => $provider->id,
            'user_id' => $request->user()->id,
            'reference' => 'PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(12)),
            'purpose' => 'iuran:'.$invoiceRecord->reference,
            'amount' => $invoiceRecord->amount,
            'currency' => config('payment.currency', 'IDR'),
            'method' => $validated['method'],
            'status' => 'pending',
            'metadata' => ['invoice_id' => $invoiceRecord->id],
        ]);

        try {
            $checkout = $gateways->adapterFor($provider)->createCheckout($provider, $transaction);
        } catch (\Throwable $exception) {
            $transaction->update(['status' => 'failed', 'metadata' => array_merge($transaction->metadata ?? [], ['error' => 'checkout_creation_failed'])]);
            report($exception);

            return back()->withErrors(['payment_provider_id' => 'Checkout belum dapat dibuat. Periksa konfigurasi provider atau coba provider lain.']);
        }

        $transaction->update([
            'provider_reference' => $checkout['provider_reference'] ?? null,
            'metadata' => array_merge($transaction->metadata ?? [], $checkout['metadata'] ?? [], ['checkout_url' => $checkout['checkout_url']]),
        ]);

        return redirect()->away($checkout['checkout_url']);
    }

    public function paymentStatus(Request $request, PaymentTransaction $paymentTransaction): View
    {
        abort_unless($paymentTransaction->region_id === $request->user()->region_id && $paymentTransaction->user_id === $request->user()->id, 404);

        return view('portal.payment-status', compact('paymentTransaction'));
    }

    public function paymentWebhook(Request $request, PaymentProvider $paymentProvider, PaymentGatewayRegistry $gateways): Response
    {
        $paymentProvider->refresh();
        abort_unless($paymentProvider->is_active, 404);
        $event = $gateways->adapterFor($paymentProvider)->verifyWebhook($paymentProvider, $request->getContent(), $request->headers->all());

        DB::transaction(function () use ($paymentProvider, $event): void {
            $inserted = DB::table('payment_webhook_events')->insertOrIgnore([
                'region_id' => $paymentProvider->region_id,
                'payment_provider_id' => $paymentProvider->id,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'payload' => json_encode($event['payload'], JSON_THROW_ON_ERROR),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return;
            }

            $transaction = PaymentTransaction::query()->where('reference', $event['reference'])->where('payment_provider_id', $paymentProvider->id)->where('region_id', $paymentProvider->region_id)->lockForUpdate()->first();

            if (! $transaction || $transaction->status !== 'pending') {
                return;
            }

            if ($event['amount'] !== $transaction->amount || strtoupper($event['currency']) !== strtoupper($transaction->currency)) {
                throw new HttpResponseException(response('Payment amount or currency mismatch', 422));
            }

            if ($event['status'] === 'paid') {
                $transaction->update(['status' => 'paid', 'paid_at' => now()]);
                $invoiceId = data_get($transaction->metadata, 'invoice_id');
                $invoice = DB::table('dues_invoices')->where('id', $invoiceId)->where('region_id', $paymentProvider->region_id)->where('user_id', $transaction->user_id)->where('status', 'unpaid')->first();

                if (! $invoice) {
                    return;
                }

                $ledgerTransaction = LedgerTransaction::query()->create([
                    'region_id' => $paymentProvider->region_id,
                    'recorded_by' => $transaction->user_id,
                    'organization' => $invoice->organization,
                    'type' => 'pemasukan',
                    'category' => 'iuran',
                    'description' => 'Pembayaran '.$invoice->reference,
                    'amount' => $transaction->amount,
                    'status' => 'disetujui',
                    'is_public' => true,
                    'occurred_at' => now(),
                ]);
                $transaction->update(['metadata' => array_merge($transaction->metadata ?? [], ['ledger_transaction_id' => $ledgerTransaction->id])]);
                DB::table('dues_invoices')->where('id', $invoice->id)->update(['status' => 'paid', 'paid_at' => now(), 'ledger_transaction_id' => $ledgerTransaction->id, 'updated_at' => now()]);
            } elseif (in_array($event['status'], ['failed', 'cancelled', 'expired'], true)) {
                $transaction->update(['status' => $event['status']]);
            }
        });

        return response('ok');
    }

    private function ensureSameRegion(Request $request, ?int $regionId): void
    {
        abort_unless($regionId && $regionId === $request->user()->region_id, 404);
    }

    private function authorizeOrganization(Request $request, string $organization): void
    {
        $allowed = match ($organization) {
            'rt' => in_array($request->user()->role, ['superadmin', 'admin_rt', 'bendahara'], true),
            'dkm' => in_array($request->user()->role, ['superadmin', 'pengurus_dkm'], true),
            'karang_taruna' => in_array($request->user()->role, ['superadmin', 'pengurus_karang_taruna'], true),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function assertSafeStreamUrl(?string $url): void
    {
        if ($url === null) {
            return;
        }

        $host = parse_url($url, PHP_URL_HOST);
        abort_unless(parse_url($url, PHP_URL_SCHEME) === 'https' && is_string($host), 422, 'URL stream harus memakai HTTPS.');

        foreach (gethostbynamel($host) ?: [] as $address) {
            abort_unless(filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE), 422, 'Stream kamera harus melalui gateway publik, bukan IP lokal.');
        }
    }

    private function maskSensitiveNumber(?string $value): string
    {
        return $value ? str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4) : '';
    }

    /** @param array<string, mixed> $metadata */
    private function logActivity(Request $request, string $action, ?object $subject, array $metadata = []): void
    {
        ActivityLog::query()->create([
            'region_id' => $request->user()->region_id,
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
        ]);
    }
}
