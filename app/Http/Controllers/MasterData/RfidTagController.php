<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\RfidTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RfidTagController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $tags = RfidTag::when($q, function ($query) use ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('serial_number', 'ILIKE', "%{$q}%")
                    ->orWhere('value', 'ILIKE', "%{$q}%");
            });
        })
            ->orderBy('serial_number')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Tag RFID',
            'description' => 'Kelola serial number dan UID RFID.',
            'searchPlaceholder' => 'Cari serial atau UID...',
            'items' => $tags,
            'columns' => [
                ['key' => 'serial_number', 'label' => 'Serial Number'],
                ['key' => 'value', 'label' => 'Value (UID)'],
            ],
            'fields' => [
                ['key' => 'serial_number', 'label' => 'Serial Number', 'required' => true],
                ['key' => 'value', 'label' => 'Value (UID)', 'required' => true],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.rfid-tags.index'),
                'store' => route('master-data.rfid-tags.store'),
                'update' => route('master-data.rfid-tags.update', ['rfid_tag' => '__ID__']),
                'destroy' => route('master-data.rfid-tags.destroy', ['rfid_tag' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:150', 'unique:rfid_tags,serial_number'],
            'value' => ['required', 'string', 'max:191', 'unique:rfid_tags,value'],
        ]);

        RfidTag::create($data);

        return redirect()->route('master-data.rfid-tags.index')
            ->with('success', 'RFID Tag berhasil ditambahkan.');
    }

    public function update(Request $request, RfidTag $rfid_tag)
    {
        $data = $request->validate([
            'serial_number' => [
                'required',
                'string',
                'max:150',
                Rule::unique('rfid_tags', 'serial_number')->ignore($rfid_tag->id, 'id'),
            ],
            'value' => [
                'required',
                'string',
                'max:191',
                Rule::unique('rfid_tags', 'value')->ignore($rfid_tag->id, 'id'),
            ],
        ]);

        $rfid_tag->update($data);

        return redirect()->route('master-data.rfid-tags.index')
            ->with('success', 'RFID Tag berhasil diperbarui.');
    }

    public function destroy(RfidTag $rfid_tag)
    {
        $rfid_tag->delete();

        return back()->with('success', 'RFID Tag berhasil dihapus.');
    }
}
