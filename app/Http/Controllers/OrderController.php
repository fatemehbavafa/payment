<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function order(Package $package): JsonResponse
    {
        $order = Order::create([
            'user_id'    => Auth::id(),
            'package_id' => $package->id,
            'amount'     => $package->price
        ]);

        return response()->json([
            'status' => '200',
            'result' => [
                'order' => $order
            ]
        ]);
    }


}
