<template>
    <div class="e-sidebar">
        <div class="e-sidebar-header">
        </div>
        <ETree />
    </div>
</template>

<script>
import ETree from './ETree.vue';

export default {
    components: {
        ETree,
    },

    data() {
        return {
            app: 'explorer',
        };
    },

    methods: {
        changeApp() {
            this.$store.dispatch('setApp', this.app);
        }
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
        padding-bottom: 3px;

    }
}

</style>