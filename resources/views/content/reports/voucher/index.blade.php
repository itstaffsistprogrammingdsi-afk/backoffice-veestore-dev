@extends('layouts/contentNavbarLayout')

@section('title','Report Voucher')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

<h4 class="py-3 mb-4">
<span class="text-muted fw-light">Reports /</span> Voucher
</h4>

<div class="card">

<h5 class="card-header">
Voucher Usage Report
</h5>


{{-- FILTER --}}
<div class="card-body border-bottom">

<form method="GET" class="row g-2">

<div class="col-md-3">

<select name="voucher" class="form-control">

<option value="">All Voucher</option>

@foreach($voucherList as $item)

<option value="{{ $item }}" {{ request('voucher')==$item?'selected':'' }}>
{{ $item }}
</option>

@endforeach

</select>

</div>


<div class="col-md-3">

<input
type="date"
name="start"
value="{{ request('start') }}"
class="form-control">

</div>


<div class="col-md-3">

<input
type="date"
name="end"
value="{{ request('end') }}"
class="form-control">

</div>


<div class="col-md-3 d-flex gap-2">

<button class="btn btn-primary">
Filter
</button>

<a
href="{{ route('reports.voucher.export',request()->all()) }}"
class="btn btn-success">

Export Excel

</a>

</div>

</form>

</div>


<div class="table-responsive">

<table class="table">

<thead class="table-light">

<tr>
<th>Code</th>
<th>Name</th>
<th>Type</th>
<th>Value</th>
<th>Context</th>
<th>Used</th>
<th>Action</th>
</tr>

</thead>

<tbody>

@forelse($vouchers as $v)

<tr>

<td>{{ $v->code }}</td>

<td>{{ $v->name }}</td>

<td>{{ $v->type }}</td>

<td>{{ number_format($v->value) }}</td>

<td>

@if($v->context == 'store')

<span class="badge bg-label-success">
Store
</span>

@else

<span class="badge bg-label-primary">
Ecommerce
</span>

@endif

</td>

<td>

<span class="badge bg-label-danger">
{{ $v->used_total }}
</span>

</td>

<td>

<button
class="btn btn-sm btn-primary btn-detail"
data-id="{{ $v->id }}"
data-bs-toggle="modal"
data-bs-target="#modalDetail">

Detail

</button>

</td>

</tr>

@empty

<tr>
<td colspan="7" class="text-center">
No Data
</td>
</tr>

@endforelse

</tbody>

</table>

</div>


<div class="card-footer">

{{ $vouchers->links() }}

</div>

</div>

</div>



{{-- MODAL DETAIL --}}

<div class="modal fade" id="modalDetail">

<div class="modal-dialog modal-lg">

<div class="modal-content">

<div class="modal-header">

<h5 class="modal-title">

Voucher Usage Detail

</h5>

<button class="btn-close" data-bs-dismiss="modal"></button>

</div>


<div class="modal-body">

<table class="table">

<thead>

<tr>
<th>Order</th>
<th>Customer</th>
<th>Value</th>
<th>Source</th>
<th>Date</th>
</tr>

</thead>

<tbody id="detailBody">

<tr>
<td colspan="5" class="text-center">
Click detail to load data
</td>
</tr>

</tbody>

</table>

</div>

</div>

</div>

</div>

@endsection



@push('page-script')

<script>

document.querySelectorAll('.btn-detail').forEach(btn => {

btn.addEventListener('click',function(){

let id = this.dataset.id

document.getElementById('detailBody').innerHTML =
`<tr><td colspan="5" class="text-center">Loading...</td></tr>`

fetch('/reports/voucher/'+id+'/detail')

.then(res => res.json())

.then(data => {

let html = ''

data.forEach(r => {

html += `
<tr>
<td>${r.order_number}</td>
<td>${r.customer_name}</td>
<td>${r.value}</td>
<td>${r.source}</td>
<td>${r.created_at}</td>
</tr>
`

})

if(html === ''){
html = `<tr><td colspan="5" class="text-center">No Data</td></tr>`
}

document.getElementById('detailBody').innerHTML = html

})

})

})

</script>

@endpush