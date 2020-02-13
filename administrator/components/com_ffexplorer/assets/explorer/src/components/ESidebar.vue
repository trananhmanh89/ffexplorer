<template>
    <div class="e-sidebar">
        <h4>Explorer</h4>
        <ETree />
    </div>
</template>

<script>
import ETree from './ETree.vue';

export default {
    components: {
        ETree,
    }
}
</script>

<style>
.e-sidebar {
    width: 300px;
}

</style>