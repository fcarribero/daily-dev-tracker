@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <strong>Nueva entrada</strong>
                    <form class="d-flex" method="get" action="{{ route('entries.index') }}">
                        <input type="date" class="form-control form-control-sm me-2" name="date" value="{{ $date }}">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Ir</button>
                    </form>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('entries.store') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <div class="mb-3">
                            <label class="form-label">¿Qué hiciste?</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" rows="3" name="content" placeholder="Escribe en texto plano lo que trabajaste...">{{ old('content') }}</textarea>
                            @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar entrada</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <strong>Entradas del {{ \Illuminate\Support\Carbon::parse($date)->isoFormat('D [de] MMMM YYYY') }}</strong>

                    @if(!$entries->isEmpty())
                    <form method="post" action="{{ route('entries.summary') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="btn btn-sm btn-ai">🤖 Generar Daily</button>
                    </form>
                    @endif
                </div>
                <div class="list-group list-group-flush">
                    @forelse($entries as $e)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="small text-muted">{{ $e->created_at->format('H:i') }} — {{ \Illuminate\Support\Carbon::parse($e->work_date)->toDateString() }}</div>
                                    <div style="white-space: pre-wrap">{{ $e->content }}</div>
                                </div>
                                <div class="ms-3 text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEntryModal{{ $e->id }}">
                                        Editar
                                    </button>
                                    <form method="post" action="{{ route('entries.destroy', $e) }}?date={{ $date }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta entrada?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Modal Editar -->
                            <div class="modal fade" id="editEntryModal{{ $e->id }}" tabindex="-1" aria-labelledby="editEntryLabel{{ $e->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editEntryLabel{{ $e->id }}">Editar entrada</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post" action="{{ route('entries.update', $e) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Fecha</label>
                                                    <input type="date" name="work_date" class="form-control" value="{{ old('work_date', \Illuminate\Support\Carbon::parse($e->work_date)->toDateString()) }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Contenido</label>
                                                    <textarea name="content" class="form-control" rows="4">{{ old('content', $e->content) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">No hay entradas para esta fecha.</div>
                    @endforelse
                </div>
            </div>

            @if(session('summary'))
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Resumen Daily</strong>
                        @if(session('summary_source') === 'ai')
                            <span class="badge bg-info">Generado con IA</span>
                        @else
                            <span class="badge bg-secondary">Generado localmente</span>
                        @endif
                    </div>
                    <div class="card-body">
                        {!! \Illuminate\Support\Str::markdown(session('summary'), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
