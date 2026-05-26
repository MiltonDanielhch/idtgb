<form action="{{ url('admin/ajax/person/store') }}" id="create-form-person" method="POST">
    <div class="modal fade" tabindex="-1" id="modal-create-person" role="dialog" style="display: none;">
        <div class="modal-dialog modal-primary">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" style="color: #ffffff !important"><i class="voyager-plus" ></i> Registrar Persona</h4>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="nombre_completo">Nombre Completo</label>
                            <input type="text" name="nombre_completo" class="form-control" placeholder="Juan Pérez Ortiz" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="ci">CI</label>
                            <input type="text" name="ci" class="form-control" placeholder="123456789" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">Celular</label>
                            <input type="text" name="phone" class="form-control" placeholder="76558214">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <input type="submit" class="btn btn-primary btn-save-person" value="Guardar">
                </div>
            </div>
        </div>
    </div>
</form>

@push('javascript')
<script>
$(document).ready(function() {
    // Manejar envío del formulario de persona
    $(document).on('submit', '#create-form-person', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.btn-save-person');
        
        // Deshabilitar botón para evitar doble envío
        submitBtn.prop('disabled', true).text('Guardando...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success && response.person) {
                    // Cerrar el modal
                    $('#modal-create-person').modal('hide');
                    
                    // Limpiar el formulario
                    form[0].reset();
                    
                    // Agregar la nueva persona al select2
                    var newOption = new Option(
                        response.person.nombre_completo + ' - CI: ' + response.person.ci,
                        response.person.id,
                        true,
                        true
                    );
                    $('.select2-ajax').append(newOption).trigger('change');
                    
                    // Mostrar mensaje de éxito
                    alert('Persona registrada exitosamente');
                } else {
                    alert('Error al registrar persona: ' + (response.error || 'Error desconocido'));
                }
            },
            error: function(xhr) {
                alert('Error al registrar persona: ' + (xhr.responseJSON?.error || xhr.statusText));
            },
            complete: function() {
                // Rehabilitar botón
                submitBtn.prop('disabled', false).text('Guardar');
            }
        });
    });
});
</script>
@endpush
