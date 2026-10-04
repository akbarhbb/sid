<?php

namespace App\Http\Controllers;

use App\Models\PaymentProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentProviderController extends Controller
{
    public function index(): View
    {
        $providers = PaymentProvider::query()
            ->where('region_id', request()->user()->region_id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.payment-providers', compact('providers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedProvider($request);
        $validated['credentials'] = $this->decodeCredentials($validated['credentials'] ?? null);
        $validated['region_id'] = $request->user()->region_id;

        abort_if($validated['region_id'] === null, 403, 'Akun belum ditautkan ke wilayah.');

        if ($validated['is_default']) {
            PaymentProvider::query()->where('region_id', $validated['region_id'])->update(['is_default' => false]);
        }

        PaymentProvider::query()->create($validated);

        return to_route('admin.payment-providers.index')->with('status', 'Penyedia pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, PaymentProvider $paymentProvider): RedirectResponse
    {
        abort_unless($paymentProvider->region_id === $request->user()->region_id, 404);

        $validated = $this->validatedProvider($request);
        $credentials = $validated['credentials'] ?? null;
        unset($validated['credentials']);

        if ($credentials !== null && trim($credentials) !== '') {
            $validated['credentials'] = $this->decodeCredentials($credentials);
        }

        if ($validated['is_default']) {
            PaymentProvider::query()
                ->where('region_id', $request->user()->region_id)
                ->whereKeyNot($paymentProvider->id)
                ->update(['is_default' => false]);
        }

        $paymentProvider->update($validated);

        return to_route('admin.payment-providers.index')->with('status', 'Konfigurasi penyedia diperbarui.');
    }

    public function destroy(Request $request, PaymentProvider $paymentProvider): RedirectResponse
    {
        abort_unless($paymentProvider->region_id === $request->user()->region_id, 404);

        $paymentProvider->delete();

        return to_route('admin.payment-providers.index')->with('status', 'Penyedia pembayaran dihapus.');
    }

    /** @return array<string, mixed> */
    private function validatedProvider(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'driver' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'environment' => ['required', 'in:sandbox,production'],
            'credentials' => ['nullable', 'string', 'json', 'max:10000'],
            'supported_methods' => ['required', 'array', 'min:1'],
            'supported_methods.*' => ['string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
        ];
    }

    /** @return array<string, mixed>|null */
    private function decodeCredentials(?string $credentials): ?array
    {
        if ($credentials === null || trim($credentials) === '') {
            return null;
        }

        return json_decode($credentials, true, flags: JSON_THROW_ON_ERROR);
    }
}
