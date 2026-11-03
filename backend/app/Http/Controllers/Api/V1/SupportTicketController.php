<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'email' => ['required', 'email', 'max:190'], 'topic' => ['required', 'string', 'max:80'], 'message' => ['required', 'string', 'min:10', 'max:5000']]);
        $data['user_id'] = $request->user()?->id;
        $ticket = SupportTicket::create($data);
        return response()->json(['data' => $ticket], 201);
    }
}
