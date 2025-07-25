<?php

namespace App\Services;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class ThresholdService
{
    protected $base_url;
    protected $user_name;
    protected $password;
    protected $btc_wallet;
    protected $sol_wallet;

    public function __construct()
    {
        $this->base_url = config('services.threshold.base_url');
        $this->user_name = config('services.threshold.user_name');
        $this->password = config('services.threshold.password');
        $this->btc_wallet = config('services.threshold.btc_wallet');
        $this->sol_wallet = config('services.threshold.sol_wallet');
    }

    public function createUserWallets($user)
    {
        $coins = ['btc', 'sol'];

        foreach ($coins as $coin) {
            $this->generateAndStoreAddress($user->id, $coin);
        }
    }

    protected function generateAndStoreAddress($userId, $coin)
    {
        $latestAddress = UserAddress::where('coin_type', $coin)->latest()->first();

        Log::info('Latest address lookup result', [
            'user_id' => $userId,
            'coin' => $coin,
            'latestAddress' => $latestAddress
        ]);


        if ($latestAddress) {
            $parts = explode('/', $latestAddress->path);
            $pathIndex = (int) array_pop($parts) + 1;
        }

        $walletId = match ($coin) {
            'btc' => $this->btc_wallet,
            'sol' => $this->sol_wallet,
            default => throw new \InvalidArgumentException("Unsupported coin type: {$coin}"),
        };

        $url = "{$this->base_url}/generate-address";
        $payload = [
            'wallet' => [
                'coin' => $coin,
                'walletId' => (int) $walletId,
                'allToken' => true
            ],
            'path' => $pathIndex
        ];


        Log::info("Generating address for user ID {$userId}, coin: {$coin}", [
            'url' => $url,
            'payload' => $payload,
            'user_id' => $userId
        ]);

        $response = Http::withBasicAuth($this->user_name, $this->password)
            ->post($url, $payload);

        if ($response->successful() && $response->json('success')) {
            $addressData = $response->json('data');

            UserAddress::create([
                'user_id' => $userId,
                'coin_type' => $coin,
                'address' => $addressData['address'],
                'path' => $addressData['path']
            ]);

            Log::info("Successfully generated address for user ID {$userId}", [
                'addressData' => $addressData
            ]);

            return $addressData;
        }

        Log::error("Failed to generate address for user ID {$userId}, coin: {$coin}", [
            'response' => $response->json(),
            'status' => $response->status()
        ]);

        throw new \Exception('Address generation failed for ' . strtoupper($coin));
    }


    public function processTopUpWebhook(array $data)
    {
        // Log the full payload
        Log::info("Top-up webhook received:", $data);

        // Send raw email directly
        Mail::raw('Webhook Data: ' . json_encode($data, JSON_PRETTY_PRINT), function ($message) {
            $message->to('nwanoziep@gmail.com')
                    ->subject('Top-up Webhook Notification');
        });

        if (($data['type'] ?? '') !== 'receive' || ($data['transactionStatus']['primaryStatus'] ?? '') !== 'success') {
            Log::info("Webhook ignored: type or status invalid.");
            return;
        }

        $coin = strtolower($data['coin'] ?? '');
        $outputs = $data['outputs'] ?? [];
        $address = collect($outputs)->firstWhere('isMine', true)['address'] ?? null;

        $amount = $data['effectivechange'] ?? null;

        if (!$address || !$amount || !in_array($coin, ['btc', 'sol'])) {
            Log::warning("Missing or invalid data: address = $address, amount = $amount, coin = $coin");
            return;
        }

        $userAddress = UserAddress::where('address', $address)->where('coin_type', $coin)->first();

        if (!$userAddress) {
            Log::warning("Top-up received but no user found for address: $address");
            return;
        }

        $user = $userAddress->user;

        $user->increment('clique_token_wallet', $amount);

        Log::info("User ID {$user->id} topped up {$amount} {$coin} to clique_token_wallet via address {$address}");

        return [
            'user' => $user,
            'amount' => $amount
        ];
    }


}
