@extends('admin.layout')
@section('title', $definition['label'])
@section('content')
<a class="back-link" href="{{ route('admin.resources.index',$resource) }}">← Kembali ke {{ strtolower($definition['label']) }}</a><div class="page-heading"><div><span class="eyebrow">{{ strtoupper($definition['group']) }}</span><h1>{{ $record->exists?'Edit':'Tambah' }} {{ strtolower($definition['label']) }}</h1><p>Lengkapi informasi berikut, lalu simpan perubahan Anda.</p></div></div>
@if($errors->any())<div class="notice error" role="alert">Periksa kembali kolom yang ditandai di bawah.</div>@endif
<form method="post" action="{{ $record->exists ? route('admin.resources.update',[$resource,$record->getKey()]) : route('admin.resources.store',$resource) }}" class="panel editor-form" enctype="multipart/form-data">@csrf @if($record->exists)@method('PUT')@endif<div class="form-section-heading"><h2>Informasi {{ strtolower($definition['label']) }}</h2><p>Kolom bertanda * wajib diisi.</p></div><div class="form-grid">
@if($resource === 'media')<div class="field full-width"><label for="upload">Upload gambar / video</label><input type="file" id="upload" name="upload" data-image-upload data-preview-target="media-image-preview" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/webm"><small>Maksimal 2 MB. Upload file atau gunakan path aset yang tersedia di bawah.</small>@error('upload')<span class="field-error" role="alert">{{ $message }}</span>@enderror<div id="media-image-preview" class="image-preview" aria-live="polite"></div></div>@endif
@if(isset($definition['fields']['image_id']))
<div class="field full-width image-upload-field">
    <label for="image_upload">Upload gambar baru</label>
    <input type="file" id="image_upload" name="image_upload" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" data-image-upload data-preview-target="image-preview">
    <small>JPG, PNG, GIF, WebP atau AVIF, maksimal 2 MB. Gambar baru otomatis digunakan setelah disimpan. Anda juga dapat memilih gambar dari media library di bawah.</small>
    @error('image_upload')<span class="field-error" role="alert">{{ $message }}</span>@enderror
    <div id="image-preview" class="image-preview" aria-live="polite">
        @if($selectedImage)<figure><img src="{{ asset($selectedImage->file_path) }}" alt="{{ $selectedImage->alt_text ?? 'Gambar saat ini' }}"><figcaption>Gambar saat ini</figcaption></figure>@endif
    </div>
</div>
@endif
@if($resource === 'pages')
<div class="field full-width image-upload-field">
    <label for="page_images">Upload gambar halaman</label>
    <input type="file" id="page_images" name="page_images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif" data-image-upload data-preview-target="page-image-preview">
    <small>Maksimal 5 gambar, masing-masing 2 MB. Gambar akan ditambahkan ke media halaman saat disimpan.</small>
    @error('page_images')<span class="field-error" role="alert">{{ $message }}</span>@enderror
    @error('page_images.*')<span class="field-error" role="alert">{{ $message }}</span>@enderror
    <div id="page-image-preview" class="image-preview" aria-live="polite"></div>
</div>
@endif
@foreach($definition['fields'] as $name=>$field)
@php($value=old($name,$record->getAttribute($name) ?? ($field['default'] ?? ($field['type']==='number'?0:''))))
<div class="field {{ $field['type']==='textarea'?'full-width':'' }}"><label for="{{ $name }}">{{ $field['label'] }} @if(in_array('required',$field['rules'],true))*@endif</label>
@if($field['type']==='textarea')<textarea id="{{ $name }}" name="{{ $name }}" rows="5">{{ $value }}</textarea>
@elseif(in_array($field['type'],['select','relation']))<select id="{{ $name }}" name="{{ $name }}"><option value="">Pilih {{ strtolower($field['label']) }}</option>@foreach($options[$name] ?? $field['options'] as $key=>$label)<option value="{{ $key }}" @selected((string)$value===(string)$key)>{{ $label }}</option>@endforeach</select>
@elseif($field['type']==='checkbox')<input type="hidden" name="{{ $name }}" value="0"><label class="checkbox-label"><input id="{{ $name }}" name="{{ $name }}" type="checkbox" value="1" @checked($value)> {{ $field['label'] }}</label>
@else<input id="{{ $name }}" name="{{ $name }}" type="{{ $field['type'] }}" value="{{ $value }}" @if($field['type']==='number') step="any" min="0" @endif @if($record->exists && $name===$record->getKeyName()) readonly @endif>
@endif @error($name)<span class="field-error" role="alert">{{ $message }}</span>@enderror @if($name==='file_path')<small>Path aset yang tersedia, contoh: assets/images/destination.jpg</small>@endif</div>@endforeach
@if($resource==='pages')<fieldset class="full-width media-checks"><legend>Media halaman</legend>@foreach($media as $asset)<label><input type="checkbox" name="media_ids[]" value="{{ $asset->id }}" @checked(in_array($asset->id,old('media_ids',$record->exists?$record->media->modelKeys():[])))>{{ $asset->file_path }}</label>@endforeach @error('media_ids.*')<span class="field-error">{{ $message }}</span>@enderror</fieldset>@endif</div><div class="form-actions"><a class="button secondary" href="{{ route('admin.resources.index',$resource) }}">Batal</a><button class="button primary">Simpan perubahan →</button></div></form>
@endsection


