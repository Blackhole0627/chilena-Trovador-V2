<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Models\Gift;
use App\Models\User;
use App\Models\Messages;
use App\Models\LiveComments;
use Illuminate\Http\Request;
use App\Models\Notifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

final class GiftController extends Controller
{
    use Traits\Functions;

    public $path = 'public/img/gifts/';

    public function show()
    {
        return view('admin.gifts', [
            'data' => Gift::latest()->paginate(20)
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'price' => 'required|numeric|min:0.50',
            'image' => 'required|mimes:png,svg,gif|dimensions:min_width=100',
        ];

        $this->validate($request, $rules);

        if ($request->hasFile('image')) {
            $file = $request->file('image')->hashName();
            $request->file('image')->move($this->path, $file);

            Gift::create([
                'price' => $request->price,
                'image' => $file
            ]);
        }

        return back()->withSuccess(__('general.successfully_added'));
    }

    public function edit(Gift $gift)
    {
        return view('admin.edit-gift', [
            'gift' => $gift
        ]);
    }

    public function update(Gift $gift, Request $request): RedirectResponse
    {
        $rules = [
            'price' => 'required|numeric|min:0.50',
        ];

        $file = $gift->image;

        $this->validate($request, $rules);

        if ($request->hasFile('image')) {
            $file = $request->file('image')->hashName();
            $request->file('image')->move($this->path, $file);

            \File::delete($this->path . $gift->image);
        }

        $gift->update([
            'price' => $request->price,
            'image' => $file,
            'status' => $request->status
        ]);

        return redirect()->route('gifts')
            ->withSuccess(__('general.success_update'));
    }

    public function destroy(Gift $gift): RedirectResponse
    {
        \File::delete($this->path . $gift->image);

        $gift->delete();

        return back()->withSuccess(__('general.successfully_removed'));
    }

    public function send(Request $request)
    {
        $messages = [
            'gift.required' => __('general.please_select_gift'),
            'gift.integer' => __('general.please_select_gift'),
        ];

        $validator = Validator::make($request->all(), [
            'gift' => 'required|integer',
            'message' => 'max:50'
          ], $messages);

        if ($validator->fails()) {
            return response()->json([
              'success' => false,
              'errors' => $validator->getMessageBag()->toArray(),
            ]);
          }

        $gift = Gift::findOrFail($request->gift);
        $user = User::findOrFail($request->user_id);
        $amount = $gift->price;

        if (auth()->user()->wallet < Helper::amountGross($amount)) {
            return response()->json([
                "success" => false,
                "errors" => ['error' => __('general.not_enough_funds')]
            ]);
        }

        // Admin and user earnings calculation
        $earnings = $this->earningsAdminUser($user->custom_fee, $amount, null, null);

        // Insert Transaction
        $txn = $this->transaction(
            'w_' . str_random(25),
            auth()->id(),
            0,
            $user->id,
            $amount,
            $earnings['user'],
            $earnings['admin'],
            'Wallet',
            'gift',
            $earnings['percentageApplied'],
            auth()->user()->taxesPayable()
        );

        // Insert ID Gift
        $txn->update([
            'gift_id' => $gift->id
        ]);

        // Subtract user funds
        auth()->user()->decrement('wallet', Helper::amountGross($amount));

        // Add Earnings to User
        $user->increment('balance', $earnings['user']);

        // Send Notification
        if (!$request->isLive) {
            Notifications::send($user->id, auth()->id(), 26, auth()->id());
        }

        // Check if is Live Streaming
        if ($request->isLive) {
            $sql = new LiveComments();
            $sql->user_id = auth()->id();
            $sql->live_streamings_id = $request->liveID;
            $sql->comment = $request->message ?? '';
            $sql->joined = 0;
            $sql->gift_id = $gift->id;
            $sql->earnings = $amount;
            $sql->save();
        }

        // Check if the tip is sent by message
        if ($request->isMessage) {
            $message = new Messages();
            $message->conversations_id = 0;
            $message->from_user_id = auth()->id();
            $message->to_user_id = $user->id;
            $message->message = $request->message ?? '';
            $message->updated_at = now();
            $message->gift_id = $gift->id;
            $message->gift_amount = $amount;
            $message->save();
        }

        return response()->json([
            'success' => true,
            'wallet' => Helper::userWallet()
        ]);
    }
}
