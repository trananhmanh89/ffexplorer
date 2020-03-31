<template>
    <div class="e-sidebar">
        <div class="e-sidebar-header">
            <select class="e-sdiebar-app-select" v-model="app" @change="changeApp">
                <option value="explorer">Explorer</option>
                <option value="database">Database</option>
            </select>
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

        .e-sdiebar-app-select {
            margin-bottom: 0;
        }
    }
}

</style>