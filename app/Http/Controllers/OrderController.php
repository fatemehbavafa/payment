<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPUnit\Util\Exception;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Payment;

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
            'order' => $order
        ], 200);
    }

    public function pay(Order $order)
    {
        $invoice = new Invoice();
        $invoice->amount((int)$order->amount);
        $uuid = (string)Str::uuid();

        return Payment::callbackUrl(action([self::class, 'verify'], ['uuid' => $uuid]))
            ->purchase(
                $invoice,
                function ($driver) use ($invoice, $uuid, $order) {
                    Transaction::create([
                        'uuid'      => $uuid,
                        'authority' => $invoice->getTransactionId(),
                        'amount'    => $order->amount,
                        'user_id'   => $order->user_id,
                        'order_id'  => $order->id,
                    ]);
                }
            )->pay()->render();

    }

    public function verify()
    {
        $transaction = Transaction::where('uuid', request('uuid'))->first();
        if (null === $transaction) {
            return response()->json([
                'message' => 'تراکنش یافت نشد'
            ], 422);
        }

        try {
            $receipt                = Payment::amount($transaction->amount)->transactionId((string)$transaction->authority)->verify();
            $transaction->reference = $receipt->getReferenceId();
            $transaction->status    = 1;
            $transaction->save();
        } catch (\Exception $exception) {
            throw new Exception('مشکل در انجام تراکنش');
        }
    }

}
