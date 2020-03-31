<template>
    <div class="d-sidebar">
        <div class="d-sidebar-header">
            <select class="d-sdiebar-app-select" v-model="app" @change="changeApp">
                <option value="explorer">Explorer</option>
                <option value="database">Database</option>
            </select>
            <input 
                class="d-sidebar-table-search" 
                type="text" 
                placeholder="search table..."
                v-model="keyword"
                @input="searchDb">
        </div>
        <DSidbarItems />
    </div>
</template>

<script>
import DSidbarItems from './DSidebarItems.vue';
import debounce from 'lodash/debounce';

export default {
    components: {
        DSidbarItems,
    },

    data() {
        return {
            app: 'database',
            keyword: '',
        };
    },

    methods: {
        changeApp() {
            this.$store.dispatch('setApp', this.app);
        },

        searchDb: debounce(function() {
            this.$store.commit('searchDb', this.keyword);
        }, 300),
    }
}
</script>

<style lang="scss">
.d-sidebar {
    width: 300px;

    .d-sidebar-header {
        display: flex;
        min-height: 40px;
        font-size: 14px;
        line-height: 40px;
        font-weight: bold;
        padding-bottom: 3px;
        align-items: center;

        .d-sdiebar-app-select {
            margin-bottom: 0;
        }

        .d-sidebar-table-search {
            margin: 0 0 0 5px;
        }
    }
}

</style>