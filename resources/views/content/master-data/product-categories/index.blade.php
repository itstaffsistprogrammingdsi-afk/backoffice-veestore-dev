@extends('layouts/contentNavbarLayout')

@section('title', 'Master Product - Category')


<style>
/* Modal kategori dibuat tetap ringkas, baik ada error maupun tidak */
.category-modal .modal-content {
    border-radius: 0.75rem;
}

.category-modal .modal-header {
    padding: 1rem 1.25rem;
}

.category-modal .modal-body {
    padding: 1rem 1.25rem 0.75rem;
}

.category-modal .modal-footer {
    padding: 0.75rem 1.25rem 1rem;
    border-top: 0;
}

.category-modal .form-label {
    margin-bottom: 0.4rem;
}

/* Ruang bantuan/error selalu tersedia, sehingga tinggi modal tidak berubah */
.category-feedback-slot {
    min-height: 24px;
    margin-top: 0.35rem;
    font-size: 0.8125rem;
    line-height: 1.25;
}

.category-feedback-slot .invalid-feedback,
.category-feedback-slot .form-text {
    display: block;
    margin: 0;
    line-height: inherit;
}

.category-modal .modal-footer .btn {
    min-width: 94px;
}

.category-image-thumb{width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #e6e8eb;background:#f8f9fa}
.category-image-preview{width:120px;height:120px;object-fit:cover;border-radius:12px;border:1px solid #e6e8eb;background:#f8f9fa;display:none}
.category-image-preview.show{display:block}

.category-popup-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(25, 23, 32, .58);
    backdrop-filter: blur(3px);
}

.category-popup-backdrop.show {
    display: flex;
}

.category-popup {
    width: min(460px, 100%);
    padding: 32px 34px 28px;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 80px rgba(20, 20, 35, .32);
    text-align: center;
    animation: categoryPopupIn .22s ease-out;
}

.category-popup-icon {
    width: 74px;
    height: 74px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-size: 38px;
}

.category-popup.success .category-popup-icon {
    background: #28ad4f;
}

.category-popup.error .category-popup-icon {
    background: #ea3d4f;
}

.category-popup-title {
    margin: 0 0 10px;
    color: #4e4c59;
    font-size: 26px;
    font-weight: 700;
}

.category-popup-message {
    margin: 0;
    color: #606575;
    font-size: 17px;
    line-height: 1.6;
}

.category-popup-errors {
    max-height: 220px;
    margin: 14px 0 0;
    padding-left: 22px;
    overflow-y: auto;
    color: #606575;
    text-align: left;
    font-size: 15px;
    line-height: 1.55;
}

.category-popup-button {
    min-width: 130px;
    margin-top: 24px;
    padding: 10px 28px;
    border: 0;
    border-radius: 5px;
    background: #8447e6;
    box-shadow: 0 8px 18px rgba(132, 71, 230, .28);
    color: #fff;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
}

@keyframes categoryPopupIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(.96);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

</style>

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Breadcrumb --}}
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">
            Master Product /
        </span>
        Category
    </h4>

    {{-- Flash success --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex align-items-center">
                <i class="mdi mdi-check-circle-outline me-2"></i>
                <span>{{ session('success') }}</span>
            </div>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Tutup">
            </button>
        </div>
    @endif

    {{-- Flash error sistem --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex align-items-center">
                <i class="mdi mdi-alert-circle-outline me-2"></i>
                <span>{{ session('error') }}</span>
            </div>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Tutup">
            </button>
        </div>
    @endif

    {{-- Semua validation error --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex">
                <i class="mdi mdi-alert-circle-outline me-2 mt-1"></i>

                <div>
                    <div class="fw-semibold mb-1">
                        Data belum dapat disimpan.
                    </div>

                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Tutup">
            </button>
        </div>
    @endif

    <div class="card">

        {{-- Header --}}
        <div class="card-header">
            <div
                class="d-flex flex-column flex-md-row
                       align-items-md-center justify-content-between gap-3">

                <h5 class="mb-0">
                    Daftar Kategori
                </h5>

                <div
                    class="d-flex flex-column flex-sm-row
                           align-items-stretch align-items-sm-center gap-2">

                    {{-- Search --}}
                    <form
                        method="GET"
                        action="{{ route('master-data.product-categories.index') }}"
                        class="d-flex gap-2">

                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? '' }}"
                            class="form-control form-control-sm"
                            placeholder="Cari kategori..."
                            autocomplete="off">

                        <button
                            type="submit"
                            class="btn btn-outline-secondary btn-sm"
                            data-bs-toggle="tooltip"
                            title="Cari kategori">

                            <i class="mdi mdi-magnify"></i>
                        </button>

                        @if (!empty($q))
                            <a
                                href="{{ route('master-data.product-categories.index') }}"
                                class="btn btn-outline-secondary btn-sm"
                                data-bs-toggle="tooltip"
                                title="Reset pencarian">

                                <i class="mdi mdi-refresh"></i>
                            </a>
                        @endif
                    </form>

                    {{-- Tambah --}}
                    @can('create master-data-product-categories')
                        <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalCreateCategory">

                            <i class="mdi mdi-plus me-1"></i>
                            Tambah Kategori
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0">

                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">
                            #
                        </th>

                        <th>
                            Foto
                        </th>
                        <th>
                            Nama Kategori
                        </th>

                        <th
                            class="text-center"
                            style="width: 120px;">

                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="table-border-bottom-0">
                    @forelse ($categories as $index => $category)
                        <tr>
                            <td>
                                {{
                                    ($categories->currentPage() - 1)
                                    * $categories->perPage()
                                    + $index
                                    + 1
                                }}
                            </td>

                            <td>
                                @if (!empty($category->image_url))
                                    <img src="{{ $category->image_url }}"
                                         alt="{{ $category->name }}"
                                         class="category-image-thumb">
                                @else
                                    <div class="category-image-thumb d-flex align-items-center justify-content-center text-muted">
                                        <i class="mdi mdi-image-outline mdi-24px"></i>
                                    </div>
                                @endif
                            </td>

                            <td class="fw-medium">
                                <i
                                    class="mdi mdi-tag-outline
                                           mdi-20px text-primary me-2">
                                </i>

                                {{ $category->name }}
                            </td>

                            <td class="text-center">
                                <div class="dropdown">

                                    <button
                                        type="button"
                                        class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">

                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end">

                                        {{-- Edit --}}
                                        @can('update master-data-product-categories')
                                            <button
                                                type="button"
                                                class="dropdown-item btn-edit-category"
                                                data-id="{{ $category->id }}"
                                                data-name="{{ $category->name }}"
                                                data-image-url="{{ $category->image_url }}"
                                                data-update-url="{{
                                                    route(
                                                        'master-data.product-categories.update',
                                                        $category->id
                                                    )
                                                }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditCategory">

                                                <i class="mdi mdi-pencil-outline me-1"></i>
                                                Edit
                                            </button>
                                        @endcan

                                        {{-- Delete --}}
                                        @can('delete master-data-product-categories')
                                            <button
                                                type="button"
                                                class="dropdown-item text-danger btn-delete-category"
                                                data-name="{{ $category->name }}"
                                                data-delete-url="{{
                                                    route(
                                                        'master-data.product-categories.destroy',
                                                        $category->id
                                                    )
                                                }}">

                                                <i class="mdi mdi-trash-can-outline me-1"></i>
                                                Hapus
                                            </button>
                                        @endcan

                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-muted py-4">

                                <i
                                    class="mdi mdi-folder-open-outline
                                           mdi-36px d-block mb-2">
                                </i>

                                @if (!empty($q))
                                    Kategori dengan kata kunci
                                    <strong>{{ $q }}</strong>
                                    tidak ditemukan.
                                @else
                                    Belum ada data kategori.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        @if ($categories->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{
                    $categories
                        ->appends(['q' => $q ?? null])
                        ->links()
                }}
            </div>
        @endif

    </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL CREATE --}}
{{-- ========================================================= --}}

