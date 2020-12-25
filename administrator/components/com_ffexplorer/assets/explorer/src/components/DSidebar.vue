<template>
    <div class="d-sidebar">
        <div class="d-sidebar-header">
            <el-input
                class="d-sidebar-table-search" 
                type="text" 
                placeholder="search table..."
                size="small"
                v-model="keyword"
                @input="searchDb" />
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
        }),
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
    }
}

</style>