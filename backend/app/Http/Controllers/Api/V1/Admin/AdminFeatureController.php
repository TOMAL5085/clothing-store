<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminFeatureController extends Controller
{
    public function coupons(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        return response()->json(['data' => Coupon::query()
            ->when($request->filled('q'), fn ($q) => $q->where('code', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->string('q')).'%'))
            ->latest()->paginate($request->integer('per_page', 50))]);
    }

    public function storeCoupon(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $data = $request->validate($this->couponRules());
        $data['code'] = strtoupper($data['code']);
        return response()->json(['data' => Coupon::create($data)], 201);
    }

    public function updateCoupon(Request $request, Coupon $coupon): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $rules = $this->couponRules();
        $rules['code'] = ['sometimes', 'string', 'max:80', Rule::unique('coupons', 'code')->ignore($coupon->id)];
        $data = $request->validate($rules);
        if (isset($data['code'])) $data['code'] = strtoupper($data['code']);
        $coupon->update($data);
        return response()->json(['data' => $coupon->refresh()]);
    }

    public function deleteCoupon(Coupon $coupon): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted.']);
    }

    public function users(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $users = User::query()->select(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'ilike', '%'.$request->string('q').'%')->orWhere('email', 'ilike', '%'.$request->string('q').'%')))
            ->latest()->paginate($request->integer('per_page', 50));
        return response()->json(['data' => $users]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        abort_if($request->user()->is($user), 422, 'You cannot change your own admin role here.');
        $data = $request->validate(['role' => ['required', Rule::in(['customer', 'staff', 'manager', 'admin'])], 'status' => ['sometimes', Rule::in(['active', 'inactive'])], 'permissions' => ['nullable', 'array'], 'permissions.*' => [Rule::in(['catalog', 'commerce', 'orders', 'reports', 'support', 'marketing'])]]);
        $user->update($data);
        return response()->json(['data' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status'])]);
    }

    public function tickets(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $tickets = SupportTicket::with(['assignee:id,name,email'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->paginate($request->integer('per_page', 50));
        return response()->json(['data' => $tickets]);
    }

    public function updateTicket(Request $request, SupportTicket $ticket): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $data = $request->validate(['status' => ['sometimes', Rule::in(['open', 'pending', 'resolved', 'closed'])], 'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])], 'assigned_to' => ['nullable', 'exists:users,id'], 'admin_note' => ['nullable', 'string', 'max:5000']]);
        $ticket->update($data);
        return response()->json(['data' => $ticket->refresh()->load('assignee:id,name,email')]);
    }

    private function couponRules(): array
    {
        return ['code' => ['required', 'string', 'max:80', 'unique:coupons,code'], 'type' => ['required', Rule::in(['percent', 'fixed'])], 'value' => ['required', 'numeric', 'min:0.01'], 'is_active' => ['sometimes', 'boolean'], 'starts_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'minimum_order_amount' => ['sometimes', 'numeric', 'min:0'], 'maximum_discount_amount' => ['nullable', 'numeric', 'min:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'per_customer_limit' => ['nullable', 'integer', 'min:1']];
    }
}