@can('create master-data-product-categories')
    <div
        class="modal fade category-modal"
        id="modalCreateCategory"
        tabindex="-1"
        aria-labelledby="modalCreateCategoryLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-md modal-dialog-centered">

            <div class="modal-content">

                <form
                    id="formCreateCategory"
                    method="POST"
                    action="{{
                        route('master-data.product-categories.store')
                    }}"
                    enctype="multipart/form-data"
                    novalidate>

                    @csrf

                    <div class="modal-header">

                        <h5
                            class="modal-title"
                            id="modalCreateCategoryLabel">

                            <i class="mdi mdi-plus me-1"></i>
                            Tambah Kategori
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Tutup">
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">

                            <label
                                for="create_name"
                                class="form-label">

                                Nama Kategori

                                <span class="text-danger">
                                    *
                                </span>
                            </label>

                            <div class="input-group input-group-merge">

                                <span class="input-group-text">
                                    <i class="mdi mdi-tag-outline"></i>
                                </span>

                                <input
                                    type="text"
                                    name="name"
                                    id="create_name"
                                    value="{{
                                        $errors->createCategory->any()
                                            ? old('name')
                                            : ''
                                    }}"
                                    class="form-control
                                        @if (
                                            $errors->createCategory->has('name')
                                        )
                                            is-invalid
                                        @endif"
                                    placeholder="Contoh: Bahan Baku"
                                    maxlength="100"
                                    autocomplete="off"
                                    required>

                            </div>

                            <div class="category-feedback-slot">
                                @if ($errors->createCategory->has('name'))
                                    <div class="invalid-feedback">
                                        {{ $errors->createCategory->first('name') }}
                                    </div>
                                @else
                                    <div class="form-text text-muted">
                                        Maksimal 100 karakter.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="create_image" class="form-label">Gambar Kategori</label>
                            <input type="file"
                                   name="image"
                                   id="create_image"
                                   class="form-control @if($errors->createCategory->has('image')) is-invalid @endif"
                                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <div class="category-feedback-slot">
                                @if($errors->createCategory->has('image'))
                                    <div class="invalid-feedback">{{ $errors->createCategory->first('image') }}</div>
                                @else
                                    <div class="form-text text-muted">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                                @endif
                            </div>
                            <img id="create_image_preview"
                                 class="category-image-preview mt-2"
                                 alt="Preview gambar kategori">
                        </div>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                            Batal
                        </button>

                        <button
                            id="btnCreateSave"
                            type="submit"
                            class="btn btn-primary">

                            <i class="mdi mdi-content-save-outline me-1"></i>
                            Simpan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endcan

