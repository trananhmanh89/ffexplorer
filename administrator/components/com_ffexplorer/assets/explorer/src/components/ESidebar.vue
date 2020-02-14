<template>
    <div class="e-sidebar">
        <div class="e-sidebar-header">Explorer</div>
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

<style lang="scss">
.e-sidebar {
    width: 300px;

    .e-sidebar-header {
        min-height: 40px;
        font-size: 14px;
        line-height: 40px;
        font-weight: bold;
    }
}

</style>