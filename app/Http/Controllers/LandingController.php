<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Reservation;
use App\Settings\LandingPageSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    public function index(LandingPageSettings $settings)
    {
        $categories = Category::with(['products' => function ($query) {
            $query->where('is_sellable', true);
        }])->get();

        // Get featured products (e.g., favorites)
        $featuredProducts = Product::where('is_favorite', true)
            ->where('is_sellable', true)
            ->take(8)
            ->get();

        return view('landing.index', [
            'settings' => $settings,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
        ]);
    }

    public function menu(LandingPageSettings $settings)
    {
        $categories = Category::with(['products' => function ($query) {
            $query->where('is_sellable', true)->orderBy('name');
        }])->orderBy('name')->get();

        return view('landing.menu', [
            'settings' => $settings,
            'categories' => $categories,
        ]);
    }

    public function storeReservation(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'party_size' => 'required|integer|min:1|max:20',
            'reservation_date' => 'required|date|after:now',
            'special_requests' => 'nullable|string|max:500',
        ]);

        try {
            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'party_size' => $validated['party_size'],
                'reservation_date' => $validated['reservation_date'],
                'special_requests' => $validated['special_requests'] ?? null,
                'status' => 'pending',
                'deposit_amount' => 0, // Default 0 for public booking, maybe customizable later
            ]);

            // Optional: Dispatch event or notification here

            return response()->json([
                'success' => true,
                'message' => 'Reservasi berhasil dikirim! Kami akan segera menghubungi Anda.',
                'data' => $reservation
            ]);
        } catch (\Exception $e) {
            Log::error('Reservation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses reservasi.',
            ], 500);
        }
    }
}
