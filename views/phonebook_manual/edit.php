<div id="manual-entry">
    <phonebook-manual-entry :dialog="<?php echo $isDialog ? 'true' : 'false' ?>" prefix="<?php echo $prefix ?>"
                            :entry='<?php echo studip_json_encode($entry) ?>'></phonebook-manual-entry>
</div>

<script>
    new Vue({
        el: '#manual-entry'
    })
</script>
