<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FlutterwaveService
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.flutterwave.base_url');
        $this->secretKey = config('services.flutterwave.secret_key');
    }


    public function fetchBanks($country = 'NG')
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/banks/{$country}");

        return $response->json();
    }

    public function createBeneficiary(array $data)
    {
        try {

            $response = Http::withToken($this->secretKey)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->post("{$this->baseUrl}/beneficiaries", $data);


            $result = $response->json();

            if (!$response->successful() || $result['status'] !== 'success') {
                throw new \Exception($result['message'] ?? 'Failed to create beneficiary');
            }

            return [
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['data'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verifyBankAccount(string $accountNumber, string $bankCode)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/accounts/resolve", [
                    'account_number' => $accountNumber,
                    'account_bank' => $bankCode,
                ]);

            $result = $response->json();

            if (!$response->successful() || $result['status'] !== 'success') {
                throw new \Exception($result['message'] ?? 'Failed to verify bank account');
            }

            return [
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['data'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function deleteBeneficiary(int $beneficiaryId)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->delete("{$this->baseUrl}/beneficiaries/{$beneficiaryId}");

            $result = $response->json();

            if (!$response->successful() || $result['status'] !== 'success') {
                throw new \Exception($result['message'] ?? 'Failed to delete beneficiary');
            }

            return [
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verifyTransaction($transactionId)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->withHeaders([
                        'Accept' => 'application/json',
                    ])
                ->get("{$this->baseUrl}/transactions/{$transactionId}/verify");

            $result = $response->json();

            if (!$response->successful() || $result['status'] !== 'success') {
                throw new \Exception($result['message'] ?? 'Verification failed');
            }

            return [
                'status' => 'success',
                'data' => $result['data'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }



}
