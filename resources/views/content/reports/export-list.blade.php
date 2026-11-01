@extends('layouts/contentNavbarLayout')
@section('title', 'Export Saya')
@section('content')
<div class="card my-4"><div class="card-body">
<h4>Export Saya</h4>
<div class="table-responsive"><table class="table"><thead><tr><th>Laporan</th><th>Status</th><th>Dibuat</th><th></th></tr></thead><tbody>
@forelse($exports as $export)
<tr><td>{{ $export->filename }}</td><td>{{ ['queued'=>'Dalam antrean','processing'=>'Diproses','completed'=>'Selesai','failed'=>'Gagal'][$export->status] ?? $export->status }}</td><td>{{ $export->created_at }}</td><td><a href="{{ route('report-exports.show', $export->id) }}">Lihat</a></td></tr>
@empty
<tr><td colspan="4">Belum ada permintaan export.</td></tr>
@endforelse
</tbody></table></div>{{ $exports->links() }}
</div></div>
@endsection
