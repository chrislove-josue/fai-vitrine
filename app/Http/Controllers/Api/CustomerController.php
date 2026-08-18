<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController
{
    /**
     * Liste paginée des clients (source isp_core) pour les systèmes externes.
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->withCount('subscriptions')
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($where) use ($request) {
                    $where->where('customer_number', 'like', '%'.$request->string('q').'%')
                        ->orWhere('email', 'like', '%'.$request->string('q').'%')
                        ->orWhere('phone', 'like', '%'.$request->string('q').'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($customers);
    }
}