{{-- ========================================================= --}}
{{-- MODAL EDIT --}}
{{-- ========================================================= --}}

@can('update master-data-product-categories')
    <div
        class="modal fade category-modal"
        id="modalEditCategory"
        tabindex="-1"
        aria-labelledby="modalEditCategoryLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-md modal-dialog-centered">

            <div class="modal-content">

                <form
                    id="formEditCategory"
                    method="POST"
                    action="#"
                    enctype="multipart/form-data"
                    novalidate>

                    @csrf
                    @method('PUT')

                    <div class="modal-header">

                        <h5
                            class="modal-title"
                            id="modalEditCategoryLabel">

                            <i class="mdi mdi-pencil-outline me-1"></i>
                            Edit Kategori
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Tutup">
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">

                            <label
                                for="edit_name"
                                class="form-label">

                                Nama Kategori

                                <span class="text-danger">
                                    *
                                </span>
                            </label>

                            <div class="input-group input-group-merge">

                                <span class="input-group-text">
                                    <i class="mdi mdi-tag-outline"></i>
                                </span>

                                <input
                                    type="text"
                                    name="name"
                                    id="edit_name"
                                    value="{{
                                        $errors->editCategory->any()
                                            ? old('name')
                                            : ''
                                    }}"
                                    class="form-control
                                        @if (
                                            $errors->editCategory->has('name')
                                        )
                                            is-invalid
                                        @endif"
                                    placeholder="Nama kategori"
                                    maxlength="100"
                                    autocomplete="off"
                                    required>

                            </div>

                            <div class="category-feedback-slot">
                                @if ($errors->editCategory->has('name'))
                                    <div class="invalid-feedback">
                                        {{ $errors->editCategory->first('name') }}
                                    </div>
                                @else
                                    <div class="form-text text-muted">
                                        Maksimal 100 karakter.
                                    </div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="edit_image" class="form-label">
                                    Gambar Kategori
                                </label>

                                <input
                                    type="file"
                                    name="image"
                                    id="edit_image"
                                    class="form-control
                                        @if ($errors->editCategory->has('image'))
                                            is-invalid
                                        @endif"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                                <div class="category-feedback-slot">
                                    @if ($errors->editCategory->has('image'))
                                        <div class="invalid-feedback">
                                            {{ $errors->editCategory->first('image') }}
                                        </div>
                                    @else
                                        <div class="form-text text-muted">
                                            Pilih gambar baru untuk mengganti gambar lama.
                                            Kosongkan jika gambar tidak diubah.
                                        </div>
                                    @endif
                                </div>

                                <img
                                    id="edit_image_preview"
                                    class="category-image-preview mt-2"
                                    alt="Preview gambar kategori">
                            </div>

                            <input
                                type="hidden"
                                name="id"
                                id="edit_id"
                                value="{{ old('id') }}">

                            <input
                                type="hidden"
                                name="update_url"
                                id="edit_update_url"
                                value="{{ old('update_url') }}">

                            <input
                                type="hidden"
                                name="current_image_url"
                                id="edit_current_image_url"
                                value="{{ old('current_image_url') }}">
                        </div>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                            Batal
                        </button>

                        <button
                            id="btnEditUpdate"
                            type="submit"
                            class="btn btn-primary">

                            <i class="mdi mdi-content-save-outline me-1"></i>
                            Update
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endcan

