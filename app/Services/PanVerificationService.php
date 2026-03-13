<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PanVerificationService
{
    public static function verify($panNumber)
    {
        try {

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'token' => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
                'secretkey' => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60'
            ])->timeout(30)
              ->post('https://api.rpacpc.com/services/get-pan-nsdl-details', [
                    'pan_number' => $panNumber
              ]);

            $data = $response->json();

            Log::info('PAN verify response', $data);

            if ($data['status'] === 'SUCCESS') {
                return $data['data'];
            }

            return null;

        } catch (\Exception $e) {

            Log::error("PAN verify failed: ".$e->getMessage());

            return null;
        }
    }
}