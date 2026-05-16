<?php

// if (!function_exists('sendResponse')) {
//     function sendResponse($status, $data = [], $message = '')
//     {
//         return response()->json([
//             'status' => $status,
//             'message' => $message,
//             'data' => $data
//         ], $status == 'error' ? 500 : 200);
//     }
// }

if (!function_exists('sendResponse')) {
    function sendResponse($status, $collection, $pagination, $message = '')
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $collection,
            'meta' => $pagination
        ], $status == 'error' ? 500 : 200);
    }
}
