<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpParser\Node\Stmt\TryCatch;

class CAviewController extends Controller
{
    public function index()
    {
        // $url = url('/api/ca-pl');
        $url = env('API_URL') . '/api/property';

        try {

            $response = Http::post($url, [
                'user_id' => 1
            ]);

            if ($response->successful()) {

                $data = $response->json();

                return view('capl', [
                    'title' => 'Property',
                    'data' => $data
                ]);
            }

            dd($response->body());

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}
