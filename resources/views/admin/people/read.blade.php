@extends('voyager::master')

@section('page_title', 'Ver Persona')

@section('content')
<div class="page-content container-fluid">
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Ver Persona
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td>{{ $person->id }}</td>
                            </tr>
                            <tr>
                                <th>Tipo de persona</th>
                                <td>{{ $person->person_type }}</td>
                            </tr>
                            <tr>
                                <th>Tipo de documento</th>
                                <td>{{ $person->tipo_doc }}</td>
                            </tr>
                            <tr>
                                <th>Documento</th>
                                <td>
                                    @if($person->person_type === 'Natural')
                                        {{ $person->ci }}{{ $person->ci_complemento ? ' '.$person->ci_complemento : '' }}
                                    @else
                                        {{ $person->nit }}
                                    @endif
                                </td>
                            </tr>

                            {{-- NATURAL --}}
                            @if($person->person_type === 'Natural')
                                <tr>
                                    <th>Nombre completo</th>
                                    <td>{{ strtoupper(trim($person->first_name.' '.$person->middle_name.' '.$person->paternal_surname.' '.$person->maternal_surname)) }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de nacimiento</th>
                                    <td>{{ $person->birth_date ? \Carbon\Carbon::parse($person->birth_date)->format('d/m/Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Edad</th>
                                    <td>{{ $person->birth_date ? \Carbon\Carbon::parse($person->birth_date)->age.' años' : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Género</th>
                                    <td>{{ $person->gender }}</td>
                                </tr>
                            @else
                                {{-- JURÍDICA --}}
                                <tr>
                                    <th>Razón social</th>
                                    <td>{{ strtoupper($person->legal_name) }}</td>
                                </tr>
                            @endif

                            <tr>
                                <th>Email</th>
                                <td>{{ $person->email ?? 'SN' }}</td>
                            </tr>
                            <tr>
                                <th>Teléfono / Celular</th>
                                <td>{{ $person->phone ?? 'SN' }}</td>
                            </tr>
                            <tr>
                                <th>Dirección</th>
                                <td>{{ $person->address ?? 'SN' }}</td>
                            </tr>
                            <tr>
                                <th>Fotografía</th>
                                <td>
                                    @if($person->image)
                                        <img src="{{ asset('storage/'.$person->image) }}" alt="Foto" style="width: 150px; height: auto; border-radius: 4px;">
                                    @else
                                        <img src="{{ asset('images/default.jpg') }}" alt="Sin foto" style="width: 150px; height: auto; border-radius: 4px;">
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Estado</th>
                                <td>
                                   <span class="badge badge-{{ $person->status == 1 ? 'success' : ($person->status == 2 ? 'warning' : 'danger') }}">
                                        {{ $person->estado_persona }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Creado</th>
                                <td>{{ $person->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Actualizado</th>
                                <td>{{ $person->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.people.index') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>
            @can('edit_people')
                <a href="{{ route('admin.people.edit', $person) }}" class="btn btn-primary">
                    <i class="voyager-edit"></i> Editar
                </a>
            @endcan
        </div>
    </div>
</div>
@stop
