{{-- resources/views/admin/tramites/wizard/create_step_5.blade.php --}}
@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 5: Documentos')

@section('wizard-content')
{{-- Formulario para agregar documentos --}}
<div class="panel panel-bordered">
    <form action="{{ route('admin.tramites.wizard.add.documento') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="panel-body">
            <h4 class="text-muted">{{ $step_title }}</h4>
            <p>Añada los documentos de respaldo requeridos para el trámite.</p>
            <hr>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0" style="list-style: none; padding-left: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-3">
                    <label>Tipo de Documento <span class="required">*</span></label>
                    <select name="tipo_doc" class="form-control" required>
                        @foreach($tiposDocumento as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Asociado a <span class="required">*</span></label>
                    <select name="person_id" class="form-control select2" required>
                        @foreach($personas as $persona)
                            <option value="{{ $persona->id }}">{{ $persona->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Archivo (PDF, JPG, PNG) <span class="required">*</span></label>
                    <input type="file" name="archivo" class="form-control" required accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-1 text-right" style="padding-top: 25px;">
                    <button type="submit" class="btn btn-success"><i class="voyager-plus"></i> Añadir</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Tabla de documentos y navegación --}}
<form action="{{ route('admin.tramites.wizard.post.step5') }}" method="POST">
    @csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-heading"><h3 class="panel-title">Documentos Agregados</h3></div>
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre del Archivo</th>
                        <th>Asociado a</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documentosSubidos as $doc)
                    <tr>
                        <td>{{ $doc->tipo_doc }}</td>
                        <td>{{ $doc->original_name }}</td>
                        <td>{{ $doc->persona->display_name ?? 'N/A' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.tramites.wizard.remove.documento', $doc->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('¿Quitar este documento?')">
                                <i class="voyager-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">Aún no se han agregado documentos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step4') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Anterior
            </a>
            <button type="submit" class="btn btn-primary pull-right">
                Siguiente <i class="voyager-angle-right"></i>
            </button>
        </div>
    </div>
</form>
@endsection
