<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudCheck;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\StoreSetting;
use App\Models\VisitorCount;
use App\Models\WeightPriceRule;
use App\Models\WholesaleCustomer;
use App\Models\WholesaleCustomerLedger;
use App\Models\Shipment;
use App\Models\User;
use App\Models\BlockedIp;
use App\Services\ShippingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Messaging\MessageGatewayResolver;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PhaseOperationsController extends Controller
{
    public function __construct(private readonly ShippingService $shipping)
    {
    }

    public function wholesaleCustomers(Request $request)
    {
        $customers=WholesaleCustomer::query()->with('ledger:id,wholesale_customer_id,entry_type,amount')->latest()->paginate($request->integer('per_page', 25));
        $customers->getCollection()->transform(function (WholesaleCustomer $customer) { $customer->setAttribute('balance', $customer->ledger->sum(fn ($entry) => $entry->entry_type === 'debit' ? (float) $entry->amount : -(float) $entry->amount)); return $customer; });
        return response()->json(['data' => $customers]);
    }

    public function storeWholesaleCustomer(Request $request)
    {
        $data = $request->validate(['code'=>'required|string|max:40|unique:wholesale_customers,code','name'=>'required|string|max:160','email'=>'nullable|email','phone'=>'nullable|string|max:40','company'=>'nullable|string|max:160','credit_limit'=>'nullable|numeric|min:0','is_active'=>'nullable|boolean']);
        return response()->json(['data'=>WholesaleCustomer::create($data)], 201);
    }

    public function updateWholesaleCustomer(Request $request, WholesaleCustomer $customer)
    {
        $data = $request->validate(['name'=>'sometimes|string|max:160','email'=>'nullable|email','phone'=>'nullable|string|max:40','company'=>'nullable|string|max:160','credit_limit'=>'nullable|numeric|min:0','is_active'=>'nullable|boolean']);
        $customer->update($data); return response()->json(['data'=>$customer]);
    }

    public function deleteWholesaleCustomer(WholesaleCustomer $customer) { $customer->delete(); return response()->json(['message'=>'Wholesale customer deleted.']); }

    public function markWholesaleOrder(Request $request, Order $order)
    {
        $data = $request->validate(['wholesale_customer_id'=>'required|exists:wholesale_customers,id']);
        $customer = WholesaleCustomer::findOrFail($data['wholesale_customer_id']);
        abort_if(! $customer->is_active, 422, 'The wholesale customer is inactive.');
        $order->update(['wholesale_customer_id'=>$customer->id]);
        return response()->json(['data'=>$order->fresh()->load('wholesaleCustomer')]);
    }

    public function wholesaleLedger(Request $request, WholesaleCustomer $customer)
    {
        $items = $customer->ledger()->latest('entry_date')->paginate($request->integer('per_page', 50));
        return response()->json(['data'=>$items, 'balance'=>(float) $customer->ledger()->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE -amount END),0) as balance")->value('balance')]);
    }

    public function addWholesaleLedger(Request $request, WholesaleCustomer $customer)
    {
        $data=$request->validate(['reference'=>'nullable|string|max:80','entry_type'=>'required|in:debit,credit','amount'=>'required|numeric|min:0.01','description'=>'nullable|string|max:500','entry_date'=>'nullable|date']);
        $data['wholesale_customer_id']=$customer->id; $data['entry_date']=$data['entry_date'] ?? now()->toDateString();
        return response()->json(['data'=>WholesaleCustomerLedger::create($data)],201);
    }

    public function weightRules(Request $request)
    {
        $query=WeightPriceRule::with('product:id,name,sku')->latest(); if($request->filled('product_id')) $query->where('product_id',$request->integer('product_id')); return response()->json(['data'=>$query->paginate($request->integer('per_page',50))]);
    }

    public function storeWeightRule(Request $request)
    {
        $data=$request->validate(['product_id'=>'nullable|exists:products,id','min_weight'=>'required|numeric|min:0','max_weight'=>'nullable|numeric|gt:min_weight','price'=>'required|numeric|min:0','unit'=>'nullable|string|max:10','is_active'=>'nullable|boolean']); return response()->json(['data'=>WeightPriceRule::create($data)],201);
    }

    public function deleteWeightRule(WeightPriceRule $rule) { $rule->delete(); return response()->json(['message'=>'Weight price rule deleted.']); }

    public function assignOrder(Request $request, Order $order)
    {
        $data=$request->validate(['staff_id'=>'required|exists:users,id']);
        $assignment=OrderAssignment::updateOrCreate(['order_id'=>$order->id,'status'=>'assigned'],['staff_id'=>$data['staff_id'],'assigned_by'=>$request->user()->id,'assigned_at'=>now()]);
        return response()->json(['data'=>$assignment->load('staff:id,name,email')]);
    }

    public function autoDisburse(Request $request)
    {
        $data=$request->validate(['staff_id'=>'nullable|exists:users,id','limit'=>'nullable|integer|min:1|max:500']);
        $staff = $data['staff_id'] ? User::findOrFail($data['staff_id']) : null;
        $staffPool = $staff ? collect([$staff]) : User::query()->where('status','active')->whereIn('role',['admin','staff'])->get();
        abort_if($staffPool->isEmpty(), 422, 'No active staff members are available for disbursement.');
        $orders=Order::query()->where('payment_status','paid')->whereIn('status',['confirmed','processing'])->whereNull('disbursed_at')->whereDoesntHave('assignments', fn($q)=>$q->whereIn('status',['assigned','disbursed']))->oldest()->limit($data['limit'] ?? 100)->get();
        $assigned=[];
        foreach($orders as $order){
            $chosen=$staffPool->sortBy(fn($member)=>OrderAssignment::where('staff_id',$member->id)->whereIn('status',['assigned','disbursed'])->count())->first();
            $assignment=OrderAssignment::create(['order_id'=>$order->id,'staff_id'=>$chosen->id,'assigned_by'=>$request->user()->id,'status'=>'disbursed','assigned_at'=>now(),'completed_at'=>now()]);
            $order->update(['disbursed_at'=>now()]); $assigned[]=['order_number'=>$order->number,'staff_id'=>$chosen->id,'assignment_id'=>$assignment->id];
        }
        return response()->json(['data'=>['assigned'=>$assigned,'count'=>count($assigned)]]);
    }

    public function disburseOrder(Request $request, Order $order)
    {
        $order->update(['disbursed_at'=>now()]); $order->assignments()->update(['status'=>'disbursed','completed_at'=>now()]); return response()->json(['data'=>$order->fresh()->load('assignments.staff:id,name,email')]);
    }

    public function assignments(Request $request)
    {
        $q=OrderAssignment::with(['order:id,number,status,total,created_at','staff:id,name,email'])->latest(); if($request->filled('staff_id')) $q->where('staff_id',$request->integer('staff_id')); if($request->filled('status')) $q->where('status',(string)$request->string('status')); if($request->filled('from')) $q->whereDate('created_at','>=',$request->date('from')); if($request->filled('to')) $q->whereDate('created_at','<=',$request->date('to')); return response()->json(['data'=>$q->paginate($request->integer('per_page',50))]);
    }

    public function staffOrderReport(Request $request)
    {
        return $this->assignments($request);
    }

    public function orderReport(Request $request)
    {
        $q=$this->filteredOrders($request); return response()->json(['data'=>$q->paginate($request->integer('per_page',50))]);
    }

    public function downloadOrders(Request $request)
    {
        $orders=$this->filteredOrders($request)->get();
        return response()->streamDownload(function() use ($orders){ $handle=fopen('php://output','wb'); fputcsv($handle,['order_number','created_at','status','payment_status','customer_email','subtotal','discount','shipping','tax','total','courier_reference']); foreach($orders as $order) fputcsv($handle,[$order->number,$order->created_at?->toIso8601String(),$order->status,$order->payment_status,$order->user?->email ?? $order->shippingAddress?->email,$order->subtotal,$order->discount,$order->shipping,$order->tax,$order->total,$order->courier_reference]); fclose($handle); }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type'=>'text/csv']);
    }

    private function filteredOrders(Request $request)
    {
        $q=Order::with(['items','user','shippingAddress','assignments.staff:id,name,email']); foreach(['status','payment_status'] as $field) if($request->filled($field)) $q->where($field,(string)$request->string($field)); if($request->filled('from')) $q->whereDate('created_at','>=',$request->date('from')); if($request->filled('to')) $q->whereDate('created_at','<=',$request->date('to')); if($request->filled('search')) { $needle='%'.str_replace(['%','_'],['\\%','\\_'],(string)$request->string('search')).'%'; $q->where(fn($x)=>$x->where('number','ilike',$needle)->orWhereHas('user',fn($u)=>$u->where('email','ilike',$needle))); }
        $allowed=['created_at','total','status','payment_status','number']; $sort=in_array((string)$request->string('sort'),$allowed,true)?(string)$request->string('sort'):'created_at'; $direction=strtolower((string)$request->string('direction'))==='asc'?'asc':'desc'; return $q->orderBy($sort,$direction);
    }

    public function courierBulk(Request $request)
    {
        $data=$request->validate(['orders'=>'required|array|min:1','orders.*.order_number'=>'required|string','orders.*.courier_reference'=>'nullable|string|max:100']); $updated=[]; foreach($data['orders'] as $row){$order=Order::where('number',$row['order_number'])->firstOrFail(); $order->update(['courier_reference'=>$row['courier_reference'] ?? null]); $updated[]=$order->fresh();} return response()->json(['data'=>$updated,'count'=>count($updated)]);
    }

    public function courierBulkImport(Request $request)
    {
        $request->validate(['file'=>'required|file|mimes:csv,txt,xlsx|max:5120']);
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = $extension === 'xlsx' ? $this->readXlsxRows($file->getRealPath()) : $this->readCsvRows($file->getRealPath());
        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), array_shift($rows) ?: []);
        $required = ['order_number'];
        abort_if(array_diff($required, $headers), 422, 'CSV must include an order_number column.');
        $updated = []; $errors = []; $line = 1;
        foreach ($rows as $row) {
            $line++;
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) continue;
            $record = array_combine($headers, array_pad($row, count($headers), null));
            try {
                $order = Order::where('number', trim((string) ($record['order_number'] ?? '')))->firstOrFail();
                $shipment = $order->shipment ?: new Shipment(['order_id' => $order->id]);
                $shipment->fill(array_filter([
                    'carrier' => $record['carrier'] ?? null,
                    'tracking_number' => $record['tracking_number'] ?? null,
                    'tracking_reference' => $record['tracking_reference'] ?? null,
                    'carrier_reference' => $record['carrier_reference'] ?? ($record['courier_reference'] ?? null),
                    'status' => $record['status'] ?? 'ready_to_ship',
                ], fn ($value) => $value !== null && $value !== ''));
                $shipment->save();
                $updated[] = $order->number;
            } catch (\Throwable $exception) {
                $errors[] = ['line' => $line, 'order_number' => $record['order_number'] ?? null, 'message' => $exception->getMessage()];
            }
        }
        return response()->json(['data'=>['updated'=>$updated,'errors'=>$errors,'count'=>count($updated)]], empty($errors) ? 200 : 207);
    }

    private function readCsvRows(string $path): array
    {
        $handle=fopen($path,'rb'); $rows=[]; while(($row=fgetcsv($handle))!==false) $rows[]=$row; fclose($handle); return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip=new \ZipArchive(); abort_if($zip->open($path)!==true,422,'The Excel workbook could not be opened.');
        $shared=[]; if(($xml=$zip->getFromName('xl/sharedStrings.xml'))!==false){$doc=simplexml_load_string($xml); $doc->registerXPathNamespace('a','http://schemas.openxmlformats.org/spreadsheetml/2006/main'); foreach($doc->xpath('//a:si') as $item){$text=''; foreach($item->xpath('.//a:t') as $part) $text.=(string)$part; $shared[]=$text;}}
        $sheet=simplexml_load_string((string)$zip->getFromName('xl/worksheets/sheet1.xml')); $sheet->registerXPathNamespace('a','http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $rows=[];
        foreach($sheet->xpath('//a:sheetData/a:row') as $row){$values=[]; foreach($row->xpath('./a:c') as $cell){$ref=(string)$cell['r']; preg_match('/([A-Z]+)/',$ref,$match); $index=0; foreach(str_split($match[1]??'') as $char) $index=$index*26+(ord($char)-64); $index--; $value=(string)($cell->v ?? ''); if((string)$cell['t']==='s') $value=$shared[(int)$value]??$value; $values[$index]=$value;} $rows[]=array_values(array_pad($values,max(count($values),1),''));}
        $zip->close(); return $rows;
    }

    public function courierAutoEntry(Request $request)
    {
        $data = $request->validate(['carrier'=>'nullable|string|max:100','limit'=>'nullable|integer|min:1|max:500']);
        $orders = Order::query()->where('payment_status','paid')->whereIn('status',['confirmed','processing','shipped'])->whereDoesntHave('shipment')->oldest()->limit($data['limit'] ?? 100)->get();
        $created = []; $errors = [];
        foreach ($orders as $order) {
            try { $shipment = $this->shipping->createShipment($order, array_filter(['carrier'=>$data['carrier'] ?? null])); $created[] = ['order_number'=>$order->number,'shipment_id'=>$shipment->id,'tracking_number'=>$shipment->tracking_number]; }
            catch (\Throwable $exception) { $errors[] = ['order_number'=>$order->number,'message'=>$exception->getMessage()]; }
        }
        return response()->json(['data'=>['created'=>$created,'errors'=>$errors,'count'=>count($created)]], empty($errors) ? 200 : 207);
    }

    public function courierReport(Request $request)
    {
        $query = Shipment::with('order:id,number,status,payment_status,total,created_at')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('carrier')) $query->where('carrier', $request->string('carrier'));
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        $shipments = $query->paginate($request->integer('per_page', 50));
        $base = Shipment::query();
        if ($request->filled('status')) $base->where('status', $request->string('status'));
        if ($request->filled('carrier')) $base->where('carrier', $request->string('carrier'));
        if ($request->filled('from')) $base->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $base->whereDate('created_at', '<=', $request->date('to'));
        return response()->json(['data'=>$shipments,'summary'=>['total'=>(clone $base)->count(),'delivered'=>(clone $base)->where('status','delivered')->count(),'failed'=>(clone $base)->where('status','failed_delivery')->count(),'in_transit'=>(clone $base)->whereIn('status',['shipped','in_transit','out_for_delivery'])->count()]]);
    }

    public function invoice(Order $order) { return response()->json(['data'=>$order->load(['items','shippingAddress','billingAddress','payment','user'])]); }

    public function emailInvoice(Order $order)
    {
        $recipient=$order->user?->email ?? $order->shippingAddress?->email;
        abort_unless($recipient, 422, 'This order has no customer email address.');
        Mail::to($recipient)->queue(new InvoiceMail($order));
        $order->update(['invoice_emailed_at'=>now()]);
        return response()->json(['message'=>'Invoice queued for email delivery.','data'=>$order->fresh(['user','shippingAddress'])]);
    }

    public function abandonedCarts(Request $request)
    {
        $carts=Cart::with(['user:id,name,email','items'])->where('updated_at','<',now()->subHour())->whereHas('items')->latest('updated_at')->paginate($request->integer('per_page',50));
        return response()->json(['data'=>$carts]);
    }

    public function recoverCart(Cart $cart)
    {
        abort_if($cart->items()->doesntExist(), 422, 'This cart has no items to recover.');
        if (! $cart->guest_token) { $cart->update(['guest_token'=>(string) Str::uuid()]); }
        $token=(string) $cart->guest_token;
        return response()->json(['data'=>['cart_id'=>$cart->id,'recovery_token'=>$token,'recovery_url'=>rtrim((string)config('app.url'),'/').'/cart?recover='.$cart->id.'&token='.$token]]);
    }

    public function fraudCheck(Order $order)
    {
        $score=0; $signals=[]; if(!$order->user_id){$score+=20;$signals[]='guest_checkout';} if($order->payment_status==='failed'){$score+=40;$signals[]='payment_failed';} if((float)$order->total>=100000){$score+=30;$signals[]='high_order_value';} $level=$score>=60?'high':($score>=30?'medium':'low'); $check=FraudCheck::updateOrCreate(['order_id'=>$order->id],['risk_score'=>$score,'risk_level'=>$level,'signals'=>$signals,'status'=>'reviewed']); return response()->json(['data'=>$check]);
    }

    public function settings(Request $request)
    {
        $q=StoreSetting::query(); if(!$request->user()) $q->where('is_public',true); $settings=$q->get()->mapWithKeys(fn($s)=>[$s->key=>$s->decodedValue()]); return response()->json(['data'=>$settings]);
    }

    public function saveSetting(Request $request)
    {
        $data=$request->validate(['key'=>'required|string|max:100','value'=>'nullable','type'=>'nullable|in:string,boolean,json','is_public'=>'nullable|boolean']); $value=$data['value']; if(($data['type']??'string')==='json') $value=json_encode($value,JSON_THROW_ON_ERROR); $setting=StoreSetting::updateOrCreate(['key'=>$data['key']],['value'=>$value,'type'=>$data['type']??'string','is_public'=>$data['is_public']??false]); return response()->json(['data'=>$setting]);
    }

    public function marketingConfig(MessageGatewayResolver $messages)
    {
        $smsDriver = (string) config('messaging.sms_driver');
        $smsCredentialsReady = $smsDriver !== 'twilio' || collect(['sid','token','from'])->every(fn ($key) => filled(config("services.twilio.{$key}")));
        return response()->json(['data'=>[
            'sms_driver'=>$smsDriver,
            'sms_configured'=>$messages->configured('sms') && $smsCredentialsReady,
            'marketing_events_enabled'=>(bool) config('marketing.events_enabled', true),
        ]]);
    }

    public function fraudReport(Request $request)
    {
        $query=FraudCheck::with('order:id,number,total,payment_status,status,created_at')->latest();
        if($request->filled('risk_level')) $query->where('risk_level',$request->string('risk_level'));
        if($request->filled('status')) $query->where('status',$request->string('status'));
        if($request->filled('from')) $query->whereDate('created_at','>=',$request->date('from'));
        if($request->filled('to')) $query->whereDate('created_at','<=',$request->date('to'));
        return response()->json(['data'=>$query->paginate($request->integer('per_page',50))]);
    }

    public function blockedIps(Request $request)
    {
        return response()->json(['data'=>BlockedIp::latest()->paginate($request->integer('per_page',50))]);
    }

    public function blockIp(Request $request)
    {
        $data=$request->validate(['ip_address'=>'required|ip|unique:blocked_ips,ip_address','reason'=>'nullable|string|max:255']);
        $data['blocked_by']=$request->user()->id;
        return response()->json(['data'=>BlockedIp::create($data)], 201);
    }

    public function unblockIp(BlockedIp $blockedIp)
    {
        $blockedIp->delete();
        return response()->json(['message'=>'IP address unblocked.']);
    }
}
