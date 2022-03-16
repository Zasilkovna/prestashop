<div class="panel-footer">
    <button type="submit" class="btn btn-default pull-right" name="submitPacketeryOrderGrid" value="submitPacketeryOrderGrid">
        <i class="process-icon-save"></i> {l s='Save' mod='packetery'}
    </button>
</div>
<script type="text/javascript">
    // in old PrestaShop, this hook is outside the form tag
    $('button[name="submitPacketeryOrderGrid"]').on('click', function (e) {
        e.preventDefault();
        $('form#orders').submit();
    });
</script>
