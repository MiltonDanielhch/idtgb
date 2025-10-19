<!-- resources/views/partials/modal-delete.blade.php -->
<form action="#" id="delete_form" method="POST">
    {{ method_field('DELETE') }}
    {{ csrf_field() }}
    <div class="modal modal-danger fade" data-backdrop="static" id="modal-delete" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="color:rgb(255, 255, 255) !important">
                        <i class="voyager-trash"></i> <span id="delete_modal_title">¿Estás seguro que quieres eliminar?</span>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="voyager-trash" style="color: red; font-size: 4em;"></i>
                        <br>
                        {{-- ✅ TEXTO DINÁMICO --}}
                        <p><b id="delete_modal_message">¿Estás seguro que quieres eliminar este registro?</b></p>
                    </div>
                    <div class="form-group">
                        <textarea name="deleteObservation" class="form-control" rows="4" placeholder="Describa el motivo de la eliminación..." required></textarea>
                    </div>
                    <label class="checkbox-inline">
                        <input type="checkbox" id="delete_confirm_checkbox" required> Confirmar eliminación
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <input type="submit" class="btn btn-danger btn-form-delete" value="Sí, eliminar" id="delete_submit_btn">
                </div>
            </div>
        </div>
    </div>
</form>
