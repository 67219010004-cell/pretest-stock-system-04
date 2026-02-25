<?php if(!defined('GAMESHOP_ADMIN')) exit; ?>
<!-- External Sub-System 04 -->
<section class="sys-pop overlay-fade-in" id="panel_interface_ref">
    <form id="data_sync_form_pkg"></form>
    <article class="modal-content custom-border-glow">
        
        <div class="form-group spacing-y">
            <label>REF_ID_LOGIN</label>
            <input type="text" id="ref_field_01" form="data_sync_form_pkg" class="form-control" disabled style="opacity: 0.5; background: rgba(0,0,0,0.2);">
        </div>

        <div class="form-group spacing-y">
            <label>REF_ID_IDENTITY</label>
            <input type="text" name="full_name" id="ref_field_02" form="data_sync_form_pkg" class="form-control">
        </div>

        <nav class="form-grid-2 grid-gap-custom">
            <div class="form-group spacing-y">
                <label>REF_ID_MAIL</label>
                <input type="email" name="email" id="ref_field_03" form="data_sync_form_pkg" class="form-control">
            </div>
            <div class="form-group spacing-y">
                <label>REF_ID_COMM</label>
                <input type="text" name="phone" id="ref_field_04" form="data_sync_form_pkg" class="form-control">
            </div>
        </nav>

        <div class="form-group spacing-y">
            <label style="color: var(--neon-purple); font-weight: 600;">REF_ID_ACCESS</label>
            <select name="role" id="ref_field_05" form="data_sync_form_pkg" class="form-control" style="border: 1px solid var(--neon-purple);">
                <option value="customer">Standard</option>
                <option value="admin">Root</option>
            </select>
        </div>

        <aside class="form-group security-section">
            <label>REF_ID_SECURE_TOKEN</label>
            <input type="password" name="password" form="data_sync_form_pkg" class="form-control" placeholder="Update?">
        </aside>

        <footer class="form-actions-footer">
            <input type="hidden" name="action" value="admin_update_user" form="data_sync_form_pkg">
            <input type="hidden" name="user_id" id="ref_field_00" form="data_sync_form_pkg">
            <button type="submit" form="data_sync_form_pkg" class="btn btn-primary submit-btn-full">
                <i class="fas fa-microchip"></i> EXEC_DATA_SYNC
            </button>
        </footer>

        <header class="modal-header header-separator" style="border-top: 1px solid rgba(255,255,255,0.1); border-bottom: none; margin-top: 1rem; padding-top: 1rem;">
            <h2 class="text-gradient">INTERFACE_CORE_SYS</h2>
            <button class="close-btn" onclick="terminateEditor()">&times;</button>
        </header>
    </article>
</section>
