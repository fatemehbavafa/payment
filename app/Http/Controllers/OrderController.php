<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Util\Exception;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Shetabit\Payment\Facade\Payment as PaymentFacade;
use Shetabit\Multipay\Exceptions\InvoiceNotFoundException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

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

        return PaymentFacade::via('zarinpal')->callbackUrl(action([self::class, 'verify'], ['uuid' => $uuid]))
            ->purchase(
                $invoice,
                function ($driver, $transactionId) use ($invoice, $uuid, $order) {
                    Transaction::create([
                        'uuid'      => $uuid,
                        'authority' => $transactionId,
                        'amount'    => $order->amount,
                        'user_id'   => $order->user_id,
                        'order_id'  => $order->id,
                    ]);
                }
            )->pay()->render();

    }

    /**
     * @throws InvoiceNotFoundException
     */
    public function verify()
    {
        $transaction = Transaction::where('uuid', request('uuid'))->first();
        if (null === $transaction) {
            return response()->json([
                'message' => 'تراکنش یافت نشد'
            ], 422);
        }
        $payment = new Payment(config('payment'));
        try {
            $receipt = $payment->via('zarinpal')->amount($transaction->amount)->transactionId((string)$transaction->authority)->verify();
            DB::beginTransaction();
            $transaction->update([
                'reference' => $receipt->getReferenceId(),
                'status'    => 1
            ]);
            $transaction->order()->update([
                'paid' => true
            ]);
            DB::commit();
            dd('yay');
        } catch (InvalidPaymentException $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage() . '. ' . $exception->getCode());
        }
    }

}
