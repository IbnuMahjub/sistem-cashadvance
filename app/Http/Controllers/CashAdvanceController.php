<?php

namespace App\Http\Controllers;

use App\Models\tr_ca;
use Illuminate\Http\Request;

class CashAdvanceController extends Controller
{
    public function index()
    {
        // return response()->json(['data' => 'hello']);
        $data = tr_ca::with('trCA')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
