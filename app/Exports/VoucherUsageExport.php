<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
};

class VoucherUsageExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{

public function collection()
{

/*
|--------------------------------------------------------------------------
| Ecommerce Voucher
|--------------------------------------------------------------------------
*/

$ecommerce = DB::table('ecommerce_order_vouchers as ev')

->join(
'ecommerce_orders as eo',
'eo.checkout_voucher_snapshot_id',
'=',
'ev.id'
)

->join(
'vouchers as v',
'v.id',
'=',
'ev.master_id'
)

->join(
'customers as c',
'c.id',
'=',
'eo.customer_id'
)

->select(

'v.code',
'v.name',
'v.type',
'v.value',

'eo.order_number',

'c.name as customer',

DB::raw("'Ecommerce' as source"),

'eo.created_at'

)

->where('eo.status','paid')

->get();



/*
|--------------------------------------------------------------------------
| Store Voucher
|--------------------------------------------------------------------------
*/

$store = DB::table('store_carts as sc')

->join(
'vouchers as v',
'v.id',
'=',
'sc.voucher_id'
)

->leftJoin(
'customers as c',
'c.id',
'=',
'sc.customer_id'
)

->select(

'v.code',
'v.name',
'v.type',
'v.value',

DB::raw("'-' as order_number"),

'c.name as customer',

DB::raw("'Store' as source"),

'sc.created_at'

)

->where('sc.status','paid')

->get();


return $ecommerce->merge($store);

}



/*
|--------------------------------------------------------------------------
| Excel Column Headers
|--------------------------------------------------------------------------
*/

public function headings(): array
{
return [

'Voucher Code',
'Voucher Name',
'Type',
'Value',
'Order Number',
'Customer',
'Source',
'Used Date'

];
}



/*
|--------------------------------------------------------------------------
| Mapping Format
|--------------------------------------------------------------------------
*/

public function map($row): array
{

return [

$row->code,

$row->name,

$row->type,

number_format($row->value),

$row->order_number,

$row->customer,

$row->source,

date('Y-m-d H:i:s',strtotime($row->created_at))

];

}

}