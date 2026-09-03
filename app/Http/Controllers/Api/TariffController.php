<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tariff;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    public function index()
    {
        $categories = ['roda23', 'roda4', 'rodaplus4', 'bermalam'];
        $result     = [];

        foreach ($categories as $cat) {
            $tariff = Tariff::where('vehicle_type', $cat)
                ->where('effective_date', '<=', now()->toDateString())
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->first();

            $result[$cat] = $tariff
                ? ['amount' => $tariff->amount, 'effective_date' => $tariff->effective_date]
                : null;
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_type'   => 'required|in:roda23,roda4,rodaplus4,bermalam',
            'amount'         => 'required|integer|min:0',
            'effective_date' => 'required|date',
        ]);

        $tariff = Tariff::create([
            'vehicle_type'   => $request->vehicle_type,
            'amount'         => $request->amount,
            'effective_date' => $request->effective_date,
            'created_by'     => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Tarif berhasil disimpan.',
            'tariff'  => $tariff,
        ], 201);
    }

    public function history()
    {
        $history = Tariff::with('creator:id,name')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        return response()->json($history);
    }
}