{{-- ========================================================= --}}
{{-- FORM DELETE --}}
{{-- ========================================================= --}}

@can('delete master-data-product-categories')
    <form
        id="formDeleteCategory"
        method="POST"
        class="d-none">

        @csrf
        @method('DELETE')
    </form>
@endcan

{{-- ========================================================= --}}
{{-- SCRIPT --}}
{{-- ========================================================= --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Bootstrap tooltip
    |--------------------------------------------------------------------------
    */

    if (typeof bootstrap !== 'undefined') {
        const tooltipElements = document.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        );

        tooltipElements.forEach(function (element) {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Elemen modal dan form
    |--------------------------------------------------------------------------
    */

    const modalCreateElement = document.getElementById(
        'modalCreateCategory'
    );

    const modalEditElement = document.getElementById(
        'modalEditCategory'
    );

    const formCreate = document.getElementById(
        'formCreateCategory'
    );

    const formEdit = document.getElementById(
        'formEditCategory'
    );

    const formDelete = document.getElementById(
        'formDeleteCategory'
    );

    const createNameInput = document.getElementById(
        'create_name'
    );

    const editNameInput = document.getElementById(
        'edit_name'
    );

    const editIdInput = document.getElementById(
        'edit_id'
    );

    const editUpdateUrlInput = document.getElementById(
        'edit_update_url'
    );

    const createImageInput =
        document.getElementById('create_image');

    const editImageInput =
        document.getElementById('edit_image');

    const createImagePreview =
        document.getElementById('create_image_preview');

    const editImagePreview =
        document.getElementById('edit_image_preview');

    const editCurrentImageUrlInput =
        document.getElementById('edit_current_image_url');


    /*
    |--------------------------------------------------------------------------
    | Fokus input saat modal dibuka
    |--------------------------------------------------------------------------
    */

    if (modalCreateElement && createNameInput) {
        modalCreateElement.addEventListener(
            'shown.bs.modal',
            function () {
                createNameInput.focus();
            }
        );
    }

    if (modalEditElement && editNameInput) {
        modalEditElement.addEventListener(
            'shown.bs.modal',
            function () {
                editNameInput.focus();
                editNameInput.select();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reset modal create ketika ditutup
    |--------------------------------------------------------------------------
    | Jangan reset jika ada validation error.
    |--------------------------------------------------------------------------
    */

    if (modalCreateElement && formCreate) {
        modalCreateElement.addEventListener(
            'hidden.bs.modal',
            function () {
                const hasCreateError =
                    @json($errors->createCategory->any());

                if (!hasCreateError) {
                    formCreate.reset();

                    formCreate
                        .querySelectorAll('.is-invalid')
                        .forEach(function (element) {
                            element.classList.remove('is-invalid');
                        });
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reset modal edit ketika ditutup
    |--------------------------------------------------------------------------
    */

    if (modalEditElement && formEdit) {
        modalEditElement.addEventListener(
            'hidden.bs.modal',
            function () {
                const hasEditError =
                    @json($errors->editCategory->any());

                if (!hasEditError) {
                    formEdit.reset();

                    if (editImagePreview) {
                        editImagePreview.removeAttribute('src');
                        editImagePreview.classList.remove('show');
                    }

                    formEdit
                        .querySelectorAll('.is-invalid')
                        .forEach(function (element) {
                            element.classList.remove('is-invalid');
                        });
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Isi modal edit
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.btn-edit-category')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                if (!formEdit) {
                    return;
                }

                const id = this.dataset.id || '';
                const name = this.dataset.name || '';
                const updateUrl =
                    this.dataset.updateUrl || '#';

                const imageUrl =
                    this.dataset.imageUrl || '';

                formEdit.action = updateUrl;

                if (editIdInput) {
                    editIdInput.value = id;
                }

                if (editNameInput) {
                    editNameInput.value = name;
                    editNameInput.classList.remove('is-invalid');
                }

                if (editUpdateUrlInput) {
                    editUpdateUrlInput.value = updateUrl;
                }

                if (editImageInput) {
                    editImageInput.value = '';
                    editImageInput.classList.remove('is-invalid');
                }

                if (editCurrentImageUrlInput) {
                    editCurrentImageUrlInput.value = imageUrl;
                }

                if (editImagePreview) {
                    if (imageUrl) {
                        editImagePreview.src = imageUrl;
                        editImagePreview.classList.add('show');
                    } else {
                        editImagePreview.removeAttribute('src');
                        editImagePreview.classList.remove('show');
                    }
                }
            });
        });


    function bindCategoryImagePreview(input, preview) {
        if (!input || !preview) {
            return;
        }

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            if (!file.type || !file.type.startsWith('image/')) {
                this.value = '';
                alert('File yang dipilih harus berupa gambar.');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                this.value = '';
                alert('Ukuran gambar maksimal 2 MB.');
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.classList.add('show');
            };

            reader.readAsDataURL(file);
        });
    }

    bindCategoryImagePreview(createImageInput, createImagePreview);
    bindCategoryImagePreview(editImageInput, editImagePreview);


    /*
    |--------------------------------------------------------------------------
    | ALT + ENTER untuk submit
    |--------------------------------------------------------------------------
    */

    if (modalCreateElement && formCreate) {
        modalCreateElement.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.altKey &&
                    event.key === 'Enter'
                ) {
                    event.preventDefault();
                    formCreate.requestSubmit();
                }
            }
        );
    }

    if (modalEditElement && formEdit) {
        modalEditElement.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.altKey &&
                    event.key === 'Enter'
                ) {
                    event.preventDefault();
                    formEdit.requestSubmit();
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalisasi input sebelum submit
    |--------------------------------------------------------------------------
    */

    function normalizeInputValue(input) {
        if (!input) {
            return;
        }

        input.value = input.value
            .trim()
            .replace(/\s+/g, ' ');
    }


    /*
    |--------------------------------------------------------------------------
    | Cegah double submit
    |--------------------------------------------------------------------------
    */

    function handleSubmit(form, input, processingText) {
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {

            normalizeInputValue(input);

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                form.classList.add('was-validated');

                return;
            }

            const submitButton = form.querySelector(
                'button[type="submit"]'
            );

            if (!submitButton) {
                return;
            }

            if (submitButton.disabled) {
                event.preventDefault();
                return;
            }

            submitButton.disabled = true;

            submitButton.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-1"
                    role="status"
                    aria-hidden="true">
                </span>
                ${processingText}
            `;
        });
    }

    handleSubmit(
        formCreate,
        createNameInput,
        'Menyimpan...'
    );

    handleSubmit(
        formEdit,
        editNameInput,
        'Memperbarui...'
    );


    /*
    |--------------------------------------------------------------------------
    | Hapus kategori
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.btn-delete-category')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                if (!formDelete) {
                    return;
                }

                const categoryName =
                    this.dataset.name || 'kategori ini';

                const deleteUrl =
                    this.dataset.deleteUrl || '';

                if (!deleteUrl) {
                    alert('URL hapus tidak ditemukan.');
                    return;
                }

                const confirmed = confirm(
                    `Yakin ingin menghapus kategori "${categoryName}"?`
                );

                if (!confirmed) {
                    return;
                }

                formDelete.action = deleteUrl;
                formDelete.submit();
            });
        });


    /*
    |--------------------------------------------------------------------------
    | Buka kembali modal create jika validasi gagal
    |--------------------------------------------------------------------------
    */

    const hasCreateValidationError =
        @json($errors->createCategory->any());

    if (
        hasCreateValidationError &&
        modalCreateElement &&
        typeof bootstrap !== 'undefined'
    ) {
        bootstrap.Modal
            .getOrCreateInstance(modalCreateElement)
            .show();
    }


    /*
    |--------------------------------------------------------------------------
    | Buka kembali modal edit jika validasi gagal
    |--------------------------------------------------------------------------
    */

    const hasEditValidationError =
        @json($errors->editCategory->any());

    if (
        hasEditValidationError &&
        modalEditElement &&
        formEdit &&
        typeof bootstrap !== 'undefined'
    ) {
        const oldUpdateUrl = @json(old('update_url'));
        const oldId = @json(old('id'));
        const oldName = @json(old('name'));

        if (oldUpdateUrl) {
            formEdit.action = oldUpdateUrl;
        }

        if (editUpdateUrlInput) {
            editUpdateUrlInput.value =
                oldUpdateUrl || '';
        }

        if (editIdInput) {
            editIdInput.value =
                oldId || '';
        }

        if (editNameInput) {
            editNameInput.value =
                oldName || '';
        }

        bootstrap.Modal
            .getOrCreateInstance(modalEditElement)
            .show();
    }

});
</script>

{{-- ========================================================= --}}
{{-- POPUP NOTIFIKASI --}}
{{-- ========================================================= --}}
<div
    id="categoryPopupBackdrop"
    class="category-popup-backdrop"
    aria-hidden="true">

    <div
        id="categoryPopup"
        class="category-popup success"
        role="alertdialog"
        aria-modal="true">

        <div class="category-popup-icon">
            <i id="categoryPopupIcon" class="mdi mdi-check"></i>
        </div>

        <h3 id="categoryPopupTitle" class="category-popup-title">
            Berhasil
        </h3>

        <p id="categoryPopupMessage" class="category-popup-message"></p>

        <ul
            id="categoryPopupErrors"
            class="category-popup-errors d-none">
        </ul>

        <button
            type="button"
            id="categoryPopupClose"
            class="category-popup-button">
            OK
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const flashSuccess = @json(session('success'));
    const flashError = @json(session('error'));
    const validationErrors = @json($errors->all());

    const backdrop = document.getElementById('categoryPopupBackdrop');
    const popup = document.getElementById('categoryPopup');
    const icon = document.getElementById('categoryPopupIcon');
    const title = document.getElementById('categoryPopupTitle');
    const message = document.getElementById('categoryPopupMessage');
    const errorList = document.getElementById('categoryPopupErrors');
    const closeButton = document.getElementById('categoryPopupClose');

    function closeCategoryModals() {
        document
            .querySelectorAll('.category-modal.show')
            .forEach(function (modalElement) {
                if (typeof bootstrap !== 'undefined') {
                    const instance =
                        bootstrap.Modal.getInstance(modalElement);

                    if (instance) {
                        instance.hide();
                    }
                }

                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                modalElement.setAttribute('aria-hidden', 'true');
            });

        document
            .querySelectorAll('.modal-backdrop')
            .forEach(function (element) {
                element.remove();
            });

        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    }

    function showCategoryPopup(type, heading, detail, errors) {
        if (!backdrop || !popup) {
            return;
        }

        closeCategoryModals();

        const success = type === 'success';

        popup.classList.remove('success', 'error');
        popup.classList.add(success ? 'success' : 'error');

        icon.className = success
            ? 'mdi mdi-check'
            : 'mdi mdi-close';

        title.textContent = heading;
        message.textContent = detail || '';
        errorList.innerHTML = '';

        if (Array.isArray(errors) && errors.length > 0) {
            errors.forEach(function (errorMessage) {
                const item = document.createElement('li');
                item.textContent = errorMessage;
                errorList.appendChild(item);
            });

            errorList.classList.remove('d-none');
        } else {
            errorList.classList.add('d-none');
        }

        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeCategoryPopup() {
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    closeButton?.addEventListener('click', closeCategoryPopup);

    backdrop?.addEventListener('click', function (event) {
        if (event.target === backdrop) {
            closeCategoryPopup();
        }
    });

    if (
        Array.isArray(validationErrors) &&
        validationErrors.length > 0
    ) {
        showCategoryPopup(
            'error',
            'Gagal Menyimpan',
            'Data belum dapat disimpan karena:',
            validationErrors
        );

        return;
    }

    if (flashError) {
        showCategoryPopup(
            'error',
            'Gagal',
            flashError,
            []
        );

        return;
    }

    if (flashSuccess) {
        showCategoryPopup(
            'success',
            'Berhasil',
            flashSuccess,
            []
        );
    }
});
</script>

@endsection
