<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Models\VisitorCount;
use App\Models\WeightPriceRule;
use Illuminate\Http\Request;

class PhaseOperationsController extends Controller
{
    public function visitor(Request $request)
    {
        $hash=hash('sha256', $request->ip().'|'.config('app.key')); $today=now()->toDateString(); VisitorCount::firstOrCreate(['visit_date'=>$today,'period'=>'daily','ip_hash'=>$hash],['path'=>$request->string('path','/')]); VisitorCount::firstOrCreate(['visit_date'=>now()->startOfMonth()->toDateString(),'period'=>'monthly','ip_hash'=>$hash],['path'=>$request->string('path','/')]); return response()->json(['message'=>'Visitor recorded.']);
    }

    public function visitorSummary(Request $request)
    {
        $date=$request->date('date')?->toDateString() ?? now()->toDateString(); $month=$request->date('month')?->startOfMonth()->toDateString() ?? now()->startOfMonth()->toDateString(); return response()->json(['data'=>['daily'=>VisitorCount::where('period','daily')->whereDate('visit_date',$date)->count(),'monthly'=>VisitorCount::where('period','monthly')->whereDate('visit_date',$month)->count()]]);
    }

    public function settings() { return response()->json(['data'=>StoreSetting::where('is_public',true)->get()->mapWithKeys(fn($s)=>[$s->key=>$s->decodedValue()])]); }

    public function weightQuote(Request $request)
    {
        $data=$request->validate(['weight'=>'required|numeric|min:0','product_id'=>'nullable|exists:products,id']); $q=WeightPriceRule::where('is_active',true)->where('min_weight','<=',$data['weight'])->where(fn($x)=>$x->whereNull('max_weight')->orWhere('max_weight','>=',$data['weight'])); if(!empty($data['product_id'])) $q->where(fn($x)=>$x->whereNull('product_id')->orWhere('product_id',$data['product_id'])); $rule=$q->orderByDesc('product_id')->orderBy('min_weight','desc')->first(); return response()->json(['data'=>$rule ? ['price'=>$rule->price,'unit'=>$rule->unit,'rule_id'=>$rule->id] : null]);
    }
}
